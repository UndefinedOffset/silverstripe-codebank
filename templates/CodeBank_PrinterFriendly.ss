<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <head>
        <title>$Snippet.Title.XML - Code Bank</title>
        <% base_tag %>
    </head>
    <body class="cms CodeBankPrinterFriendly">
        <div class="header">
            <img src="$CodeBankDir/images/print-logo.jpg" alt="" class="logo"/>
            
            <h2>$Snippet.Title.XML</h2>
            
            <div class="clear"><!--  --></div>
        </div>
        
        <pre id="PrintHighlight" class="brush: $Snippet.HighlightCode"><% if $SnippetVersion %>$SnippetVersion.Text.XML<% else %>$Snippet.SnippetText.XML<% end_if %></pre>
        
        <p class="copyright"><%t CodeBank.PRINT_COPYRIGHT "_Printed from Code Bank which is copyright {year} Ed Chipman" year=$Now.Year %></p>
        
        <script type="text/javascript"></script>
    </body>
</html>