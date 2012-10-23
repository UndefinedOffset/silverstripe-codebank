<% if $SourceList.Count %>
    <ul>
        <% loop $SourceList %>
            <li><a href="admin/codeBank/show/$ID">$Title.XML</a> <span>($Language.Name.XML)</span>
                <% if $Top.ShowNested && $ClassName=="Snippet" && $PackageSnippets.Count %>
                    <ul>
                        <% loop $PackageSnippets %>
                            <li><a href="admin/codeBank/show/$ID">$Title.XML</a> <span>($Language.Name.XML)</span></li>
                        <% end_loop %>
                    </ul>
                <% end_if %>
            </li>
        <% end_loop %>
    </ul>
<% else %>
    <p><%t PackageViewField.NO_PACKAGES "_No Packages" %></p>
<% end_if %>