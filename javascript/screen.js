(function($) {
    var methods={
        /**
         * Initializer
         * @param {Object} options Options used for the screen's settings
         */
        init:function(options) {
            $.fn.codeBankScreen.screens.push(this);
            
            this.data('codeBankScreen', jQuery.extend({
                                        initCallback:function() {},
                                        enableCallback:function() {},
                                        disableCallback:function() {}
                                    },options));
            
            this.fadeOut(0);
            
            //Call the callback
            this.data('codeBankScreen').initCallback();
        },
        
        /**
         * Shows the screen, and calls the enable callback
         */
        show:function() {
            for(var i=0;i<$.fn.codeBankScreen.screens.length;i++) {
                if($.fn.codeBankScreen.screens[i]!=this) {
                    $.fn.codeBankScreen.screens[i].codeBankScreen('hide');
                }
            }
            
            this.fadeIn();
            
            //Perform Layout
            layout();
            
            //Call the callback
            this.data('codeBankScreen').enableCallback();
        },
        
        /**
         * Hides the screen, and calls the disable callback
         */
        hide:function() {
            this.fadeOut();
            
            //Call the callback
            this.data('codeBankScreen').disableCallback();
        }
    };
    
    /**
     * Handler method for screens
     */
    $.fn.codeBankScreen=function(method) {
        if(methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }else if (typeof method==='object' || !method) {
            return methods.init.apply(this, arguments);
        }else if(window.console) {
            window.console.error('Method ' + method + ' does not exist on jQuery.codeBankScreen');
        }
    };
    
    $.fn.codeBankScreen.screens=[];
})(jQuery);