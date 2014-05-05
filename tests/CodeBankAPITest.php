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
        $response=$this->getURLContents(Controller::join_links(Director::absoluteBaseURL(), 'code-bank-api'));
        
        
        //Test that the content type is correct
        $this->assertEquals('application/x-amf', $response['content-type']);
        
        
        //Test that we recieved the default endpoint notice, we base64 encode it because it can contain binary data
        $this->assertEquals('PHA+WmVuZCBBbWYgRW5kcG9pbnQ8L3A+AAAAAAAA', base64_encode($response['content']), 'API response was not what was expected, this could indicate an error has occured or there is a problem in the response');
    }
    
    /**
     * Tests to see if the login method in the session manager actually login a user
     */
    public function testLogin() {
        $apiClass=new CodeBankSessionManager();
        $response=$apiClass->login($this->fakeRequest(array('user'=>'admin', 'pass'=>'admin')));
        
        
        //Check the response to ensure it is HELO
        $this->assertEquals('HELO', $response['status'], 'API Response was not HELO');
        
        
        //Verify the user was logged in
        $this->assertGreaterThan(0, Member::currentUserID(), 'Member was not logged in');
    }
    
    /**
     * Test to see if the logout method in the session manager actually logs the user out
     */
    public function testLogout() {
        $apiClass=new CodeBankSessionManager();
        
        //Login the admin user
        $this->objFromFixture('Member', 'admin')->login();
        
        
        //Verify the user was logged in
        $this->assertGreaterThan(0, Member::currentUserID(), 'Member was not logged in');
        
        
        //Attempt to logout
        $apiClass->logout();
        
        
        //Verify the user was logged out
        $this->assertEquals(0, Member::currentUserID(), 'Member was not logged out');
    }
    
    /**
     * Tests generic api access to see if user control is enforced correctly
     */
    public function testAPIAccess() {
        $apiClass=new CodeBankSnippets();
        
        //Login the noaccess account
        $this->objFromFixture('Member', 'noaccess')->login();
        
        //Try to get the languages
        $response=$apiClass->getLanguages();
        
        
        //Test response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        //Login the apiuser account
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Try to get the languages
        $response=$apiClass->getLanguages();
        
        
        //Test response
        $this->assertNotEquals('EROR', $response['status'], 'Response status should not have been EROR');
    }
    
    /**
     * Tests to see if a member can update their preferences
     */
    public function testMemberSavePreferences() {
        $apiClass=new CodeBankServer();
        $member=$this->objFromFixture('Member', 'apiuser');
        $member->login();
        
        
        //Negate the current value
        $heartBeat=($member->UseHeartbeat ? 0:1);
        
        
        
        //Fetch the message from the api
        $response=$apiClass->savePreferences($this->fakeRequest(array('heartbeat'=>$heartBeat)));
        
        
        //Re-fetch the apiuser account
        $member=$this->objFromFixture('Member', 'apiuser');
        
        
        //Test the response to see if it matched
        $this->assertEquals($heartBeat, $member->UseHeartbeat, 'Heartbeat preference was not updated');
    }
    
    /**
     * Checks to see if a non-admin user can access admin specific sections
     */
    public function testAdminPermissionFailure() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'apiuser')->login(); //Login apiuser
        
        
        //Try fetching users list with a user with just CODE_BANK_API permissions
        $response=$apiClass->getUsersList();
        
        
        //Verify the response was an error
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        $this->objFromFixture('Member', 'admin')->login(); //Login admin user
        
        //Try fetching users list with a user with ADMIN permissions
        $response=$apiClass->getUsersList();
        
        
        //Verify the response was an error
        $this->assertNotEquals('EROR', $response['status'], 'Response status should have not been EROR');
    }
    
    /**
     * Tests to see if admin's can delete a user, also checks to see that a normal user cannot
     */
    public function testAdminDeleteUser() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'apiuser')->login(); //Login apiuser
        
        
        //Try deleting with a user with just CODE_BANK_API permissions
        $response=$apiClass->deleteUser($this->fakeRequest(array('id'=>3)));
        
        
        //Verify the response was an error
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied'), $response['message'], 'Response message should have been Permission Denied');
        
        
        //Verify the member was not deleted
        $this->assertGreaterThan(0, Member::get()->filter('Email', 'apiuser')->count(), 'Member was deleted');
        
        
        
        $this->objFromFixture('Member', 'admin')->login(); //Login admin user
        
        //Try deleting with a user with ADMIN permissions
        $response=$apiClass->deleteUser($this->fakeRequest(array('id'=>3)));
        
        
        //Verify the response was an error
        $this->assertEquals('HELO', $response['status'], 'Response status should have not been EROR');
        
        
        //Verify the member was deleted
        $this->assertEquals(0, Member::get()->filter('Email', 'noaccess')->count(), 'Member was not deleted');
    }
    
    /**
     * Test the change password system, first checks to ensure that a user changing their password must provide the current password, then checks to see if an admin can change another user's password
     */
    public function testChangePassword() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test incorrect current password
        $response=$apiClass->changeUserPassword($this->fakeRequest(array('currPassword'=>'nimda', 'password'=>'admin1')));
        
        
        //Verify the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.CURRENT_PASSWORD_MATCH', '_Current password does not match'), $response['message'], 'Response message should have been that the current password does not match');
        
        
        //Test chancing with the correct current password
        $response=$apiClass->changeUserPassword($this->fakeRequest(array('currPassword'=>'admin', 'password'=>'admin1')));
        
        
        //Verify the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test chancing with the correct current password
        $response=$apiClass->changeUserPassword($this->fakeRequest(array('id'=>$this->objFromFixture('Member', 'noaccess')->ID, 'password'=>'admin1')));
        
        
        //Verify the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests to see if an admin can create a user, also checks to see if duplicate detection is functioning correctly
     */
    public function testCreateUser() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test creating a user
        $response=$apiClass->createUser($this->fakeRequest(array(
                                                                'firstname'=>'Joe',
                                                                'surname'=>'User',
                                                                'username'=>'joe@example.com',
                                                                'Password'=>'joespass'
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a user who already exists in the database
        $response=$apiClass->createUser($this->fakeRequest(array(
                                                                'firstname'=>'Joe',
                                                                'surname'=>'User',
                                                                'username'=>'joe@example.com',
                                                                'Password'=>'joespass'
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.EMAIL_EXISTS', '_An account with that email already exists'), $response['message'], 'Response message should have been that a user already exists with that email');
    }
    
    /**
     * Tests to see if the language creation is working correctly
     */
    public function testCreateLanguage() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Test creating a language
        $response=$apiClass->createLanguage($this->fakeRequest(array(
                                                                'language'=>'API Language',
                                                                'fileExtension'=>'api'
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a duplicate language
        $response=$apiClass->createLanguage($this->fakeRequest(array(
                                                                'language'=>'API Language',
                                                                'fileExtension'=>'api'
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists'), $response['message'], 'Response message should have been that the language already exists');
    }
    
    /**
     * Tests to see if the language deletion is working correctly
     */
    public function testDeleteLanguage() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Try deleting a default language
        $response=$apiClass->deleteLanguage($this->fakeRequest(array('id'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID)));
        
        
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
        $response=$apiClass->deleteLanguage($this->fakeRequest(array('id'=>$lang->ID)));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_DELETE_ERROR', '_Language cannot be deleted, it is either not a user language or has snippets attached to it'), $response['message'], 'Response message should have been that the language is not a user language or has children');
        
        
        //Delete the snippet and try again
        $snippet->delete();
        $response=$apiClass->deleteLanguage($this->fakeRequest(array('id'=>$lang->ID)));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
    }
    
    /**
     * Tests to see if the language editing is working correctly
     */
    public function testEditLanguage() {
        $apiClass=new CodeBankAdministration();
        $this->objFromFixture('Member', 'admin')->login(); //Login admin
        
        
        //Create a test language
        $lang=new SnippetLanguage();
        $lang->Name='API Language';
        $lang->FileExtension='api';
        $lang->UserLanguage=true;
        $lang->write();
        
        
        //Test creating a language
        $response=$apiClass->editLanguage($this->fakeRequest(array(
                                                                'id'=>$lang->ID,
                                                                'language'=>'API Language 2',
                                                                'fileExtension'=>'api2',
                                                                'hidden'=>false
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        //Ensure the language actually was edited to what it should have been
        $this->assertGreaterThan(0, SnippetLanguage::get()->filter('Name', 'API Language 2')->count(), 'Language was not changed as expected');
        
        
        
        //Test editing to a duplicate language
        $response=$apiClass->editLanguage($this->fakeRequest(array(
                                                                'id'=>$lang->ID,
                                                                'language'=>'PHP',
                                                                'fileExtension'=>'api',
                                                                'hidden'=>false
                                                            )));
        
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists'), $response['message'], 'Response message should have been that the language already exists');
    }
    
    /**
     * Tests creating a snippet from the api
     */
    public function testNewSnippet() {
        $apiClass=new CodeBankSnippets();
        $this->objFromFixture('Member', 'apiuser')->login();
        
        
        //Test creating a snippet
        $response=$apiClass->newSnippet($this->fakeRequest(array(
                                                                'title'=>'API Snippet',
                                                                'description'=>'This is a snippet created from the api',
                                                                'code'=>'<?php print "hello api"; ?>',
                                                                'tags'=>'api,test,snippet,php',
                                                                'language'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID,
                                                                'packageID'=>0,
                                                                'folderID'=>0
                                                            )));
        
        //Validate the response
        $response=$this->assertEquals('HELO', $response['status'], 'Response status should have been HELO');
        
        
        //Test creating a snippet, that belongs to a folder in another language
        $response=$apiClass->newSnippet($this->fakeRequest(array(
                                                                'title'=>'API Snippet',
                                                                'description'=>'This is a snippet created from the api',
                                                                'code'=>'<?php print "hello api"; ?>',
                                                                'tags'=>'api,test,snippet,php',
                                                                'language'=>SnippetLanguage::get()->filter('Name', 'PHP')->first()->ID,
                                                                'packageID'=>0,
                                                                'folderID'=>$this->objFromFixture('SnippetFolder', 'folder3')->ID
                                                            )));
        
        //Validate the response
        $this->assertEquals('EROR', $response['status'], 'Response status should have been EROR');
        $this->assertEquals(_t('CodeBankAPI.FOLDER_NOT_LANGUAGE', '_Folder is not in the same language as the snippet'), $response['message'], 'Response massage should have been that the folder is not in the same language');
    }
    
    /**
     * Loads the contents of a url, with sensitivity for allow_url_fopen being off
     * @param {string} $url URL to load
     * @return {mixed} Returns the contents of the loaded url or false
     */
    private function getURLContents($url) {
        if(ini_get('allow_url_fopen')==true) {
            $contents=@file_get_contents($url);
            $responseHeaders=$this->parseHeaders($http_response_header);
            $contents=array('content'=>$contents, 'content-type'=>$responseHeaders['content-type']);
        }else if(function_exists('curl_init') && $ch = curl_init()) {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $contents=array('content'=>curl_exec($ch), 'content-type'=>curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
            curl_close($ch);
        }else {
            user_error('Allow URL Fopen appears to be off and could not fallback to curl', E_USER_ERROR);
        }
        
        return $contents;
    }
    
    /**
     * Parses the headers contained in $http_response_header into an associative array
     * @param {array} $headers Headers contained in $http_response_header
     * @return {array} Associative array representing the headers
     */
    private function parseHeaders($headers) {
        $result=array();
        
        foreach($headers as $header) {
            $tmp=explode(':', $header);
            if(count($tmp)>1) {
                $name=strtolower($tmp[0]);
                unset($tmp[0]);
                
                $result[$name]=trim(implode(':', $tmp));
            }
        }
        
        return $result;
    }
    
    
    /**
     * Generates a fake request object to be passed to the api class
     * @param {array} $data Associative array of data to be converted into a request object
     * @return {stdObject} Standard php object to be sent to the client
     */
    private function fakeRequest($data) {
        $request=new stdClass();
        
        foreach($data as $prop=>$value) {
            $request->$prop=$value;
        }
        
        return $request;
    }
}
?>