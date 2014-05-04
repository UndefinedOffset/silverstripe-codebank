<?php
class SnippetLanguage extends DataObject {
    private static $db=array(
                            'Name'=>'Varchar(100)',
                            'FileExtension'=>'Varchar(45)',
                            'HighlightCode'=>'Varchar(45)',
                            'BrushFile'=>'Text',
                            'UserLanguage'=>'Boolean',
                            'Hidden'=>'Boolean'
                         );
    
    private static $has_many=array(
                                'Snippets'=>'Snippet.Language',
                                'Folders'=>'SnippetFolder'
                            );
    
    private static $defaults=array(
                                'HighlightCode'=>'Plain',
                                'UserLanguage'=>true,
                                'Hidden'=>false
                            );
    
    private static $extensions=array(
                                    'SnippetHierarchy'
                                );
    
    private static $default_sort='Name';
    
    private static $allowed_children=array(
                                        'Snippet',
                                        'SnippetFolder'
                                    );
    
    private static $default_child='Snippet';
    
    private $defaultLanguages=array(
                                    'Flex 3'=>array('Extension'=>'mxml', 'HighlightCode'=>'Flex'),
                                    'ActionScript 3'=>array('Extension'=>'as', 'HighlightCode'=>'AS3'),
                                    'PHP'=>array('Extension'=>'php', 'HighlightCode'=>'Php'),
                                    'Bison'=>array('Extension'=>'bison', 'HighlightCode'=>'bison'),
                                    'C'=>array('Extension'=>'c', 'HighlightCode'=>'Cpp'),
                                    'C++'=>array('Extension'=>'cpp', 'HighlightCode'=>'Cpp'),
                                    'C#'=>array('Extension'=>'csharp', 'HighlightCode'=>'CSharp'),
                                    'ChangeLog'=>array('Extension'=>'log', 'HighlightCode'=>'Plain'),
                                    'CSS'=>array('Extension'=>'css', 'HighlightCode'=>'Css'),
                                    'Diff'=>array('Extension'=>'diff', 'HighlightCode'=>'Diff'),
                                    'Flex'=>array('Extension'=>'mxml', 'HighlightCode'=>'Flex'),
                                    'GLSL'=>array('Extension'=>'glsl', 'HighlightCode'=>'Plain'),
                                    'Haxe'=>array('Extension'=>'haxe', 'HighlightCode'=>'Plain'),
                                    'HTML'=>array('Extension'=>'html', 'HighlightCode'=>'Xml'),
                                    'Java'=>array('Extension'=>'java', 'HighlightCode'=>'Java'),
                                    'Java properties'=>array('Extension'=>'properties', 'HighlightCode'=>'properties'),
                                    'JavaScript'=>array('Extension'=>'js', 'HighlightCode'=>'JScript'),
                                    'JavaScript with DOM'=>array('Extension'=>'js', 'HighlightCode'=>'JScript'),
                                    'LaTeX'=>array('Extension'=>'latax', 'HighlightCode'=>'Latex'),
                                    'LDAP'=>array('Extension'=>'ldap', 'HighlightCode'=>'Plain'),
                                    'Log'=>array('Extension'=>'log', 'HighlightCode'=>'Plain'),
                                    'LSM (Linux Software Map)'=>array('Extension'=>'lsm', 'HighlightCode'=>'Plain'),
                                    'M4'=>array('Extension'=>'m4', 'HighlightCode'=>'Plain'),
                                    'Makefile'=>array('Extension'=>'makefile', 'HighlightCode'=>'Plain'),
                                    'Oracle SQL'=>array('Extension'=>'sql', 'HighlightCode'=>'Sql'),
                                    'Pascal'=>array('Extension'=>'pascal', 'HighlightCode'=>'Delphi'),
                                    'Perl'=>array('Extension'=>'pl', 'HighlightCode'=>'Perl'),
                                    'Prolog'=>array('Extension'=>'prolog', 'HighlightCode'=>'Plain'),
                                    'Python'=>array('Extension'=>'python', 'HighlightCode'=>'Python'),
                                    'RPM spec'=>array('Extension'=>'spec', 'HighlightCode'=>'Plain'),
                                    'Ruby'=>array('Extension'=>'ruby', 'HighlightCode'=>'Ruby'),
                                    'S-Lang'=>array('Extension'=>'slang', 'HighlightCode'=>'Plain'),
                                    'Scala'=>array('Extension'=>'scala', 'HighlightCode'=>'Scala'),
                                    'Shell'=>array('Extension'=>'sh', 'HighlightCode'=>'Bash'),
                                    'SQL'=>array('Extension'=>'sql', 'HighlightCode'=>'Sql'),
                                    'Standard ML'=>array('Extension'=>'sml', 'HighlightCode'=>'Plain'),
                                    'Tcl'=>array('Extension'=>'tcl', 'HighlightCode'=>'Plain'),
                                    'XML'=>array('Extension'=>'xml', 'HighlightCode'=>'Xml'),
                                    'Xorg configuration'=>array('Extension'=>'conf', 'HighlightCode'=>'Plain'),
                                    'Objective Caml'=>array('Extension'=>'caml', 'HighlightCode'=>'Plain'),
                                    'AppleScript'=>array('Extension'=>'applescript', 'HighlightCode'=>'AppleScript'),
                                    'Assembler'=>array('Extension'=>'asm', 'HighlightCode'=>'Asm'),
                                    'Ada'=>array('Extension'=>'ada', 'HighlightCode'=>'Ada'),
                                    'ColdFusion'=>array('Extension'=>'cf', 'HighlightCode'=>'ColdFusion'),
                                    'Batch'=>array('Extension'=>'bat', 'HighlightCode'=>'Bat'),
                                    'Bash'=>array('Extension'=>'bash', 'HighlightCode'=>'Bash'),
                                    'Delphi'=>array('Extension'=>'delphi', 'HighlightCode'=>'Delphi'),
                                    'Erlang'=>array('Extension'=>'el', 'HighlightCode'=>'Erlang'),
                                    'F#'=>array('Extension'=>'fsharp', 'HighlightCode'=>'FSharp'),
                                    'Groovy'=>array('Extension'=>'groovy', 'HighlightCode'=>'Groovy'),
                                    'Visual Basic'=>array('Extension'=>'vb', 'HighlightCode'=>'Vb'),
                                    'PowerShell'=>array('Extension'=>'ps', 'HighlightCode'=>'PowerShell'),
                                    'Other'=>array('Extension'=>'txt', 'HighlightCode'=>'Plain'),
                                    'SilverStripe Template'=>array('Extension'=>'ss', 'HighlightCode'=>'SilverStripe'),
                                    'Yaml'=>array('Extension'=>'yml', 'HighlightCode'=>'Yaml'),
                                    'AutoIt'=>array('Extension'=>'au3', 'HighlightCode'=>'AutoIt')
                                );
    
    
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
     * Adds the default languages if they are missing
     */
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        $defaultLangs=array_keys($this->defaultLanguages);
        $dbLangCount=SnippetLanguage::get()
                                        ->filter('Name', $defaultLangs)
                                        ->filter('UserLanguage', false)
                                        ->Count();
        if($dbLangCount<count($defaultLangs)) {
            foreach($this->defaultLanguages as $name=>$data) {
                if(!SnippetLanguage::get()->find('Name', $name)) {
                    $lang=new SnippetLanguage();
                    $lang->Name=$name;
                    $lang->FileExtension=$data['Extension'];
                    $lang->HighlightCode=$data['HighlightCode'];
                    $lang->UserLanguage=false;
                    $lang->write();
                    
                    DB::alteration_message('Created snippet language "'.$name.'"', 'created');
                }
            }
        }
        
        
        //Look for config languages
        $configLanguages=CodeBank::config()->extra_languages;
        if(!empty($configLanguages)) {
            foreach($configLanguages as $language) {
                //Validate languages
                if(empty($language['Name']) || empty($language['FileName']) || empty($language['HighlightCode']) || empty($language['Brush'])) {
                    user_error('Invalid snippet user language found in config, user languages defined in config must contain a Name, FileName, HighlightCode and Brush file path', E_USER_WARNING);
                    continue;
                }
                
                
                $lang=SnippetLanguage::get()
                                            ->filter('Name', Convert::raw2sql($language['Name']))
                                            ->filter('HighlightCode', Convert::raw2sql($language['HighlightCode']))
                                            ->filter('UserLanguage', true)
                                            ->first();
                
                if(empty($lang) || $lang===false || $lang->ID<=0) {
                    if(Director::is_absolute($language['Brush']) || Director::is_absolute_url($language['Brush'])) {
                        user_error('Invalid snippet user language found in config, user languages defined in config must contain a path to the brush relative to the SilverStripe base ('.Director::baseFolder().')', E_USER_WARNING);
                        continue;
                    }
                    
                    if(preg_match('/\.js$/', $language['Brush'])==0) {
                        user_error('Invalid snippet user language found in config, user languages defined in config must be javascript files', E_USER_WARNING);
                        continue;
                    }
                    
                    
                    //Add language
                    $lang=new SnippetLanguage();
                    $lang->Name=$language['Name'];
                    $lang->FileExtension=$language['FileName'];
                    $lang->HighlightCode=$language['HighlightCode'];
                    $lang->BrushFile=$language['Brush'];
                    $lang->UserLanguage=true;
                    $lang->write();
                    
                    DB::alteration_message('Created snippet user language "'.$language['Name'].'"', 'created');
                }
            }
        }
    }
    
    /**
     * Checks to see if the given member can delete this object or not
     * @param {Member} $member Member instance or member id to check
     * @return {bool} Returns boolean true or false depending if the user can delete this object
     */
    public function canDelete($member=null) {
        $parentResult=parent::canDelete($member);
        
        if($parentResult==false || $this->UserLanguage==false) {
            return false;
        }
        
        if($this->Folders()->count()>0 || $this->Snippets()->count()>0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Gets fields used in the cms
     * @return {FieldSet} Fields to be used
     */
    public function getCMSFields() {
        $fields=new FieldList(
                            new TextField('Name', _t('SnippetLanguage.NAME', '_Name'), null, 100),
                            new TextField('FileExtension', _t('SnippetLanguage.FILE_EXTENSION', '_File Extension'), null, 45),
                            new CheckboxField('Hidden', _t('SnippetLanguage.HIDDEN', '_Hidden'))
                        );
        
        if($this->UserLanguage==false) {
            $fields->replaceField('Name', $fields->dataFieldByName('Name')->performReadonlyTransformation());
            $fields->replaceField('FileExtension', $fields->dataFieldByName('FileExtension')->performReadonlyTransformation());
        }
        
        return $fields;
    }
    
    public function Folders() {
        return $this->getComponents('Folders', 'ParentID=0');
    }
    
    /**
     * Determins if the language has snippets
     * return {bool} Counts how many children snippets there are if there are more than 0 returns true, false otherwise
     */
    public function hasSnippets() {
        return ($this->Snippets()->Count()>0);
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
    
    public function summaryFields() {
        return array(
                    'Name'=>_t('SnippetLanguage.NAME', '_Name'),
                    'UserLanguage'=>_t('SnippetLanguage.USER_LANGUAGE', '_User Language'),
                    'Hidden'=>_t('SnippetLanguage.HIDDEN', '_Hidden')
                );
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