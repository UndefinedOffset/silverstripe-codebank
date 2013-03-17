<select $AttributesHTML>
<% loop Options %>
	<option value="$Value.XML"<% if Selected %> selected="selected"<% end_if %><% if Disabled %> disabled="disabled"<% end_if %>>$Title.XML</option>
<% end_loop %>
</select>

<button type="button" class="ss-ui-button ss-ui-button-small" role="button" data-url="$Link('addPackage')"><%t PackageSelectionField.ADD_NEW "_Add New" %></button>