<div class="ss-dialog cms-page-add-form-dialog cms-dialog-content" id="cms-page-add-form" title="<% _t('CMSMain.AddNew', 'Add new page') %>">
	$AddForm
</div>

$ExtraTreeTools

<div class="center">
	<% if $TreeIsFiltered %>
    	<div class="cms-tree-filtered">
    		<strong><% _t('CMSMain.TreeFiltered', 'Filtered tree.') %></strong>
    		<a href="$Link" class="cms-panel-link">
    			<% _t('CMSMain.TreeFilteredClear', 'Clear filter') %>
    		</a>
    	</div>
	<% end_if %>

	<div class="cms-tree codebank-tree draggable" data-url-tree="$Link(getsubtree)" data-url-updatetreenodes="$Link(updatetreenodes)" data-url-addsnippet="admin/codeBank/addToLanguage?ID=%s&amp;ClassName=%s&amp;SecurityID=$SecurityID" data-url-addfolder="admin/codeBank/addFolder?ParentID=%s&amp;SecurityID=$SecurityID" data-url-renamefolder="admin/codeBank/renameFolder/?ID=%s&amp;SecurityID=$SecurityID" data-url-renamefolder="admin/codeBank/deleteFolder/?ID=%s&amp;SecurityID=$SecurityID" data-url-editscreen="$Link('edit')/show/%s" data-url-savetreenode="admin/codeBank/moveSnippet/" data-hints="$TreeHints">
		$SiteTreeAsUL
	</div>
</div>
