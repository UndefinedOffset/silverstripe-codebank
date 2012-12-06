<?php
class SnippetPackage extends DataObject {
    public static $db=array(
                            'Title'=>'Varchar(300)'
                         );
    
    public static $many_many=array(
                                    'Snippets'=>'Snippet'
                                 );
    
    public static $extensions=array(
                                    'SnippetPackageHierachy'
                                );
    
    public static $default_sort='Title';
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $fields=new FieldList(
                            new TabSet('Root',
                                            new Tab('Main', _t('SnippetPackage.MAIN', '_Main'),
                                                            new TextField('Title', _t('SnippetPackage.TITLE', '_Title'), null, 300),
                                                            $packageGrid=new GridField('Snippets', _t('SnippetPackage.PACKAGE_SNIPPETS', '_Package Snippets'), $this->Snippets(), GridFieldConfig_RelationEditor::create(10))
                                                        )
                                        )
                        );
        
        if($this->ID==0) {
            $fields->replaceField('Snippets', new LabelField('Snippets', _t('SnippetPackage.SNIPPETS_AFTER_FIRST_SAVE', '_Snippets can be added after saving for the first time')));
        }else {
            $packageGrid->getConfig()->removeComponentsByType('GridFieldEditButton')
                                    ->removeComponentsByType('GridFieldAddNewButton')
                                    ->addComponent(new PackageViewButton());
        }
        
        return $fields;
    }
    
    /**
     * Gets validator used in the cms
     * @return {RequiredFields} Required fields validator
     */
    public function getCMSValidator() {
        return new RequiredFields(
                                'Title'
                            );
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
	 * Always return true
	 * @return {bool} Return true
	 */
	public function hasSnippets() {
	    return true;
	}
}
?>