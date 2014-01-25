<div class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content" data-ignore-tab-state="true">
    <div class="cms-content-header north">
        <div class="cms-content-header-info">
            <% include CMSBreadcrumbs %>
        </div>
    
        <div class="cms-content-header-tabs">
            <ul class="cms-tabset-nav-primary">
                <li<% if $class=='CodeBank' %> class="ui-tabs-active"<% end_if %>>
                    <a href="$LinkMain" class="cms-panel-link" title="Form_EditForm" data-href="$LinkMain">
                        <%t CodeBank.SNIPPETS "_Snippets" %>
                    </a>
                </li>
                <li<% if $class=='CodeBankSettings' %> class="ui-tabs-active"<% end_if %>>
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