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
        $('#Form_EditForm_action_doCopy').entwine({
            //@TODO
        });
        
        
        //Edit Button
        $('#Form_EditForm_action_doEditRedirect').entwine({
            onclick: function(e) {
                $('.cms-container').loadPanel(codeBankTree.data('url-editscreen')+$('#Form_EditForm_ID').val());
            }
        });
        
        
        //Export Button
        $('#Form_EditForm_action_doExport').entwine({
            onclick: function(e) {
                window.open('code-bank-api/export-snippet?id='+$('#Form_EditForm_ID').val());
                
                e.stopPropagation();
                return false;
            }
        });
        
        
        //Print Button
        $('#Form_EditForm_action_doPrint').entwine({
            onclick: function(e) {
                window.print();
                
                e.stopPropagation();
                return false;
            }
        });
        
        
        //Compare Revision Button
        $('#Form_EditForm_action_compareRevision').entwine({
            //@TODO
        });
    });
})(jQuery);