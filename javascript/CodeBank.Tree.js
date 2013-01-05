(function($) {
    $.entwine('ss.tree', function($) {
        $('.CodeBank .codebank-tree').entwine({
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
            },
            getTreeConfig: function() {
                var self=this, config=this._super(), hints=this.getHints();
                config.plugins.push('contextmenu');
                config.contextmenu={
                                    'items': function(node) {
                                                            // Build a list for allowed children as submenu entries
                                                            var pagetype=node.data('pagetype');
                                                            var id=node.data('id');
                                                            
                                                            
                                                            var menuitems={};
                                                            
                                                            
                                                            if(pagetype=='SnippetLanguage') {
                                                                menuitems['addsubpage']={
                                                                                        'label': ss.i18n._t('CodeBankTree.ADD_SNIPPET', '_Add Snippet'),
                                                                                        'action': function(obj) {
                                                                                            $('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(self.data('urlAddscreen'), id));
                                                                                        }
                                                                                    };
                                                            }else {
                                                                menuitems['edit']={
                                                                                    'label': ss.i18n._t('CodeBankTree.EDIT', '_Edit'),
                                                                                    'action': function(obj) {
                                                                                        $('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(self.data('urlEditscreen'), obj.data('id')));
                                                                                    }
                                                                                };
                                                            }
                                                            
                                                            return menuitems;
                                                        } 
                                };
                return config;
            }
        });
    });
})(jQuery);