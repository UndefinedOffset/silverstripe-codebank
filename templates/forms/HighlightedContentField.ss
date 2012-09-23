<div class="innerWrapper">
    <pre id="{$ID}_highlight" class="brush: $HighlightCode<% if $extraClass %> $extraClass<% end_if %>">$Value.XML</pre>
</div>

<% if not $isReadonly %>
    <input $AttributesHTML />
<% end_if %>