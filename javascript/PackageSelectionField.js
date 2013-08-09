(function($) {
    $.entwine('ss', function($) {
        $('.packageselection').entwine({
            UUID: null,
            onmatch: function() {
                this.setUUID(new Date().getTime());
            },
            handleSuccessResult: function(id) {
                var self=jQuery(this);
                jQuery('#ss-ui-dialog-'+this.getUUID()).ssdialog('close');
                
                //Reload Field
                $.ajax({
                    url: $(this).data('url')+'/ReloadField?id='+id,
                    success: function(data) {
                        self.replaceWith(data);
                    }
                });
            }
        });
        
        $('.packageselection button').entwine({
            onclick: function() {
                this._super();
                
                var self=this;
                var id='ss-ui-dialog-'+$(this).parent().parent().getUUID();
                var dialog = $('#' + id);
                if(!dialog.length) {
                    dialog = $('<div class="ss-ui-dialog" id="'+id+'"/>');
                    $('body').append(dialog);
                }
                
                var extraClass='packageselection-addnewdialog';
                
                dialog.ssdialog({iframeUrl: this.data('url'), autoOpen: true, dialogExtraClass: extraClass, width: 600, height: 200, maxWidth: 600, maxHeight: 200});
                return false;
            }
        });
    });
})(jQuery);