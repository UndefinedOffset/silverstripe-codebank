<?php
class SnippetTest extends SapphireTest
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
     * Tests to see if the current version functionality is actually returning the correct version
     */
    public function testCurrentVersion()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        $version=SnippetVersion::get()->filter('Created', '2014-04-27 15:46:00')->first();
        
        
        //Test that we have a current version
        $this->assertInstanceOf('SnippetVersion', $snippet->CurrentVersion, 'Snippet\'s current version is not present');
        $this->assertGreaterThan(0, $snippet->CurrentVersion->ID, 'Snippet\'s current version is not present');
        
        
        //Test that the version is correctly associated with the parent
        $this->assertEquals($version->ParentID, $snippet->ID, 'Test error, version is not lining up with it\'s snippet');
        
        
        //Test that the current version is the expected version
        $this->assertEquals($version->ID, $snippet->CurrentVersion->ID, 'Snippet\'s current version is not matching what should be the head version');
    }
    
    /**
     * Tests to ensure that the snippet text is what it should be at the current version
     */
    public function testSnippetText()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        $this->assertEquals("<?php print 'Hello World'; ?>", $snippet->SnippetText, 'Snippet text is not matching the head version');
    }
    
    /**
     * Tests to see if a version is created when we update the snippet's text
     */
    public function testVersionCreation()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        //Fetch the current version id
        $priorVersionID=$snippet->CurrentVersionID;
        
        //Change the snippet and write
        $snippet->Text="<?php print 'This is a test'; ?>";
        $snippet->write();
        
        
        //Check to see if a new version was created
        $this->assertGreaterThan($priorVersionID, $snippet->CurrentVersionID, 'A new snippet version was not created');
        
        
        //Check to see if the version text matches what we set it to
        $this->assertEquals("<?php print 'This is a test'; ?>", $snippet->SnippetText, 'Current version\'s text does not match what was set');
    }
    
    /**
     * Tests to see if the proper brush name is loaded for the snippet
     */
    public function testBrushName()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        $this->assertEquals('shBrushPhp', $snippet->BrushName, 'Snippet brush name should be shBrushPhp');
    }

    /**
     * Tests to see if the folder is reset after changing to another language id
     */
    public function testFolderResetOnNewLanguage()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        
        //Ensure the snippet's folder id has been populated
        $this->assertGreaterThan(0, $snippet->FolderID, 'Snippet folder ID is already zero');
        
        
        $snippet->LanguageID=9;
        $snippet->write();
        
        
        //Reload the snippet to ensure we have the database's version
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        
        
        //Ensure the snippets folder id has been reset
        $this->assertEquals(0, $snippet->FolderID);
    }

    /**
     * Tests to see if the versions for a snippet are actually removed when the snippet is deleted using a call to DataObject::delete()
     */
    public function testVersionCleanupOnDelete()
    {
        $snippet=$this->objFromFixture('Snippet', 'snippet1');
        $snippetID=$snippet->ID;
        
        
        //Verify that there are snippet versions for the located snippet
        $this->assertGreaterThan(0, SnippetVersion::get()->filter('ParentID', $snippetID)->count(), 'Snippet versions cannot be found');
        
        
        //Delete the snippet
        $snippet->delete();
        
        
        //Test to see if the versions have been properly removed
        $this->assertEquals(0, SnippetVersion::get()->filter('ParentID', $snippetID)->count(), 'Snippet versions were found');
    }
}
