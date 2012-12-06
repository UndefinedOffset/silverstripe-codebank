<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border" id="cms-content-tools-CodeBankPackages">
	<div class="cms-panel-content center">
        <div class="cms-actions-row">
            <a class="cms-page-add-button ss-ui-button ss-ui-action-constructive" data-icon="add" href="admin/codeBank/packages/add" data-url-addpage="admin/codeBank/packages/add"><%t CodeBankPackages.ADD_NEW_PACKAGE "_Add New Package" %></a>
        </div>
        
        <h3 class="cms-panel-header"><%t CodeBank.PACKAGES "_Packages" %></h3>
        
        <div class="cms-content-view cms-tree-view-sidebar cms-panel-deferred" id="cms-content-treeview" data-url="$LinkTreeView">
            <%-- Lazy-loaded via ajax --%>
        </div>
    </div>
    
    <div class="cms-panel-content-collapsed">
		<h3 class="cms-panel-header"><%t CodeBank.PACKAGES "_Packages" %></h3>
	</div>
</div>