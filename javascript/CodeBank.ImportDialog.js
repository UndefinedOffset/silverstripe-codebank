(function($) {
    $(document).ready(function(e) {
        window.parent.jQuery('.import-dialog .ss-ui-dialog').bind('importDialogClosed', function(e) {
            window.parent.location.reload();
        });
    });
})(jQuery);