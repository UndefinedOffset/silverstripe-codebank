/**
 * Handles laying out the page
 */
function layout() {
    fitToParent('bodyWrapper', 5);
    fitToParent('contentWrapper', 0);
    
    $('#sidebar').height($('#contentWrapper').height()-42);
    $('#snippetTree').height($('#sidebar').height()-$('#snippetTree').position().top-20);
    
    //Screens
    $('.screen').css('height', ($('#contentWrapper').height()-15)+'px');
    
    //Manage Languages Screen
    $('.manageLanguagesTableWrapper').css('height', ($('#contentWrapper').height()-118)+'px');
    
    //Snippet Screen
    adjustSnippetContainerHeight();
}

/**
 * Adjusts the height of the snippet container based on its surroundings
 */
function adjustSnippetContainerHeight() {
    $('#snippetContainer').height($('#SnippetScreen').height()-document.getElementById('snippetContainer').offsetTop-($('#snippetActions').height()+15));
}


//Bind layout() to window.onresize
window.onresize = layout;