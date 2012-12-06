(function($) {
    $.entwine('ss', function($) {
        //Edit Button
        $('.CodeBankSettings #Form_EditForm_action_doExportToClient').entwine({
            onclick: function(e) {
                window.open($(this).data('exporturl'));
                
                $(this).blur().focusout().removeClass('ui-state-hover ui-state-active');
                e.stopPropagation();
                return false;
            }
        });
    });
})(jQuery);
