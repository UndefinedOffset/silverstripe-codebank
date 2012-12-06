<% if $SourceList.Count %>
    <ul>
        <% loop $SourceList %>
            <li><a href="admin/codeBank/packages/show/$ID">$Title.XML</a>
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
    <p><%t PackageViewField.NO_PACKAGES "_No Packages" %></p>
<% end_if %>