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
            onclick: function(e) {
                var self = this, id = 'ss-ui-dialog-' + this.getUUID();
                var dialog = $('#' + id);
                if(!dialog.length) {
                    dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
                    $('body').append(dialog);
                }
                
                dialog.ssdialog({iframeUrl: this.data('importurl'), autoOpen: true, dialogExtraClass: 'import-dialog', close: function(e, ui) {dialog.trigger('importDialogClosed');}, width: 400, height: 300, maxWidth: 670, maxHeight: 300});
                
                $(this).blur().focusout().removeClass('ui-state-hover ui-state-active');
                e.stopPropagation();
                return false;
            }
        });
        
        //Export Package Button
        $('.CodeBankSettings table.ss-gridfield-table tbody td .cb-export-link').entwine({
            onclick: function(e) {
                //Kill the event but allow the default action
                e.stopPropagation();
            }
        });
    });
})(jQuery);