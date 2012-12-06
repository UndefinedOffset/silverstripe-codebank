<?php
class Snippet extends DataObject {
    public static $db=array(
                            'Title'=>'Varchar(300)',
                            'Description'=>'Varchar(600)',
                            'Tags'=>'Varchar(400)'
                         );
    
    public static $has_one=array(
                                'Language'=>'SnippetLanguage',
                                'Creator'=>'Member',
                                'LastEditor'=>'Member'
                             );
    
    public static $has_many=array(
                                'Versions'=>'SnippetVersion'
                             );
    
    public static $belongs_many_many=array(
                                            'Packages'=>'SnippetPackage'
                                        );
    
    public static $extensions=array(
                                    'SnippetHierarchy',
                                    "FulltextSearchable('Title,Description,Tags')"
                                );
    
    public static $default_sort='Title, ID';
    
    public static $create_table_options=array(
                                    		'MySQLDatabase'=>'ENGINE=MyISAM'
                                    	);
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $fields=new FieldList(
                            new TabSet('Root',
                                new Tab('Main', _t('Snippet.MAIN', '_Main'),
                                    new DropdownField('LanguageID', _t('Snippet.LANGUAGE', '_Language'), SnippetLanguage::get()->map('ID', 'Title'), null, null, '---'),
                                    new TextField('Title', _t('Snippet.TITLE', '_Title'), null, 300),
                                    TextareaField::create('Description', _t('Snippet.DESCRIPTION', '_Description'))->setRows(5),
                                    TextareaField::create('Text', _t('Snippet.CODE', '_Code'), $this->getSnippetText())->setRows(30)->addExtraClass('codeBankFullWidth')->addExtraClass('stacked'),
                                    TextareaField::create('Tags', _t('Snippet.TAGS', '_Tags (comma separate)'))->setRows(2)
                                ),
                                new Tab('Package', _t('Snippet.PACKAGES', '_Packages'),
                                    new PackageViewField('Packages', _t('Snippet.PACKAGES', '_Packages'), $this->Packages(), $this->ID)
                                )
                            )
                        );
        
        
        return $fields;
    }
    
    /**
     * Gets validator used in the cms
     * @return {RequiredFields} Required fields validator
     */
    public function getCMSValidator() {
        return new RequiredFields(
                                'LanguageID',
                                'Title',
                                'Description',
                                'Text'
                            );
    }
    
    /**
     * Sets the creator id for new snippets and sets the last editor id for existing snippets
     */
    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        
        if($this->ID==0) {
            $this->CreatorID=Member::currentUserID();
        }else {
            $this->LastEditorID=Member::currentUserID();
        }
    }
    
    /**
     * Creates the snippet version record after writing
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        //Write the snippet version record
        $version=new SnippetVersion();
        $version->Text=$this->Text;
        $version->ParentID=$this->ID;
        $version->write();
    }
    
    /**
     * Removes all version history for this snippet before deleting the snippet record
     */
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        DB::query('DELETE FROM SnippetVersion WHERE ParentID='.$this->ID);
    }
    
    /**
     * Gets the current version
     * @return {SnippetVersion} Current version of the snippet
     */
    public function getCurrentVersion() {
        return $this->Versions()->First();
    }
    
    /**
     * Gets the id from the latest snippet version
     * @return {string} Snippet text
     */
    public function getCurrentVersionID() {
        $version=$this->CurrentVersion;
        if($version) {
            return $version->ID;
        }
    }
    
    /**
     * Gets the text from the latest snippet version
     * @return {string} Snippet text
     */
    public function getSnippetText() {
        $version=$this->CurrentVersion;
        if($version) {
            return $version->Text;
        }
    }
    
    /**
     * Gets the version by its id
     * @param {int} $id Version to fetch
     * @return {SnippetVersion} Snippet Version record
     */
    public function Version($id) {
        return $this->Versions()->byID($id);
    }
    
    /**
     * Gets the summary fields used in gridfield
     * @return {array} Array of field's mapped to labels
     */
    public function summaryFields() {
        return array(
                    'Language.Name'=>_t('Snippet.LANGUAGE', '_Language'),
                    'Title'=>_t('Snippet.TITLE', '_Title')
                );
    }
	
	/**
	 * Returns two <span> html DOM elements, an empty <span> with the class 'jstree-pageicon' in front, following by a <span> wrapping around its Title.
	 * @return string a html string ready to be directly used in a template
	 */
	public function getTreeTitle() {
		$treeTitle = sprintf(
			"<span class=\"jstree-pageicon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->Title))
		);
		
		return $treeTitle;
	}
	
	/**
	 * Workaround to get snippets to display in tree, does nothing
	 */
	public function Snippets() {}
	
	/**
	 * Workaround to get snippets to display
	 * @return {bool} Returns boolean true
	 */
    public function hasSnippets() {
        return true;
    }
    
	/**
	 * Return the CSS classes to apply to this node in the CMS tree
	 * @return {string} Classes used in the cms tree
	 */
	public function CMSTreeClasses() {
		$classes=sprintf('class-%s', $this->class);
		
		$classes.=$this->markingClasses();

		return $classes;
	}
}
?>