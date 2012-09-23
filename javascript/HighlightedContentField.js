(function($) {
    $.entwine('ss', function($) {
        $('.highlightedcontent').entwine({
            onadd: function() {
                var middleColumn=this.find('.middleColumn');
                middleColumn.css('width', '300px');
                middleColumn.css('width', (this.closest('.tab').width()-50)+'px');
                
                
                //Setup the highlighter
                SyntaxHighlighter.defaults['toolbar']=false; //Disable the toolbar
                SyntaxHighlighter.defaults['quick-code']=false; //Disable the double click action that removes formatting
                SyntaxHighlighter.defaults['auto-links']=false; //Disable auto linking of web addresses
                SyntaxHighlighter.config.clipboardSwf='CodeBank/javascript/external/syntaxhighlighter/clipboard.swf'; //Path to clipboard swf
                
                //Init highlight
                SyntaxHighlighter.highlight();
            },
            
            fromWindow: {
                onresize: function(){
                    this._super();
                    
                    var middleColumn=this.find('.middleColumn');
                    middleColumn.css('width', '300px');
                    middleColumn.css('width', this.closest('.tab').width()+'px');
                }
            }
        });
    });
})(jQuery);