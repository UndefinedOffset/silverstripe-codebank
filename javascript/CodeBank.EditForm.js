(function($) {
    $.entwine('ss', function($) {
        $('.CodeBankAddSnippet #Form_AddForm_Text, .CodeBankEditSnippet #Form_EditForm_Text').entwine({
            onkeydown: function(e) {
                if(e.keyCode===9) { // tab was pressed
                    var domElm=$(this).get(0);
                    
                    // get caret position/selection
                    var start=domElm.selectionStart;
                    var end=domElm.selectionEnd;
                    console.log(start, end);

                    // set textarea value to: text before caret + tab + text after caret
                    $(this).val($(this).val().substring(0, start)+"\t"+$(this).val().substring(end));

                    // put caret at right position again
                    domElm.selectionStart=domElm.selectionEnd=start+1;

                    // prevent the focus lose
                    return false;
                }
            }
        });
        
        
        //Cancel Button
        $('.CodeBankEditSnippet #Form_EditForm_action_doCancel').entwine({
            onclick: function(e) {
                $('.cms-container').loadPanel('admin/codeBank/show/'+$('#Form_EditForm_ID').val());
            }
        });
        
        
        $('.CodeBankEditSnippet input[name=Title]').entwine({
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