(function($) {
    $.entwine('ss', function($) {
        var codeBankTree;
        $('.codebank-tree').entwine({
            onmatch: function() {
                this._super();
                
                codeBankTree=$(this);
            }
        });
        
        
        //Copy Button
        $('.CodeBank #Form_EditForm_action_doCopy').entwine({
            onmatch: function() {
                $(this).zclip({
                                path: CB_DIR+'/javascript/external/jquery-zclip/ZeroClipboard.swf',
                                copy: $('#Form_EditForm_SnippetText').val()
                            });
            },
            onclick: function(e) {
                $(this).blur().focusout().removeClass('ui-state-hover ui-state-active');
                
                e.stopPropagation();
                return false;
            }
        });
        
        
        //Edit Button
        $('.CodeBank #Form_EditForm_action_doEditRedirect').entwine({
            onclick: function(e) {
                $('.cms-container').loadPanel(codeBankTree.data('url-editscreen')+$('#Form_EditForm_ID').val());
            }
        });
        
        
        //Export Button
        $('.CodeBank #Form_EditForm_action_doExport').entwine({
            onclick: function(e) {
                window.open('code-bank-api/export-snippet?id='+$('#Form_EditForm_ID').val());
                
                e.stopPropagation();
                return false;
            }
        });
        
        
        //Print Button
        $('.CodeBank #Form_EditForm_action_doPrint').entwine({
            onclick: function(e) {
                window.print();
                
                e.stopPropagation();
                return false;
            }
        });
        
        
        //Version Dropdown
        $('.CodeBank #Form_EditForm_RevisionID').entwine({
            onchange: function(e) {
                $(this).closest('.cms-container').loadPanel('admin/codeBank/show/'+$('#Form_EditForm_ID').val()+'/'+$(this).val());
            }
        });
        
        //Compare Revision Button
        $('.CodeBank #Form_EditForm_action_compareRevision').entwine({
            onclick: function(e) {
                var id='ss-ui-dialog-snippet-compare-'+$('#Form_EditForm_ID').val()+'-'+$('#Form_EditForm_RevisionID').val();
                var dialog=$('#'+id);
                if(!dialog.length) {
                    dialog=$('<div class="ss-ui-dialog" id="'+id+'"/>');
                    $('body').append(dialog);
                }
                
                dialog.ssdialog({iframeUrl: 'admin/codeBank/compare/'+$('#Form_EditForm_ID').val()+'/'+$('#Form_EditForm_RevisionID').val(), autoOpen: true, dialogExtraClass: 'code-bank-compare-popup'});
                return false;
            }
        });
    });
})(jQuery);