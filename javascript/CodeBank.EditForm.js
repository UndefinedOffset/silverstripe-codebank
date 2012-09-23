(function($) {
    $.entwine('ss', function($) {
        $('#Form_EditForm_Text').entwine({
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
    });
})(jQuery);