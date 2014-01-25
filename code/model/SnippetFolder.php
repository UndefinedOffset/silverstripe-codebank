<?php
class SnippetFolder extends DataObject {
    private static $db=array(
                            'Name'=>'Varchar(150)'
                         );
    
    private static $has_one=array(
                                'Language'=>'SnippetLanguage',
                                'Parent'=>'SnippetFolder'
                             );
    
    private static $has_many=array(
                                'Snippets'=>'Snippet.Folder',
                                'Folders'=>'SnippetFolder.Parent'
                             );
    
    private static $extensions=array(
                                    'SnippetHierarchy'
                                );
    
    private static $allowed_children=array(
                                        'Snippet',
                                        'SnippetFolder'
                                    );
    
    private static $default_child='Snippet';
    private static $default_parent='SnippetLanguage';
    
    
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
        DB::query('UPDATE "Snippet" SET "FolderID"='.$this->ParentID.' WHERE "FolderID"='.$this->ID);
        
        //Remove all Snippet Folders from this folder
        DB::query('UPDATE "SnippetFolder" SET "ParentID"='.$this->ParentID.' WHERE "ParentID"='.$this->ID);
    }
    
    /**
     * Returns an array of the class names of classes that are allowed to be children of this class.
     * @return {array} Array of children
     */
    public function allowedChildren() {
        $allowedChildren = array();
        $candidates = $this->stat('allowed_children');
        if($candidates && $candidates != "none") {
            foreach($candidates as $candidate) {
                // If a classname is prefixed by "*", such as "*Page", then only that
                // class is allowed - no subclasses. Otherwise, the class and all its subclasses are allowed.
                if(substr($candidate,0,1) == '*') {
                    $allowedChildren[] = substr($candidate,1);
                } else {
                    $subclasses = ClassInfo::subclassesFor($candidate);
                    foreach($subclasses as $subclass) {
                        $allowedChildren[] = $subclass;
                    }
                }
            }
        }
        
        return $allowedChildren;
    }
    
    /**
     * Returns the default child for this class
     * @return {string} Class name of the default child
     */
    public function default_child() {
        return $this->stat('default_child');
    }
}
?>