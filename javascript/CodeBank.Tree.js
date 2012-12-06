(function($) {
    $.entwine('ss.tree', function($) {
        $('.CodeBank .cms-tree').entwine({
            onadd:function() {
                this._super();
                
                if(this.hasClass('codebank-tree')) {
                    this.bind('before.jstree', function(event, data) {
                        switch(data.plugin) {
                            case 'ui': {
                                        if(!data.inst.is_leaf(data.args[0])) {
                                            return false;
                                        }
                                        
                                        break;
                                    }
                        }
                    });
                }
            }
        });
    });
})(jQuery);