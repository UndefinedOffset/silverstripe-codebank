<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CodeBank">
    <div class="cms-panel-content center">
        <div class="cms-content-toolbar">
            <div class="cms-actions-row">
                <a class="cms-page-add-button ss-ui-button ss-ui-action-constructive" data-icon="add" href="admin/codeBank/add" data-url-addpage="admin/codeBank/add"><%t CodeBank.ADD_NEW_SNIPPET "_Add New Snippet" %></a>
            </div>
            
            <h3 class="cms-panel-header">&nbsp;</h3>
            
            <div class="cms-actions-row">
                $SearchForm
            </div>
            
            <h3 class="cms-panel-header"></h3>
        </div>
        
        
        <h3 class="cms-panel-header"><%t CodeBank.LANGUAGES "_Languages" %></h3>
        
        <div class="cms-content-view cms-tree-view-sidebar cms-panel-deferred" id="cms-content-treeview" data-url="$LinkTreeView">
            <%-- Lazy-loaded via ajax --%>
        </div>
    </div>
    <div class="cms-panel-content-collapsed">
        <h3 class="cms-panel-header"><%t CodeBank.LANGUAGES "_Languages" %></h3>
    </div>
</div>