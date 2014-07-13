<?php
class CodeBankAPITest extends SapphireTest {
    public static $fixture_file='SnippetTest.yml';
    
    /**
     * Forces the snippet languages to be populated on setup
     */
    public function setUp() {
        parent::setUp();
        
        //Populate default languages
        singleton('SnippetLanguage')->requireDefaultRecords();
        
        //Ensure the default config is present
        singleton('CodeBankConfig')->requireDefaultRecords();
    }
    
    /**
     * Tests the api end point to ensure it is active and returning the correct response for a ping
     */
    public function testAPIEndpoint() {
        $response=$this->getAMFResponse('ServerController.connect');
        
        
        //Test that we recieved the default endpoint notice
        $this->assertNotEquals(false, $response, 'API response was not what was expected, this could indicate an error has occured or there is a problem in the response');
    }
    
    /**
     * Tests to see if the login method in the session manager actually login a user
     */
    public function testLogin() {
        $response=$this->getAMFResponse('SessionManager.login', array('user'=>'admin', 'pass'=>'admin'));
        
        
        //Check the response to ensure it is HELO
        $this->assertEquals('HELO', $response['status'], 'API Response was not HELO');
        
        
        //Verify the user was logged in
        $this->assertGreaterThan(0, Member::currentUserID(), 'Member was not logged in');
    }
    
    /**
     * Test to see if the logout method in the session manager actually logs the user out
     */
    public function testLogout() {
        //Login the admin user
        $this->objFromFixture('Member', 'admin')->login();
        
        
        //Verify the user was logged in
        $this->assertGreaterThan(0, Member::currentUserID(), 'Member was not logged in');
        
        
        //Attempt to logout
        $this->getAMFResponse('SessionManager.logout');
        
        
        //Verify the user was logged out
        $this->assertEquals(0, Member::currentUserID(), 'Member was not logged out');
    }
    
    /**
     * Tests generic api access to see if user control is enforced correctly
     */
    public function testAPIAccess() {
        //Login the noaccess account
        $this->objFromFixture('Member', 'noaccess')->login();
        
        //Try to get the languages
        $response=$this->getAMFResponse('Snippets.getLanguages');
        
        
        //Test response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        //Login the apiuser account
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Try to get the languages
        $response=$this->getAMFResponse('Snippets.getLanguages');
        
        
        //Test response
        $this->assertNotEquals('EROR', $response['status'], 'Response status should not have been EROR');
    }
    
    /**
     * Tests to see if a member can update their preferences
     */
    public function testMemberSavePreferences() {
        $member=$this->objFromFixture('Member', 'apiuser');
        $member->login();
        
        
        //Negate the current value
        $heartBeat=($member->UseHeartbeat ? 0:1);
        
        
        
        //Fetch the message from the api
        $response=$this->getAMFResponse('Server.savePreferences', array('heartbeat'=>$heartBeat));
        
        
        //Re-fetch the apiuser account
        $member=$this->objFromFixture('Member', 'apiuser');
        
        
        //Test the response to see if it matched
        $this->assertEquals($heartBeat, $member->UseHeartbeat, 'Heartbeat preference was not updated');
    }
    
    /**
     * Checks to see if a non-admin user can access admin specific sections
     */
    public function testAdminPermissionFailure() {
        $this->objFromFixture('Member', 'apiuser')->login(); //Login apiuser
        
        
        //Try fetching users list with a user with just CODE_BANK_API permissions
        $response=$this->getAMFResponse('Administration.getUsersList');
        
        
        //Verify the response was an error
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        $this->objFromFixture('Member', 'admin')->login(); //Login admin user
        
        //Try fetching users list with a user with ADMIN permissions
        $response=$this->getAMFResponse('Administration.getUsersList');
        
        
        //Verify the response was an error
        $this->assertNotEquals('EROR', $response['status'], 'Response status should have not been EROR');
    }
    
    /**
     * Tests to see if admin's can delete a user, also checks to see that a normal user cannot
     */
    public function testAdminDeleteUser() {
        $this->objFromFixture('Member', 'apiuser')->login(); //Login apiuser
        $memberToDelete=Member::get()->filter('Email', 'noaccess')->first()->ID;
        
        
        //Try deleting with a user with just CODE_BANK_API permissions
        $response=$this->getAMFResponse('Administration.deleteUser', array('id'=>$memberToDelete));
        
        
        //Verify the response was an error
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        //Verify the member was not deleted
        $this->assertGreaterThan(0, Member::get()->filter('Email', 'apiuser')->count(), 'Member was deleted');
        
        
        
        $this->objFromFixture('Member', 'admin')->login(); //Login admin user
        
        //Try deleting with a user with ADMIN permissions
        $response=$this->getAMFResponse('Administration.deleteUser', array('id'=>$memberToDelete));
        
        
        //Verify the response was an error
        $this->assertEquals('HELO', $response['status'], 'Response status should have not been EROR');
        
        
        //Verify the member was deleted
        $this->assertEquals(0, Member::get()->filter('Email', 'noaccess')->count(), 'Member was not deleted');
    }
    
    /**
     * Test the change password system, first checks to ensure that a user changing their password must provide the current password, then checks to see if an admin can change another user's password
     */
    public function testChangePassword() {
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test incorrect current password
        $response=$this->getAMFResponse('Server.changePassword', array('currPassword'=>'nimda', 'password'=>'admin1'));
        
        
        //Verify the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.CURRENT_PASSWORD_MATCH', '_Current password does not match'), $response['message'], 'Response message should have been that the current password does not match');
        
        
        //Test chancing with the correct current password
        $response=$this->getAMFResponse('Server.changePassword', array('currPassword'=>'admin', 'password'=>'admin1'));
        
        
        //Verify the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test chancing with the correct current password
        $response=$this->getAMFResponse('Administration.changeUserPassword', array('id'=>$this->objFromFixture('Member', 'noaccess')->ID, 'password'=>'admin1'));
        
        
        //Verify the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Try changing another users pasword where they don't have admin permissions
        $this->objFromFixture('Member', 'apiuser')->login(); //Login non-admin
        
        //Test chancing with the correct current password
        $response=$this->getAMFResponse('Administration.changeUserPassword', array('id'=>$this->objFromFixture('Member', 'noaccess')->ID, 'password'=>'admin1'));
        
        
        //Verify the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
    }
    
    /**
     * Tests to see if an admin can create a user, also checks to see if duplicate detection is functioning correctly
     */
    public function testCreateUser() {
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test creating a user
        $response=$this->getAMFResponse('Administration.createUser', array(
                                                                            'firstname'=>'Joe',
                                                                            'surname'=>'User',
                                                                            'username'=>'joe@example.com',
                                                                            'Password'=>'joespass'
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a user who already exists in the database
        $response=$this->getAMFResponse('Administration.createUser', array(
                                                                            'firstname'=>'Joe',
                                                                            'surname'=>'User',
                                                                            'username'=>'joe@example.com',
                                                                            'Password'=>'joespass'
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.EMAIL_EXISTS', '_An account with that email already exists'), $response['message'], 'Response message should have been that a user already exists with that email');
    }
    
    /**
     * Tests to see if the language creation is working correctly
     */
    public function testCreateLanguage() {
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test creating a language
        $response=$this->getAMFResponse('Administration.createLanguage', array(
                                                                                'language'=>'API Language',
                                                                                'fileExtension'=>'api'
                                                                            ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a duplicate language
        $response=$this->getAMFResponse('Administration.createLanguage', array(
                                                                            'language'=>'API Language',
                                                                            'fileExtension'=>'api'
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists'), $response['message'], 'Response message should have been that the language already exists');
    }
    
    /**
     * Tests to see if the language deletion is working correctly
     */
    public function testDeleteLanguage() {
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Try deleting a default language
        $response=$this->getAMFResponse('Administration.deleteLanguage', array('id'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_DELETE_ERROR', '_Language cannot be deleted, it is either not a user language or has snippets attached to it'), $response['message'], 'Response message should have been that the language is not a user language or has children');
        
        
        //Create a test language
        $lang=new SnippetLanguage();
        $lang->Name='API Language';
        $lang->FileExtension='api';
        $lang->UserLanguage=true;
        $lang->write();
        
        //Create a snippet for the language
        $snippet=new Snippet();
        $snippet->Title='test snippet';
        $snippet->Text='Hello World';
        $snippet->LanguageID=$lang->ID;
        $snippet->write();
        
        
        //Try deleting a the language
        $response=$this->getAMFResponse('Administration.deleteLanguage', array('id'=>$lang->ID));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_DELETE_ERROR', '_Language cannot be deleted, it is either not a user language or has snippets attached to it'), $response['message'], 'Response message should have been that the language is not a user language or has children');
        
        
        //Delete the snippet and try again
        $snippet->delete();
        $response=$this->getAMFResponse('Administration.deleteLanguage', array('id'=>$lang->ID));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests to see if the language editing is working correctly
     */
    public function testEditLanguage() {
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Create a test language
        $lang=new SnippetLanguage();
        $lang->Name='API Language';
        $lang->FileExtension='api';
        $lang->UserLanguage=true;
        $lang->write();
        
        
        //Test creating a language
        $response=$this->getAMFResponse('Administration.editLanguage', array(
                                                                            'id'=>$lang->ID,
                                                                            'language'=>'API Language 2',
                                                                            'fileExtension'=>'api2',
                                                                            'hidden'=>false
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        //Ensure the language actually was edited to what it should have been
        $this->assertGreaterThan(0, SnippetLanguage::get()->filter('Name', 'API Language 2')->count(), 'Language was not changed as expected');
        
        
        
        //Test editing to a duplicate language
        $response=$this->getAMFResponse('Administration.editLanguage', array(
                                                                            'id'=>$lang->ID,
                                                                            'language'=>'PHP',
                                                                            'fileExtension'=>'api',
                                                                            'hidden'=>false
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists'), $response['message'], 'Response message should have been that the language already exists');
    }
    
    /**
     * Tests creating a snippet from the api
     */
    public function testNewSnippet() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test creating a snippet
        $response=$this->getAMFResponse('Snippets.newSnippet', array(
                                                                            'title'=>'API Snippet',
                                                                            'description'=>'This is a snippet created from the api',
                                                                            'code'=>'<?php print "hello api"; ?>',
                                                                            'tags'=>'api,test,snippet,php',
                                                                            'language'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID,
                                                                            'packageID'=>0,
                                                                            'folderID'=>0
                                                                        ));
        
        //Validate the response
        $response=$this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a snippet, that belongs to a folder in another language
        $response=$this->getAMFResponse('Snippets.newSnippet', array(
                                                                            'title'=>'API Snippet',
                                                                            'description'=>'This is a snippet created from the api',
                                                                            'code'=>'<?php print "hello api"; ?>',
                                                                            'tags'=>'api,test,snippet,php',
                                                                            'language'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID,
                                                                            'packageID'=>0,
                                                                            'folderID'=>$this->objFromFixture('SnippetFolder', 'folder3')->ID
                                                                        ));
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.FOLDER_NOT_LANGUAGE', '_Folder is not in the same language as the snippet'), $response['message'], 'Response massage should have been that the folder is not in the same language');
    }
    
    /**
     * Tests saving a snippet through the api
     */
    public function testSaveSnippet() {
        $this->objFromFixture('Member', 'apiuser')->login();
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        
        //Test saving a snippet
        $response=$this->getAMFResponse('Snippets.saveSnippet', array(
                                                                    'id'=>$snippet->ID,
                                                                    'title'=>'API Snippet',
                                                                    'description'=>$snippet->Description,
                                                                    'code'=>'<?php print "hello api"; ?>',
                                                                    'tags'=>$snippet->Tags,
                                                                    'language'=>$snippet->LanguageID,
                                                                    'packageID'=>$snippet->PackageID
                                                                ));
        
        //Validate the response
        $response=$this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests deleting a snippet through the api
     */
    public function testDeleteSnippet() {
        $this->objFromFixture('Member', 'noaccess')->login();
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        
        //Test deleting a snippet with a user with no api access
        $response=$this->getAMFResponse('Snippets.deleteSnippet', array(
                                                                        'id'=>$snippet->ID
                                                                    ));
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been permission denied');
        
        
        
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test deleting a snippet with a user with api access
        $response=$this->getAMFResponse('Snippets.deleteSnippet', array(
                                                                        'id'=>$snippet->ID
                                                                    ));
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests removing a snippet from a package
     */
    public function testRemoveSnippetFromPackage() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        $package=$this->objFromFixture('SnippetPackage', 'package1');
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        
        //Test deleting a snippet with a user with api access
        $response=$this->getAMFResponse('Snippets.packageRemoveSnippet', array(
                                                                                'packageID'=>$package->ID,
                                                                                'snippetID'=>$snippet->ID
                                                                            ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Verify that the package no longer contains the snippet
        $this->assertEquals(0, $package->Snippets()->filter('ID', $snippet->ID)->count(), 'Snippet was not removed from the package');
    }
    
    /**
     * Tests adding a snippet to a package
     */
    public function testAddSnippetToPackage() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        $package=$this->objFromFixture('SnippetPackage', 'package1');
        $snippet=$this->objFromFixture('Snippet', 'snippet3');
        
        
        //Test deleting a snippet with a user with api access
        $response=$this->getAMFResponse('Snippets.addSnippetToPackage', array(
                                                                            'packageID'=>$package->ID,
                                                                            'snippetID'=>$snippet->ID
                                                                        ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Verify that the package now contains the snippet
        $this->assertGreaterThan(0, $package->Snippets()->filter('ID', $snippet->ID)->count(), 'Snippet was not added to the package');
    }
    
    /**
     * Tests creating a package
     */
    public function testCreatePackage() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test creating a package with a user with api access
        $response=$this->getAMFResponse('Snippets.createPackage', array(
                                                                        'title'=>'API Package'
                                                                    ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests saving a package
     */
    public function testSavingPackage() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test saving a package with a user with api access
        $response=$this->getAMFResponse('Snippets.savePackage', array(
                                                                    'packageID'=>$this->objFromFixture('SnippetPackage', 'package1')->ID,
                                                                    'title'=>'API Package'
                                                                ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests deleting a package
     */
    public function testDeletePackage() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test deleting a package with a user with api access
        $response=$this->getAMFResponse('Snippets.deletePackage', array(
                                                                        'id'=>$this->objFromFixture('SnippetPackage', 'package1')->ID
                                                                    ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests creating a folder, as well as checks to see that the duplicate detection is working and that detection for different languages is also working
     */
    public function testCreatingFolder() {
        $this->objFromFixture('Member', 'apiuser')->login();
        $languageID=SnippetLanguage::get()->filter('Name', 'CSS')->first()->ID;
        
        
        //Test creating a folder
        $response=$this->getAMFResponse('Snippets.newFolder', array(
                                                                    'name'=>'API Folder',
                                                                    'languageID'=>$languageID,
                                                                    'parentID'=>0
                                                                ));
        
        
        //Validate the response
        print $response['message'];
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a duplicate folder
        $response=$this->getAMFResponse('Snippets.newFolder', array(
                                                                    'name'=>'API Folder',
                                                                    'languageID'=>$languageID,
                                                                    'parentID'=>0
                                                                ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBank.FOLDER_EXISTS', '_A folder already exists with that name'), $response['message'], 'Response message should have been that there is a duplicate');
        
        
        //Test creating a folder under a different language
        $response=$this->getAMFResponse('Snippets.newFolder', array(
                                                                    'name'=>'API Folder 2',
                                                                    'languageID'=>$languageID,
                                                                    'parentID'=>$this->objFromFixture('SnippetFolder', 'folder1')->ID
                                                                ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.FOLDER_NOT_LANGUAGE', '_Folder is not in the same language as the snippet'), $response['message'], 'Response message should have been that the parent folder is in a different language');
    }
    
    /**
     * Tests renaming a folder, also tests to ensure that the duplicate checking is working
     */
    public function testRenameFolder() {
        $this->objFromFixture('Member', 'apiuser')->login();
        

        //Test renaming a folder
        $response=$this->getAMFResponse('Snippets.renameFolder', array(
                                                                        'id'=>$this->objFromFixture('SnippetFolder', 'folder1')->ID,
                                                                        'name'=>'Lorem Ipsum'
                                                                    ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        

        //Test renaming a folder to a duplicate
        $response=$this->getAMFResponse('Snippets.renameFolder', array(
                                                                        'id'=>$this->objFromFixture('SnippetFolder', 'folder1')->ID,
                                                                        'name'=>'Test Folder 2'
                                                                    ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBank.FOLDER_EXISTS', '_A folder already exists with that name'), $response['message'], 'Response message should have been that there is a duplicate');
    }
    
    /**
     * Tests to see that if a folder is deleted the decendent folders are moved up to the language and that the snippets are also removed correctly
     */
    public function testDeleteFolder() {
        $this->objFromFixture('Member', 'apiuser')->login();
        

        //Test deleting a folder
        $response=$this->getAMFResponse('Snippets.deleteFolder', array(
                                                                        'id'=>$this->objFromFixture('SnippetFolder', 'folder2')->ID
                                                                    ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        //Check the folder id of the snippet
        $this->assertEquals(0, $this->objFromFixture('Snippet', 'snippet4')->FolderID, 'Folder id was not reset on the snippet');

        //Check the parent id of the folder
        $this->assertEquals(0, $this->objFromFixture('SnippetFolder', 'folder4')->ParentID, 'Parent id was not reset on the folder');
    }
    
    /**
     * Tests moving a snippet to a folder, then to one in another language
     */
    public function testMoveSnippet() {
        $this->objFromFixture('Member', 'apiuser')->login();
        
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        $folder=$this->objFromFixture('SnippetFolder', 'folder2');
        

        //Test moving the snippet to a folder
        $response=$this->getAMFResponse('Snippets.moveSnippet', array(
                                                                    'id'=>$snippet->ID,
                                                                    'folderID'=>$folder->ID
                                                                ));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        //Check to see if the snippet actually changed folders
        $this->assertEquals($folder->ID, $this->objFromFixture('Snippet', 'snippet1')->FolderID, 'Snippet did not actually move folders');
        
        
        //Test moving the snippet to a folder in a different language
        $folder=$this->objFromFixture('SnippetFolder', 'folder3');
        $response=$this->getAMFResponse('Snippets.moveSnippet', array(
                                                                    'id'=>$snippet->ID,
                                                                    'folderID'=>$folder->ID
                                                                ));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.FOLDER_NOT_LANGUAGE', '_Folder is not in the same language as the snippet'), $response['message'], 'Response message should have been that the parent folder is in a different language');
    }
    
    public function testSnippetSearch() {
        $this->objFromFixture('Member', 'apiuser')->login();
        

        //Test moving searching for a snippet
        $response=$this->getAMFResponse('Snippets.searchSnippets', array(
                                                                        'query'=>'PHP Test'
                                                                    ));
        
        //Verify we have a good response
        $this->assertNotEquals('EROR', $response['status']);
        
        
        $expectedSnippet=$this->objFromFixture('Snippet', 'snippet1');
        $found=false;
        if(is_array($response['data']) && count($response['data'])>0) {
            foreach($response['data'] as $language) {
                if(is_array($language['folders']) && count($language['folders'])>0) {
                    foreach($language['folders'] as $folder) {
                        if(is_array($folder['snippets']) && count($folder['snippets'])>0) {
                            foreach($folder['snippets'] as $snippet) {
                                if($snippet->id==$expectedSnippet->ID) {
                                    $found=true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        //Verify that the expected snippet was found in the results
        $this->assertEquals(true, $found, 'Expected snippet not found in the result set');
    }
    
    /**
     * Generates a fake request object to be passed to the api class
     * @param {array} $data Associative array of data to be converted into a request object
     * @return {stdObject} Standard php object to be sent to the client
     */
    protected function arrayToObject($data) {
        $request=new stdClass();
        
        foreach($data as $prop=>$value) {
            $request->$prop=$value;
        }
        
        return $request;
    }
    
    /**
     * Handles passing a request through the amf client
     * @param {string} $servicePath Service path i.e ServerController.connect
     * @param {object|array} $data Data to be sent with the request should be an array or an object
     * @return {array} Server response
     */
    protected function getAMFResponse($servicePath, $data=null) {
        require_once 'Zend/Amf/Request.php';
        require_once 'Zend/Amf/Constants.php';
        require_once 'Zend/Amf/Value/MessageBody.php';
        require_once 'Zend/Amf/Value/Messaging/RemotingMessage.php';
        require_once 'Zend/Amf/Value/Messaging/ErrorMessage.php';
        
        
        if($data) {
            if(is_array($data)) {
                $data=$this->arrayToObject($data);
            }else if(!is_object($data)) {
                user_error('$data is not an array or object', E_USER_ERROR);
            }
        }
        
        
        //Find the method and service
        $service=explode('.', $servicePath);
        $method=array_pop($service);
        $service=implode('.', $service);
        
        
        //Build the message
        $message=new Zend_Amf_Value_Messaging_RemotingMessage();
        $message->parameters=$data;
        $message->operation=$method;
        $message->source=$service;
        
        
        //Build the message body
        $body=new Zend_Amf_Value_MessageBody($servicePath, '/1', array($data));
        
        
        //Build the AMF Request
        $request=new Zend_Amf_Request();
        $request->addAmfBody($body);
        $request->setObjectEncoding(Zend_Amf_Constants::AMF3_OBJECT_ENCODING);
        
        
        //Init the client api
        $amfClient=new CodeBank_ClientAPI();
        $amfClient->setTestRequest($request);
        
        //Capture the response as an amf input stream
        ob_start();
        $response=$amfClient->index();
        ob_end_clean();
        
        
        //Get the amf bodies
        $bodies=$response->getAmfBodies();
        if(count($bodies)>0) {
            $body=$bodies[0]->getData();
            if($body instanceof Zend_Amf_Value_Messaging_ErrorMessage) {
                $this->fail('AMF Server returned an error: '. $body->faultString."\n\n".$body->faultDetail);
                
                return false;
            }
            
            return $body;
        }
        
        return false;
    }
}
?>