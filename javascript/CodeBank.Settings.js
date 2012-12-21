(function($) {
    $.entwine('ss', function($) {
        //Export Button
        $('.CodeBankSettings #Form_EditForm_action_doExportToClient').entwine({
            onclick: function(e) {
                window.open($(this).data('exporturl'));
                
                $(this).blur().focusout().removeClass('ui-state-hover ui-state-active');
                e.stopPropagation();
                return false;
            }
        });

        //Import Button
        $('.CodeBankSettings #Form_EditForm_action_doImportFromClient').entwine({
            UUID: null,
            onmatch: function() {
                this._super();
                this.setUUID(new Date().getTime());
            },
            onclick: function() {
                var self = this, id = 'ss-ui-dialog-' + this.getUUID();
                var dialog = $('#' + id);
                if(!dialog.length) {
                    dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
                    $('body').append(dialog);
                }
                
                var extraClass = this.data('popupclass')?this.data('popupclass'):'';
                
                dialog.ssdialog({iframeUrl: this.data('importurl'), autoOpen: true, dialogExtraClass: extraClass});
                
                $(this).blur().focusout().removeClass('ui-state-hover ui-state-active');
                e.stopPropagation();
                return false;
            }
        });
    });
})(jQuery);
