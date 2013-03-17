<div id="pages-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<h2>
				<% include CMSBreadcrumbs %>
			</h2>
		</div>
	
		<div class="cms-content-header-tabs">
			<ul>
				<li<% if $class=='CodeBank' %> class="ui-tabs-active"<% end_if %>>
					<a href="$LinkMain" class="cms-panel-link" data-href="$LinkMain">
						<%t CodeBank.SNIPPETS "_Snippets" %>
					</a>
				</li>
                <li<% if $class == 'CodeBankPackages' %> class="ui-tabs-active"<% end_if %>>
                    <a href="$LinkPackages" class="cms-panel-link" data-href="$LinkPackages">
                        <%t CodeBank.PACKAGES "_Packages" %>
                    </a>
                </li>
				<li<% if $class=='CodeBankSettings' %> class="ui-tabs-active"<% end_if %>>
					<a href="$LinkSettings" class="cms-panel-link" data-href="$LinkSettings">
						<%t CodeBank.SETTINGS "_Settings" %>
					</a>
				</li>
			</ul>
		</div>
	</div>

	$Tools

	$EditForm
</div>