(function($) {
    $(document).ready(function() {
        $('#compareDialogContent .compare.leftSide').scroll(function(e) {
            $('#compareDialogContent .compare.rightSide').scrollTop($('#compareDialogContent .compare.leftSide').scrollTop());
            $('#compareDialogContent .compare.rightSide').scrollLeft($('#compareDialogContent .compare.leftSide').scrollLeft());
        });
        
        $('#compareDialogContent .compare.rightSide').scroll(function(e) {
            $('#compareDialogContent .compare.leftSide').scrollTop($('#compareDialogContent .compare.rightSide').scrollTop());
            $('#compareDialogContent .compare.leftSide').scrollLeft($('#compareDialogContent .compare.rightSide').scrollLeft());
        });
        
        
        //Listen for resize
        $(window).resize(redraw);
        redraw();
    });
    
    /**
     * Redraws the screen on resize
     */
    function redraw() {
        var columnWidth=($(document.body).width()/2)-35;
        $('.compareDialogWrapper h4, #compareDialogContent .compare').width(columnWidth);
    }
})(jQuery);
    