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

	<div class="cms-tree codebank-tree" data-url-tree="$Link(getsubtree)" data-url-updatetreenodes="$Link(updatetreenodes)" data-url-addscreen="admin/codeBank/add?LanguageID=%s&amp;ClassName=%s&amp;SecurityID=$SecurityID" data-url-addtolanguage="admin/codeBank/addToFolder?ID=%s" data-url-editscreen="$Link('edit')/show/%s" data-hints="$TreeHints">
		$SiteTreeAsUL
	</div>
</div>
