<?php
class HighlightedContentField extends FormField {
    protected $language;
    
    public function __construct($name, $title=null, $language=null, $value=null) {
        parent::__construct($name, $title, $value);
        
        $this->language=$language;
        $this->extraClasses['stacked']='stacked';
    }
    
    /**
     * @return array
     */
    public function getAttributes() {
        $attrs=array(
                    'type'=>'hidden',
                    'name'=>$this->getName(),
                    'value'=>$this->Value(),
                    'class'=>$this->extraClass(),
                    'id'=>$this->ID(),
                    'disabled'=>$this->isDisabled(),
                    'title'=>$this->getDescription(),
                );
        
        return array_merge($attrs, $this->attributes);
    }
    
    /**
     * Returns the form field - used by templates. Although FieldHolder is generally what is inserted into templates, all of the field holder templates make use of $Field.  It's expected that FieldHolder will give you the "complete" representation of the field on the form, whereas Field will give you the core editing widget, such as an input tag.
     * @param {array} $properties key value pairs of template variables
     * @return {string} Returns the html to be sent to the browser
     */
    public function Field($properties=array()) {
        $obj=($properties ? $this->customise($properties):$this);
        
        
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shCore.css');
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shCore'.$this->theme_file().'.css');
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shTheme'.$this->theme_file().'.css');
        Requirements::css(CB_DIR.'/css/HighlightedContentField.css');
        
        Requirements::javascript(CB_DIR.'/javascript/external/syntaxhighlighter/brushes/shCore.js');
        
        $brushName=$this->getBrushName();
        if(!empty($brushName)) {
            Requirements::javascript(CB_DIR.'/javascript/external/syntaxhighlighter/brushes/'.$this->getBrushName().'.js');
        }else {
            $lang=SnippetLanguage::get()->filter('HighlightCode', Convert::raw2sql($this->language))->filter('UserLanguage', true)->first();
            if(!empty($lang) && $lang!==false && $lang->ID>0 && !empty($lang->BrushFile)) {
                Requirements::javascript($lang->BrushFile);
            }
        }
        
        Requirements::javascript(CB_DIR.'/javascript/HighlightedContentField.js');
        
        
        return $obj->renderWith('HighlightedContentField');
    }
    
    /**
     * Gets the brush name
     * @return {string} Name of the file used for the syntax highlighter brush
     */
    public function getBrushName() {
        switch(strtolower($this->language)) {
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
     * sets the language used for syntax highlighter
     * @param {string} $lang Language code
     */
    public function setLanguage($lang) {
        $this->language=$lang;
    }
    
    /**
     * Gets the language used for syntax highlighter
     * @return {string} Language code
     */
    public function getLanguage() {
        return $this->language;
    }
    
    /**
     * Gets the highlight code used for syntax highlighter
     * @return {string} Language code
     */
    public function getHighlightCode() {
        return strtolower($this->language);
    }
    
    /**
     * Detects the theme based on the current config
     * @return {string} Theme based on looking at the value in config, if its not valid or not present use 'Default'
     */
    protected function theme_file() {
        switch(HighlightedContentField::config()->theme) {
            case 'Django':
            case 'Eclipse':
            case 'Emacs':
            case 'FadeToGrey':
            case 'MDUltra':
            case 'FlashDevelop':
            case 'FlexBuilder':
            case 'RDark':return HighlightedContentField::config()->theme;
            default:return 'Default';
        }
    }
    
    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation() {
        $field=clone $this;
        $field->setReadonly(true);
        
        return $field;
    }
}
?>