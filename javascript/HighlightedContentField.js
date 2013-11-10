(function($) {
    $.entwine('ss', function($) {
        $('.highlightedcontent').entwine({
            onadd: function() {
                //Setup the highlighter
                SyntaxHighlighter.defaults['toolbar']=false; //Disable the toolbar
                SyntaxHighlighter.defaults['quick-code']=false; //Disable the double click action that removes formatting
                SyntaxHighlighter.defaults['auto-links']=false; //Disable auto linking of web addresses
                SyntaxHighlighter.config.clipboardSwf=CB_DIR+'/javascript/external/syntaxhighlighter/clipboard.swf'; //Path to clipboard swf
                
                //Init highlight
                SyntaxHighlighter.highlight();
            },
            
            redraw: function(){
                this._super();
                
                var middleColumn=this.find('.middleColumn');
                middleColumn.css('width', '300px');
                middleColumn.css('width', this.closest('.tab').width()+'px');
            }
        });
        
        $('.cms-content.CodeBank').entwine({
            redraw: function() {
                this._super();
                
                $('.highlightedcontent').redraw();
            }
        });
    });
})(jQuery);