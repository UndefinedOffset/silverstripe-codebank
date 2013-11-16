(function($) {
    $(document).ready(function(e) {
        //Setup the highlighter
        SyntaxHighlighter.defaults['toolbar']=false; //Disable the toolbar
        SyntaxHighlighter.defaults['quick-code']=false; //Disable the double click action that removes formatting
        SyntaxHighlighter.defaults['auto-links']=false; //Disable auto linking of web addresses
        SyntaxHighlighter.config.clipboardSwf=CB_DIR+'/javascript/external/syntaxhighlighter/clipboard.swf'; //Path to clipboard swf
        
        //Init highlight
        SyntaxHighlighter.highlight();
        
        
        //Pop the print dialog after 1 second
        setTimeout(window.print, 1000);
    });
})(jQuery);