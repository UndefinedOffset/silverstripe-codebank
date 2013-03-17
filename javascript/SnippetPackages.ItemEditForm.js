(function($) {
    $.entwine('ss', function($) {
        //Export Button
        $('.CodeBankPackages #Form_ItemEditForm_action_doExportPackage').entwine({
            onclick: function(e) {
                window.open('code-bank-api/export-package?id='+$('#Form_ItemEditForm_ID').val());
                
                return false;
            }
        });
    });
})(jQuery);