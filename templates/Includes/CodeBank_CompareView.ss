<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <head>
        <title></title>
        <% base_tag %>
    </head>
    <body class="cms">
        <div class="cms-container">
            <div class="compareDialogWrapper">
                <h4><%t CodeBank.CURRENT_REVISION "_Current Revision" %></h4>
                <h4><%t CodeBank.COMPARED_REVISION "_Compared Revision" %></h4>
                
                <div id="compareDialogContent">
                    <% if $CompareContent %>
                        $CompareContent.RAW
                    <% else %>
                        <p class="message warning"><%t CodeBank.NO_DIFFERENCES "_There are no differences between the revisions" %></p>
                    <% end_if %>
                </div>
            </div>
        </div>
        <script type="text/javascript"></script>
    </body>
</html>