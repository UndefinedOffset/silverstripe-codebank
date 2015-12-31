<?php
class SnippetLanguageTest extends SapphireTest
{
    public static $fixture_file='SnippetLanguageTest.yml';
    
    public function setUp()
    {
        parent::setUp();
        
        
        //Remove the extra_languages config key from code bank, ensures we don't have bad config languages defined elsewhere
        Config::inst()->remove('CodeBank', 'extra_languages');


        //Populate default languages
        singleton('SnippetLanguage')->requireDefaultRecords();
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        //Remove the extra_languages config key from code bank, cleans up after running the unit tests
        Config::inst()->remove('CodeBank', 'extra_languages');
    }
    
    /**
     * Checks to see if the user can't delete a default language
     */
    public function testCantDeleteDefaultLanguage()
    {
        $language=SnippetLanguage::get()->filter('Name', 'PHP')->first();
        
        
        $this->assertEquals(false, $language->canDelete(), 'User can delete a default language');
    }
    
    /**
     * Tests to see if a user can delete a user language without children
     */
    public function testCanDeleteUserLanguage()
    {
        $language=$this->objFromFixture('SnippetLanguage', 'language2');
        
        
        $this->assertEquals(true, $language->canDelete(), 'User can\'t delete a user language');
    }
    
    /**
     * Tests to see that a user can't delete a user language with snippets
     */
    public function testCantDeleteUserLanguageWithSnippet()
    {
        $language=$this->objFromFixture('SnippetLanguage', 'language1');
        
        
        $this->assertEquals(false, $language->canDelete(), 'User can\'t delete a user language with a snippet');
    }
    
    /**
     * Tests to see that a user can't delete a user language with folders
     */
    public function testCantDeleteUserLanguageWithFolder()
    {
        $language=$this->objFromFixture('SnippetLanguage', 'language3');
        
        
        $this->assertEquals(false, $language->canDelete(), 'User can delete a user language with a folder');
    }
    
    /**
     * Tests to see if a valid language can be added via the config layer
     */
    public function testValidCustomLanguage()
    {
        CodeBank::config()->extra_languages=array(array(
                                                'Name'=>'Test Language',
                                                'FileName'=>'tst',
                                                'HighlightCode'=>'tst',
                                                'Brush'=>'CodeBank/tests/files/testBrush.js'
                                            ));
        
        //Try populating the default records
        singleton('SnippetLanguage')->requireDefaultRecords();
        
        
        //Make sure it was added
        $this->assertEquals(1, SnippetLanguage::get()->filter('Name', 'Test Language')->count(), 'User language was not created');
    }
    
    /**
     * Tests to see if a language defined in the config missing components generates an error and is not added
     */
    public function testInvalidCustomLanguage()
    {
        CodeBank::config()->extra_languages=array(array(
                                                'Name'=>'Test Language 1',
                                                'HighlightCode'=>'tst1'
                                            ));
        
        $didExpectedError=false;
        try {
            //Try populating the default records
            singleton('SnippetLanguage')->requireDefaultRecords();
        } catch (PHPUnit_Framework_Error_Warning $e) {
            if (strpos($e->getMessage(), 'Invalid snippet user language found in config')!==false) {
                $didExpectedError=true;
            } else {
                throw $e; //Ensure the error is re-thrown, it doesn't match what we're expecting
            }
        }
        
        
        //Check to see if the error was thrown
        $this->assertEquals(true, $didExpectedError);
        
        
        //Make sure it was added
        $this->assertEquals(0, SnippetLanguage::get()->filter('Name', 'Test Language 1')->count(), 'Invalid language was created');
    }
    
    /**
     * Tests to see if a language defined in the config with a remote url generates an error and is not added
     */
    public function testCustomLanguageBrushInvalidRemotePath()
    {
        CodeBank::config()->extra_languages=array(array(
                                                'Name'=>'Test Language 2',
                                                'FileName'=>'tst',
                                                'HighlightCode'=>'tst2',
                                                'Brush'=>'http://www.example.com/testBrush.js'
                                            ));
        
        $didExpectedError=false;
        try {
            //Try populating the default records
            singleton('SnippetLanguage')->requireDefaultRecords();
        } catch (PHPUnit_Framework_Error_Warning $e) {
            if (strpos($e->getMessage(), 'Invalid snippet user language found in config')!==false) {
                $didExpectedError=true;
            } else {
                throw $e; //Ensure the error is re-thrown, it doesn't match what we're expecting
            }
        }
        
        
        //Check to see if the error was thrown
        $this->assertEquals(true, $didExpectedError);
        
        
        //Make sure it was added
        $this->assertEquals(0, SnippetLanguage::get()->filter('Name', 'Test Language 2')->count(), 'Invalid language was created');
    }
    
    /**
     * Tests to see if a language defined in the config with a absolute file path generates an error and is not added
     */
    public function testCustomLanguageBrushInvalidLocalPath()
    {
        CodeBank::config()->extra_languages=array(array(
                                                'Name'=>'Test Language 3',
                                                'FileName'=>'tst',
                                                'HighlightCode'=>'tst3',
                                                'Brush'=>'/testBrush.js'
                                            ));
        
        $didExpectedError=false;
        try {
            //Try populating the default records
            singleton('SnippetLanguage')->requireDefaultRecords();
        } catch (PHPUnit_Framework_Error_Warning $e) {
            if (strpos($e->getMessage(), 'Invalid snippet user language found in config')!==false) {
                $didExpectedError=true;
            } else {
                throw $e; //Ensure the error is re-thrown, it doesn't match what we're expecting
            }
        }
        
        
        //Check to see if the error was thrown
        $this->assertEquals(true, $didExpectedError);
        
        
        //Make sure it was added
        $this->assertEquals(0, SnippetLanguage::get()->filter('Name', 'Test Language 3')->count(), 'Invalid language was created');
    }
    
    /**
     * Tests to see if a language defined in the config with a brush that does not end in .js generates an error and is not added
     */
    public function testNonJSCustomLanguageBrush()
    {
        CodeBank::config()->extra_languages=array(array(
                                                'Name'=>'Test Language 4',
                                                'FileName'=>'tst',
                                                'HighlightCode'=>'tst4',
                                                'Brush'=>'CodeBank/tests/files/testBrush.lorem'
                                            ));
        
        $didExpectedError=false;
        try {
            //Try populating the default records
            singleton('SnippetLanguage')->requireDefaultRecords();
        } catch (PHPUnit_Framework_Error_Warning $e) {
            if (strpos($e->getMessage(), 'Invalid snippet user language found in config')!==false) {
                $didExpectedError=true;
            } else {
                throw $e; //Ensure the error is re-thrown, it doesn't match what we're expecting
            }
        }


        //Check to see if the error was thrown
        $this->assertEquals(true, $didExpectedError);
        
        
        //Make sure it was added
        $this->assertEquals(0, SnippetLanguage::get()->filter('Name', 'Test Language 4')->count(), 'Invalid language was created');
    }
}
