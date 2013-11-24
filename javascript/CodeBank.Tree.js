(function($) {
    $.entwine('ss.tree', function($) {
        $('.CodeBank .codebank-tree').entwine({
            onadd:function() {
                var self=this;
                
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
                
                //Handle when nodes are dropped
                this.bind('move_node.jstree', function(e, data) {
                    if(self.getIsUpdatingTree()) return;
                    self.setIsUpdatingTree(true);

                    var movedNode=data.rslt.o;
                    var newParentNode=data.rslt.np;
                    var newParentID=($(newParentNode).data('id') || 0);
                    var nodeID=$(movedNode).data('id');
                    
                    if(movedNode.data('pagetype')!='Snippet') {
                        setTimeout(function() {self.setIsUpdatingTree(false);}, 1000);
                        return;
                    }

                    $.ajax({
                        'url': self.data('urlSavetreenode'),
                        'data': {
                            ID: nodeID, 
                            ParentID: newParentID
                        },
                        success: function() {
                            $('.cms-edit-form :input[name=ParentID]').val(newParentID);
                            self.setIsUpdatingTree(false);
                            self.updateNodesFromServer([nodeID]);
                        },
                        error: function() {
                            self.setIsUpdatingTree(false);
                        },
                        statusCode: {
                            403: function() {
                                $.jstree.rollback(data.rlbk);
                            }
                        }
                    });
                });
                
                this._super();
            },
            getTreeConfig: function() {
                var self=this, config=this._super(), hints=this.getHints();
                
                config.plugins.push('contextmenu');
                
                //Setup Context Menu
                config.contextmenu={
                                    'items': function(node) {
                                                            // Build a list for allowed children as submenu entries
                                                            var pagetype=node.data('pagetype');
                                                            var id=node.data('id');
                                    
                                                            var allowedChildren=new Object;
                                                            $(hints[pagetype].allowedChildren).each(function(key, val) {
                                                                    allowedChildren["allowedchildren-"+key]={
                                                                                                            'label': '<span class="jstree-pageicon"></span>'+val.ssname,
                                                                                                            '_class': 'class-'+val.ssclass,
                                                                                                            'action': function(node) {
                                                                                                                    if(val.ssclass=='SnippetFolder') {
                                                                                                                        var dialog=$('#ss-ui-dialog-addfolder');
                                                                                                                        if(!dialog.length) {
                                                                                                                            dialog=$('<div class="ss-ui-dialog" id="ss-ui-dialog-addfolder" />');
                                                                                                                            $('body').append(dialog);
                                                                                                                        }
                                                                                                                        
                                                                                                                        dialog.ssdialog({iframeUrl: ss.i18n.sprintf(self.data('urlAddfolder'), id, val.ssclass), autoOpen: true, width: 300, height: 200, maxWidth: 670, maxHeight: 200});
                                                                                                                    }else {
                                                                                                                        $('.cms-container').entwine('.ss').loadPanel(ss.i18n.sprintf(self.data('urlAddsnippet'), id, val.ssclass));
                                                                                                                    }
                                                                                                                }
                                                                                                        };
                                                                });
                                                            
                                                            
                                                            var menuitems={};
                                                            
                                                           
                                                            // Test if there are any allowed Children and thus the possibility of adding some 
                                                            if(allowedChildren.hasOwnProperty('allowedchildren-0')) {
                                                                menuitems['addsubpage']={
                                                                                        'label': ss.i18n._t('CodeBankTree.ADD_CHILD', '_Add Child'),
                                                                                        'submenu': allowedChildren
                                                                                    };
                                                            }
                                                            
                                                            
                                                            //Add type specific menu items
                                                            if(pagetype=='Snippet') {
                                                                menuitems['edit']={
                                                                                    'label': ss.i18n._t('CodeBankTree.EDIT', '_Edit'),
                                                                                    'action': function(obj) {
                                                                                        $('.cms-container').entwine('.ss').loadPanel(self.data('urlEditscreen')+obj.data('id'));
                                                                                    }
                                                                                };
                                                            }else if(pagetype=='SnippetFolder') {
                                                                menuitems['rename']={
                                                                                    'label': ss.i18n._t('CodeBankTree.RENAME', '_Rename'),
                                                                                    'action': function(node) {
                                                                                        var dialog=$('#ss-ui-dialog-renamefolder');
                                                                                        if(!dialog.length) {
                                                                                            dialog=$('<div class="ss-ui-dialog" id="ss-ui-dialog-renamefolder" />');
                                                                                            $('body').append(dialog);
                                                                                        }
                                                                                        
                                                                                        dialog.ssdialog({iframeUrl: ss.i18n.sprintf(self.data('urlRenamefolder'), node.data('id')), autoOpen: true, width: 300, height: 200, maxWidth: 670, maxHeight: 200});
                                                                                    }
                                                                                };
                                                                
                                                                menuitems['delete']={
                                                                        'label': ss.i18n._t('CodeBankTree.DELETE', '_Delete'),
                                                                        'action': function(node) {
                                                                            if(confirm(ss.i18n.sprintf(ss.i18n._t('CodeBankTree.CONFIRM_FOLDER_DELETE', '_Are you sure you want to delete the folder "%s"?'), jQuery.trim(node.find('span.item:first').text())))) {
                                                                                $.ajax({
                                                                                    url: ss.i18n.sprintf(self.data('urlDeletefolder'), node.data('id')),
                                                                                    success: function(data) {
                                                                                        var parentNode=node.parent();
                                                                                        var removeHandler=function (e, data) {
                                                                                            data.rslt.obj.find('> ul > li').each(function() {
                                                                                                if($(this).data('pagetype')=='SnippetFolder') {
                                                                                                    data.inst.move_node(this, parentNode, 'first', false, false, true);
                                                                                                }else {
                                                                                                    data.inst.move_node(this, parentNode, 'last', false, false, true);
                                                                                                }
                                                                                            });
                                                                                        };
                                                                                        
                                                                                        //Bind to remove event, remove then unbind
                                                                                        self.bind('remove.jstree', removeHandler);
                                                                                        self.jstree('remove', node);
                                                                                        self.unbind('remove.jstree', removeHandler);
                                                                                    },
                                                                                    error: function() {
                                                                                        statusMessage(ss.i18n._t('CodeBankTree.ERROR_DELETING_FOLDER', '_Error Deleting Folder'), 'bad');
                                                                                    }
                                                                                });
                                                                            }
                                                                        }
                                                                    };
                                                            }
                                                            
                                                            
                                                            return menuitems;
                                                        } 
                                };
                
                
                //Enforce drag and drop rules
                config.crrm.move.check_move=function(data) {
                                                        //Only snippets can be dragged
                                                        if(data.o.data('pagetype')!='Snippet') {
                                                            return false;
                                                        }
                                                        
                                                        //Ensure the snippet is being dragged into it's language
                                                        if((data.np.data('pagetype')=='SnippetLanguage' && data.np.data('id')!='language-'+data.o.data('languageid')) || (data.np.data('pagetype')=='SnippetFolder' && data.np.data('languageid')!=data.o.data('languageid'))) {
                                                            return false;
                                                        }
                                                        
                                                        //Do not drag into snippets
                                                        if(data.np.data('pagetype')=='Snippet') {
                                                            return false;
                                                        }
                                                        
                                                        //Do not allow re-ordering
                                                        if(data.np.get(0)==data.op.get(0)) {
                                                            return false;
                                                        }
                                                        
                                                        //Do not allow dragging into root
                                                        if(data.np.data('id')==0) {
                                                            return false;
                                                        }
                                                        
                                                        return true;
                                                    };
                
                
                return config;
            },
            updateFromEditForm: function() {
                $(this).find('li.current').removeClass('current');
                
                this._super();
            }
        });
    });
})(jQuery);

/**
 * Updates nodes in the tree
 * @param data Node map data
 */
function updateCodeBankTreeNodes(data) {
    var self=jQuery('.CodeBank .codebank-tree').entwine('ss.tree');
    var selectedNode=self.jstree('get_selected');
    
    jQuery.each(data, function(nodeId, nodeData) {
        var node=self.getNodeByID(nodeId);
    
        // If no node data is given, assume the node has been removed
        if(!nodeData) {
            self.jstree('delete_node', node);
            return;
        }
    
        var correctStateFn=function(node) {
            self.jstree('deselect_all');
            self.jstree('select_node', node);
            // Similar to jstree's correct_state, but doesn't remove children
            var hasChildren = (node.children('ul').length > 0);
            node.toggleClass('jstree-leaf', !hasChildren);
            if(!hasChildren) node.removeClass('jstree-closed jstree-open');
        };
    
        // Check if node exists, create if necessary
        if(node.length) {
            self.updateNode(node, nodeData.html, nodeData);
            setTimeout(function() {
                correctStateFn(node);
            }, 500);
        }else {
            self.createNode(nodeData.html, nodeData, function(newNode) {
                correctStateFn(newNode);
                
                //Move Node to correct location
                var nextNode = nodeData.NextID ? self.find('li[data-id='+nodeData.NextID+']') : false;
                var prevNode = nodeData.PrevID ? self.find('li[data-id='+nodeData.PrevID+']') : false;
                if (nextNode && nextNode.length) {
                    self.jstree('move_node', newNode, nextNode, 'before', false, false, true);
                }else if (prevNode && prevNode.length) {
                    self.jstree('move_node', newNode, prevNode, 'after', false, false, true);
                }
                
                self.jstree('deselect_all');
                self.jstree('reselect');
                self.jstree('reopen');
                
                self.jstree('deselect_all');
                if(selectedNode && selectedNode.length) {
                    self.jstree('select_node', selectedNode);
                }
            });
        }
    });
}

/**
 * Renames a node in the tree
 * @param id ID of the node to be updated
 * @param nodeLabel New label html to be used for the node
 */
function renameCodeBankTreeNode(id, nodeLabel) {
    var self=jQuery('.CodeBank .codebank-tree').entwine('ss.tree');
    
    //Rename the node
    self.jstree('set_text', jQuery('#'+id), nodeLabel);
}