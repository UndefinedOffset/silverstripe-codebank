<?php
class SnippetFolder extends DataObject {
    public static $db=array(
                            'Name'=>'Varchar(150)'
                         );
    
    public static $has_one=array(
                                'Language'=>'SnippetLanguage',
                                'Parent'=>'SnippetFolder'
                             );
    
    public static $has_many=array(
                                'Snippets'=>'Snippet.Folder',
                                'Folders'=>'SnippetFolder.Parent'
                             );
    
    public static $extensions=array(
                                    'SnippetHierarchy'
                                );
    
    public static $allowed_children=array(
                                        'Snippet',
                                        'SnippetFolder'
                                    );
    
    public static $default_child='Snippet';
    public static $default_parent='SnippetLanguage';
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        return new FieldList(
                            new TextField('Name', _t('SnippetFolder.NAME', '_Name'), null, 150)
                        );
    }
    
    /**
     * Determins if the folder has snippets
     * return {bool} Returns true so that folders always show
     */
    public function hasSnippets() {
        return true;
    }
	
	/**
	 * Returns two <span> html DOM elements, an empty <span> with the class 'jstree-pageicon' in front, following by a <span> wrapping around its Title.
	 * @return {string} a html string ready to be directly used in a template
	 */
	public function getTreeTitle() {
		$treeTitle = sprintf(
			"<span class=\"jstree-pageicon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->Title))
		);
		
		return $treeTitle;
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
     * Removes all snippets from the folder before deleting
     */
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        //Remove all Snippets from this folder
        DB::query('UPDATE "Snippet" SET "FolderID"=0 WHERE "FolderID"='.$this->ID);
        
        //Remove all Snippet Folders from this folder
        DB::query('UPDATE "SnippetFolder" SET "ParentID"=0 WHERE "ParentID"='.$this->ID);
    }
}
?>