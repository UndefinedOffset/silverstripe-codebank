<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<h2>
				<% include CMSBreadcrumbs %>
			</h2>
		</div>
	
		<div class="cms-content-header-tabs">
			<ul>
				<li class="content-treeview<% if class == 'CMSPageEditController' %> ui-tabs-selected<% end_if %>">
					<a href="$EditLink" class="cms-panel-link" title="Form_EditForm" data-href="$EditLink">
						<%t CodeBank.MAIN "_Main" %>
					</a>
				</li>
				<li class="content-listview<% if $class=='CodeBankSettings' %> ui-tabs-selected<% end_if %>">
					<a href="$LinkSettings" class="cms-panel-link" title="Form_EditForm" data-href="$LinkSettings">
						<%t CodeBank.SETTINGS "_Settings" %>
					</a>
				</li>
			</ul>
		</div>
	</div>

	$Tools

	$EditForm
</div>