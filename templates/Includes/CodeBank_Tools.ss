<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CMSMain">
	<div class="cms-panel-content center">
        <div class="cms-content-toolbar">
            <div class="cms-actions-row">
                <a class="cms-page-add-button ss-ui-button ss-ui-action-constructive" data-icon="add" href="$LinkPageAdd" data-url-addpage="{$LinkPageAdd('?ParentID=%s')}"><%t CodeBank.ADD_NEW_SNIPPET "_Add New Snippet" %></a>
            </div>
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