<?php
class CodeBankShortCode {
    /**
     * Parses the snippet short code
     * @example [snippet id=123]
     * @example [snippet id=123 version=456]
     */
    public static function parse($arguments, $content=null, $parser=null) {
        //Ensure ID is pressent in the arguments
        if(!array_key_exists('id', $arguments)) {
            return '<p><b><i>'._t('CodeBankShortCode.MISSING_ID_ATTRIBUTE', '_Short Code missing the id attribute').'</i></b></p>';
        }
        
        
        //Fetch Snippet
        $snippet=Snippet::get()->byID(intval($arguments['id']));
        if(empty($snippet) || $snippet===false || $snippet->ID==0) {
            return '<p><b><i>'._t('CodeBankShortCode.SNIPPET_NOT_FOUND', '_Snippet not found').'</i></b></p>';
        }
        
        
        //Fetch Text
        $snippetText=$snippet->SnippetText;
        
        
        //If the version exists fetch it, and replace the text with that of the version
        if(array_key_exists('version', $arguments)) {
            $version=$snippet->Version(intval($arguments['version']));
            if(empty($version) || $version===false || $version->ID==0) {
                $snippetText=$version->Text;
            }
        }
        
        
        //Load CSS Requirements
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shCore.css');
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shCoreDefault.css');
        Requirements::css(CB_DIR.'/javascript/external/syntaxhighlighter/themes/shThemeDefault.css');
        
        //Load JS Requirements
        Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js');
        Requirements::javascript(CB_DIR.'/javascript/external/syntaxhighlighter/brushes/shCore.js');
        Requirements::javascript(CB_DIR.'/javascript/external/syntaxhighlighter/brushes/'.self::getBrushName($snippet->Language()->HighlightCode).'.js');
        Requirements::javascriptTemplate(CB_DIR.'/javascript/CodeBankShortCode.template.js', array('ID'=>$snippet->ID), 'snippet-highlightinit-'.$snippet->ID);
        
        
        //Render the snippet
        $obj=new ViewableData();
        return $obj->renderWith('CodeBankShortCode', array(
                                                            'ID'=>$snippet->ID,
                                                            'Title'=>$snippet->getField('Title'),
                                                            'Description'=>$snippet->getField('Description'),
                                                            'SnippetText'=>DBField::create_field('Text', $snippetText),
                                                            'HighlightCode'=>strtolower($snippet->Language()->HighlightCode)
                                                        ));
    }
    
    /**
     * Gets the brush name
     * @return {string} Name of the file used for the syntax highlighter brush
     */
    protected static function getBrushName($language) {
        switch(strtolower($language)) {
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
            case 'sql':return 'shBrushSql';
            case 'vb':
            case 'vbnet':return 'shBrushVb';
            case 'xml':
            case 'xhtml':
            case 'xslt':
            case 'html':return 'shBrushXml';
            case 'ss':
            case 'silverstripe':return 'shBrushSilverStripe';
        }
    }
}
?>