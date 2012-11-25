(function($) {
    $.entwine('ss', function($) {
        $('.packageview').entwine({
            'from .cms-container': {
                onafterstatechange: function(e) {
                    $('.cms-content .cms-tree li.current').removeClass('current');
                }
            }
        });
        
        $('.packageview a').entwine({
            onclick: function(e) {
                var container=$('.cms-container');
                
                var url=$(this).attr('href');
                if(url && url!='#') {
                    // Deselect all nodes (will be reselected after load according to form state)
                    var tree=$('.cms-content .cms-tree');
                    tree.jstree('deselect_all');
                    tree.jstree('uncheck_all');
                    tree.find('li').removeClass('current');
                    
                    
                    // Ensure URL is absolute (important for IE)
                    if($.path.isExternal($(this))) {
                        url=$.path.makeUrlAbsolute(url, $('base').attr('href'));
                    }
                    
                    
                    // Retain search parameters
                    if(document.location.search) {
                        url=$.path.addSearchParams(url, document.location.search.replace(/^\?/, ''));
                    }
                    
                    // Load new page
                    container.loadPanel(url);
                }
                
                e.stopPropagation();
                return false;
            }
        });
    });
})(jQuery);