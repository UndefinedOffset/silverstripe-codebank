(function($) {
    $(document).ready(function() {
        //Setup the highlighter
        SyntaxHighlighter.defaults['toolbar']=true; //Enable Toolbar
        SyntaxHighlighter.defaults['quick-code']=true; //Enable Double Clicking to Show Code
        SyntaxHighlighter.defaults['auto-links']=false; //Disable auto linking of web addresses
        SyntaxHighlighter.config.clipboardSwf=CB_DIR+'/javascript/external/syntaxhighlighter/clipboard.swf'; //Path to clipboard swf
        
        //Init highlight
        SyntaxHighlighter.highlight();
    });
})(jQuery);