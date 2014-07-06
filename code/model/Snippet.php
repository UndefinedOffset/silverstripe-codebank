<?php
class Snippet extends DataObject {
    private static $db=array(
                            'Title'=>'Varchar(300)',
                            'Description'=>'Varchar(600)',
                            'Tags'=>'Varchar(400)'
                         );
    
    private static $has_one=array(
                                'Language'=>'SnippetLanguage',
                                'Creator'=>'Member',
                                'LastEditor'=>'Member',
                                'Package'=>'SnippetPackage',
                                'Folder'=>'SnippetFolder'
                             );
    
    private static $has_many=array(
                                'Versions'=>'SnippetVersion'
                             );
    
    private static $extensions=array(
                                    'SnippetHierarchy',
                                    "FulltextSearchable('Title,Description,Tags')"
                                );
    
    private static $default_sort='Title, ID';
    
    private static $create_table_options=array(
                                            'MySQLDatabase'=>'ENGINE=MyISAM'
                                        );
    
    private static $allowed_children=array();
    private static $default_child=null;
    
    
    /**
     * Checks to see if the member can view or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can view false otherwise
     */
    public function canView($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks to see if the member can edit or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can edit false otherwise
     */
    public function canEdit($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks to see if the member can delete or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can delete false otherwise
     */
    public function canDelete($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks to see if the member can create or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can create false otherwise
     */
    public function canCreate($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $fields=new FieldList(
                            new TabSet('Root',
                                new Tab('Main', _t('Snippet.MAIN', '_Main'),
                                    DropdownField::create('LanguageID', _t('Snippet.LANGUAGE', '_Language'), SnippetLanguage::get()->where('"SnippetLanguage"."Hidden"=0 OR "SnippetLanguage"."ID"='.($this->LanguageID ? $this->LanguageID:0))->map('ID', 'Title'))->setEmptyString('---'),
                                    new TextField('Title', _t('Snippet.TITLE', '_Title'), null, 300),
                                    TextareaField::create('Description', _t('Snippet.DESCRIPTION', '_Description'))->setRows(5),
                                    PackageSelectionField::create('PackageID', _t('Snippet.PACKAGE', '_Package'), SnippetPackage::get()->map('ID', 'Title'))->setEmptyString(_t('Snippet.NOT_IN_PACKAGE', '_Not Part of a Package')),
                                    TextareaField::create('Text', _t('Snippet.CODE', '_Code'), $this->getSnippetText())->setRows(30)->addExtraClass('codeBankFullWidth')->addExtraClass('stacked'),
                                    TextareaField::create('Tags', _t('Snippet.TAGS', '_Tags (comma separate)'))->setRows(2)
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
            
            //If the language is changing reset the folder id
            if($this->isChanged('LanguageID')) {
                $this->FolderID=0;
            }
        }
    }
    
    /**
     * Creates the snippet version record after writing
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        //Write the snippet version record
        if(!empty($this->Text)) {
            $version=new SnippetVersion();
            $version->Text=$this->Text;
            $version->ParentID=$this->ID;
            $version->write();
        }
    }
    
    /**
     * Removes all version history for this snippet before deleting the snippet record
     */
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        SnippetVersion::get()->filter('ParentID', $this->ID)->removeAll();
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
     * Gets the brush name
     * @return {string} Name of the file used for the syntax highlighter brush
     */
    public function getBrushName() {
        switch(strtolower($this->Language()->Name)) {
            case 'applescript':return 'shBrushAppleScript';
            case 'actionscript3':
            case 'as3':return 'shBrushAS3';
            case 'mxml':
            case 'flex':return 'shBrushFlex';
            case 'bash':
            case 'shell':return 'shBrushBash';
            case 'coldfusion':
            case 'cf':return 'shBrushColdFusion';
            case 'cpp':
            case 'c':return 'shBrushCpp';
            case 'c#':
            case 'c-sharp':
            case 'csharp':return 'shBrushCSharp';
            case 'css':return 'shBrushCss';
            case 'delphi':
            case 'pascal':return 'shBrushDelphi';
            case 'diff':
            case 'patch':
            case 'pas':return 'shBrushDiff';
            case 'erl':
            case 'erlang':return 'shBrushErlang';
            case 'groovy':return 'shBrushGroovy';
            case 'java':return 'shBrushJava';
            case 'jfx':
            case 'javafx':return 'shBrushJavaFX';
            case 'js':
            case 'jscript':
            case 'javascript':return 'shBrushJScript';
            case 'perl':
            case 'pl':return 'shBrushPerl';
            case 'php':return 'shBrushPhp';
            case 'text':
            case 'plain':return 'shBrushPlain';
            case 'py':
            case 'python':return 'shBrushPython';
            case 'ruby':
            case 'rails':
            case 'ror':
            case 'rb':return 'shBrushRuby';
            case 'sass':
            case 'scss':return 'shBrushSass';
            case 'scala':return 'shBrushScala';
            case 'ss':
            case 'silverstripe':return 'shBrushSilverStripe';
            case 'sql':return 'shBrushSql';
            case 'vb':
            case 'vbnet':return 'shBrushVb';
            case 'xml':
            case 'xhtml':
            case 'xslt':
            case 'html':return 'shBrushXml';
            case 'yml':
            case 'yaml':return 'shBrushYaml';
        }
    }
    
    /**
     * Gets the highlight code used for syntax highlighter
     * @return {string} Language code
     */
    public function getHighlightCode() {
        return strtolower($this->Language()->HighlightCode);
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