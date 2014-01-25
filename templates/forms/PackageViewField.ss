<% if $SourceList.Count %>
    <ul>
        <% loop $SourceList %>
            <li><a href="admin/codeBank/settings/EditForm/field/Packages/item/$ID/edit">$Title.XML</a> <a href="code-bank-api/export-package?id=$ID" target="_blank" class="exportPackageButton"><%t PackageViewField.EXPORT_PACKAGE "_Export Package" %></a>
                <% if $Top.ShowNested && $Snippets.Count %>
                    <ul>
                        <% loop $Snippets %>
                            <li><% if $ID!=$Top.CurrentSnippetID %><a href="admin/codeBank/show/$ID">$Title.XML</a><% else %>$Title.XML<% end_if %> <span>($Language.Name.XML)</span></li>
                        <% end_loop %>
                    </ul>
                <% end_if %>
            </li>
        <% end_loop %>
    </ul>
<% else %>
    <p><%t PackageViewField.NOT_IN_PACKAGE "_Not in a Package" %></p>
<% end_if %>