(function($) {
    $.entwine('ss', function($) {
        //Export Button
        $('.CodeBankPackages #Form_EditForm_action_doExport').entwine({
            onclick: function(e) {
                window.open('code-bank-api/export-package?id='+$('#Form_EditForm_ID').val());
                
                return false;
            }
        });
        
        $('.CodeBankPackages input[name=Title]').entwine({
            onchange: function() {
                this.updatedRelatedFields();
            },

            /**
             * Same as the onchange handler but callable as a method
             */
            updatedRelatedFields: function() {
                var menuTitle = this.val();
                this.updateTreeLabel(menuTitle);
            },

            /**
             * Function: updatePanelLabels
             * 
             * Update the tree
             * (String) title
             */
            updateTreeLabel: function(title) {
                var pageID = $('.cms-edit-form input[name=ID]').val();

                // only update immediate text element, we don't want to update all the nested ones
                var treeItem = $('.item:first', $('.cms-tree').find("[data-id='" + pageID + "']"));
                if (title && title != "") {
                    treeItem.text(title);
                }
            }
        });
    });
})(jQuery);