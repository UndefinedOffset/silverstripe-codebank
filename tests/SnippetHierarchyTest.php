<?php
class SnippetHierarchyTest extends SapphireTest
{
    public static $fixture_file='SnippetTest.yml';
    
    /**
     * Forces the snippet languages to be populated on setup
     */
    public function setUp()
    {
        parent::setUp();
        
        //Populate default languages
        singleton('SnippetLanguage')->requireDefaultRecords();
    }
    
    /**
     * Tests to verify that the children of the language are what is expected
     */
    public function testLanguageChildren()
    {
        $language=SnippetLanguage::get()->filter('Name', 'PHP')->first();
        
        
        //Test to see if the first child of the language is a folder
        $this->assertInstanceOf('SnippetFolder', $language->Children()->offsetGet(0), 'There should be a snippet folder as the first child of the language');
        
        
        //Test to see if the first child of the language is "Test Folder"
        $this->assertEquals('Test Folder', $language->Children()->offsetGet(0)->Title, '"Test Folder" should be the first child of the language');
        
        
        //Test to see if the second child of the language is a snippet
        $this->assertInstanceOf('Snippet', $language->Children()->offsetGet(2), 'There should be a snippet as the third child of the language');
        
        
        //Test to see if the second child of the language is "PHP Test 2"
        $this->assertEquals('PHP Test 2', $language->Children()->offsetGet(2)->Title, '"PHP Test 2" should be the third child of the language');
        
        
        //Test to ensure there are only 3 children the second snippet "PHP Test" should be a child of the folder
        $this->assertNull($language->Children()->offsetGet(3), 'There should be no more children of this language');
    }
    
    /**
     * Tests to verify that the children of the folder are what is expected
     */
    public function testFolderChildren()
    {
        $folder=$this->objFromFixture('SnippetFolder', 'folder1');
        
        
        //Test to see if the first child is a snippet
        $this->assertInstanceOf('Snippet', $folder->Children()->offsetGet(0));
        
        
        //Test to see if the second child of the language is "PHP Test"
        $this->assertEquals('PHP Test', $folder->Children()->offsetGet(0)->Title, '"PHP Test" should be the second child of the language');
        
        
        //Test to ensure there are only 1 child the third snippet
        $this->assertNull($folder->Children()->offsetGet(1), 'There should be no more children of this folder');
    }
    
    /**
     * Tests to see that the language only contains three children, the 4th child should be considered a child of the folder
     */
    public function testLanguageChildrenCount()
    {
        $language=SnippetLanguage::get()->filter('Name', 'PHP')->first();
        
        $this->assertEquals(3, $language->Children()->count(), 'There should be 3 children of the language');
    }
    
    /**
     * Tests to see that the folder only contains one child
     */
    public function testFolderChildrenCount()
    {
        $folder=$this->objFromFixture('SnippetFolder', 'folder1');
        
        $this->assertEquals(1, $folder->Children()->count(), 'There should be 1 child of the folder');
    }
}
