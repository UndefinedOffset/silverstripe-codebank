(function($) {
    var server;
    var loggedIn=false;
    var userID;
    var userName;
    var snippetID;
    var ipAgreementEditor;
    var sessionTimer;
    
    $(document).ready(function() {
        //Perform Layout
        layout();
        
        
        //Setup the highlighter
        SyntaxHighlighter.defaults['toolbar']=false; //Disable the toolbar
        SyntaxHighlighter.defaults['quick-code']=false; //Disable the double click action that removes formatting
        SyntaxHighlighter.defaults['auto-links']=false; //Disable auto linking of web addresses
        SyntaxHighlighter.config.clipboardSwf='javascript/external/syntaxhighlighter/clipboard.swf'; //Path to clipboard swf
        
        //Setup Screens
        $('#LoginScreen').codeBankScreen({initCallback: loginScreenInit, enableCallback: loginScreenShow, disableCallback: loginScreenHide});
        $('#SnippetScreen').codeBankScreen({enableCallback: snippetScreenShow, disableCallback: snippetScreenHide});
        $('#NewSnippetScreen').codeBankScreen({enableCallback: newSnippetScreenShow, disableCallback: newSnippetScreenHide});
        $('#EditSnippetScreen').codeBankScreen({enableCallback: editSnippetScreenShow, disableCallback: editSnippetScreenHide});
        $('#IPAgreementScreen').codeBankScreen({enableCallback: ipAgreementScreenShow, disableCallback: ipAgreementScreenHide});
        $('#ManageUsersScreen').codeBankScreen({enableCallback: manageUsersScreenShow, disableCallback: manageUsersScreenHide});
        $('#ManageLanguagesScreen').codeBankScreen({enableCallback: manageLanguagesScreenShow, disableCallback: manageLanguagesScreenHide});
        $('#sidebar, #NotifyArea, #popupMask, #changePasswordDialog, #donateDialog, #ipAgreementDialog, #adminChangePasswordDialog, #addUserDialog, #compareDialog, #adminAddLanguageDialog, #adminEditLanguageDialog').fadeOut(0);
        $('#donateButton').click(showDonateDialog);
        
        
        //Setup listeners
        $('#logoutButton').click(function(e) {
            server.logout();
            
            showLoginScreen();
            
            e.stopPropagation();
            return false;
        });
        
        //Form Required fields
        $('#changePasswordForm, #NewSnippetForm, #adminChangePasswordForm, #adminAddUserForm').validate();
        $('#adminAddLanguageForm, #adminEditLanguageForm').validate({
            rules: {
                language: {
                    required: true,
                    maxlength: 100
                },
                fileExtension: {
                    required: true,
                    accept: '[a-zA-Z]+',
                    maxlength: 45
                }
            }
        });
        
        //Snippet field text area tab enable
        $('#NewSnippetForm_Code, #EditSnippetForm_Code').keydown(function(e) {
            if(e.keyCode===9) { // tab was pressed
                // get caret position/selection
                var start=this.selectionStart;
                var end=this.selectionEnd;

                // set textarea value to: text before caret + tab + text after caret
                $(this).val($(this).val().substring(0, start)+"\t"+$(this).val().substring(end));

                // put caret at right position again
                this.selectionStart=this.selectionEnd=start+1;

                // prevent the focus lose
                return false;
            }
        });
        
        
        $('#snippetTree li.file').live('click', function() {
                                                            //clear all clicked items if any
                                                            $('#snippetTree li.clicked').removeClass('clicked');
                                                            
                                                            //set this clicked
                                                            $(this).addClass('clicked');
                                                            
                                                            //Load snippet
                                                            loadSnippet($(this).children('span').attr('id').replace('snippet_',''));
                                                        });
        
        $('#LoadingScreen').hide();
        
        //Connect to the server
        var url=window.location.href.replace(/\/(index\.html)?(#?)$/, '')+'/server.json.php';
        server=jQuery.Zend.jsonrpc({'url': url, error: errorHandler, exceptionHandler: errorHandler, beforeSend: loadingHandler, complete: loadingCompleteHandler});
        if(server.error==true && server.error_request.responseText=='Could not find database config, please re-install the code bank server or run update.php') {
            window.location='install.php';
        }else if(server.error) {
            alert('Server Error: '+server.error_request.responseText);
            return;
        }
        
        
        var connectInfo=server.connect();
        if(connectInfo.data[0]=='@@VERSION@@') {
            $('#Logo').text('Development Build');
        }else {
            $('#Logo').text(connectInfo.data[0]+' '+connectInfo.data[1]);
        }
        
        //Check login
        var loginStatus=server.getSessionId();
        if(loginStatus.session!='expired') {
            server.logout();
        }
        
        $('#LoginScreen').codeBankScreen('show');
    });
    
    /**
     * Handles errors from the server
     * @param {string} Error message
     */
    function errorHandler(data) {
        if(window.console) {
            console.log(data);
        }
        
        if(data.message && data.message!='') {
            showNotification(data.message, 'error');
        }else {
            showNotification('An error has occured', 'error');
        }
    }
    
    /**
     * Handles when menu items are clicked on
     * @param e Event Data
     */
    function menuItemClick(e) {
        //If selected or disabled do nothing
        if($(this).hasClass('selected') || $(this).hasClass('disabled')) {
            e.stopPropagation();
            return false;
        }
        
        //Change Selection
        $('#TabNav a:not(#TabNav a.disabled)').removeClass('selected');
        $(this).addClass('selected');
        
        
        switch($(this).attr('id')) {
            case 'snippetsButton':$('#SnippetScreen').codeBankScreen('show');break;
            case 'newSnippetsButton':$('#NewSnippetScreen').codeBankScreen('show');break;
            case 'manageUserAccounts':if(userName=='admin') {$('#ManageUsersScreen').codeBankScreen('show');}break;
            case 'manageIPAgreement':if(userName=='admin') {$('#IPAgreementScreen').codeBankScreen('show');}break;
            case 'manageLanguages':if(userName=='admin') {$('#ManageLanguagesScreen').codeBankScreen('show');}break;
        }

        e.stopPropagation();
        return false;
    }
    
    /**
     * Shows a notification, in the notify bar
     * @param {string} message Message to be displayed
     * @param {string} type Type name (notice, error, good)
     */
    function showNotification(message, type) {
        if(typeof(type)=='undefined') {
            type='notice';
        }
        
        $('#NotifyArea').attr('class', type);
        $('#NotifyArea .content').text(message);
        
        $('#NotifyArea').fadeIn();
        
        setTimeout('$("#NotifyArea").fadeOut();', 4000);
    }

    /**
     * Handles when a server request starts
     */
    function loadingHandler() {
        clearTimeout(sessionTimer);
        $('#loadingSpinner').show();
    }
    
    /**
     * Handles when a server request completes
     */
    function loadingCompleteHandler() {
        $('#loadingSpinner').hide();
        
        if(loggedIn) {
            sessionTimer=setTimeout(sessionExpired, 1200000);//20min in milliseconds
        }
    }
    
    /**
     * Checks to see if the user's session is still good or not
     * @param {Object} data Checks to see if the users session is valid or not 
     */
    function checkLogin(data) {
        if(data.login==false && data.session=='expired') {
            showNotification('Session Expired', 'Warning');
            
            showLoginScreen();
        }
    }
    
    /**
     * Clears the snippet screen and shows the login screen
     */
    function showLoginScreen() {
        clearTimeout(sessionTimer);
        
        $('#TopNav, #TabNav').hide();
        $('#loggedInAs').text('');
        $('#TabNav a').attr('class','tab disabled');
        $('#TabNav #snippetsButton').addClass('selected');
        $('#popupMask, #changePasswordDialog, #donateDialog, #ipAgreementDialog').fadeOut(0);
        $('#sidebar').fadeOut();
        
        //Clean up
        $('#snippetContainer').children().remove();
        $('#snippetRaw').val('');
        $('#snippetID').val('');
        $('#snippetTree ul').remove();
        $('#snippetActions button, #snippetActions select').attr('disabled','disabled');
        $('#SnippetForm_Rev option').remove();
        $('#SnippetForm_Rev').append('<option>{Current Revision}</option>');
        $('#snippetLang').text('');
        $('#snippetAuthor').text('');
        $('#snippetLastEditUser').text('');
        $('#snippetModified').text('');
        userName=null;
        userID=null;
        snippetID=null;
        
        //Clear listeners
        $('#changePassword').unbind('click', showChangePassword);
        $('#TabNav a').unbind('click', menuItemClick);
        
        //Show login
        $('#LoginScreen').codeBankScreen('show');
        
        loggedIn=false;
    }
    
    
    /***** Login Screen ******/
    /**
     * Initializer for the login screen
     */
    function loginScreenInit() {
        $('#LoginForm').validate();
    }
    
    /**
     * Login screen show callback
     */
    function loginScreenShow() {
        $('#LoginForm_username, #LoginForm_password, #LoginForm_login').attr('disabled', '');
        $('#LoginForm_username').val('');
        $('#LoginForm_password').val('');
        
        $('#LoginForm_login').bind('click', doLogin);
    }
    
    /**
     * Login screen hide callback
     */
    function loginScreenHide() {
        $('#LoginForm_login').unbind('click', doLogin);
    }
    
    /**
     * Performs a login attempt
     * @param {Event} e Event Data
     * @return {bool} Returns False
     */
    function doLogin(e) {
        if($('#LoginForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        
        var response=server.login({user: $('#LoginForm_username').val(), pass: $('#LoginForm_password').val()});
        if(response.status=='HELO') {
            userID=response.data.id;
            userName=$('#LoginForm_username').val();
            $('#loggedInAs').text(userName);
            
            showNotification('Login Successful', 'good');
            
            sessionTimer=setTimeout(sessionExpired, 1200000);//20min in milliseconds
            
            if(response.data.hasIPAgreement==false) {
                loginComplete();
            }else {
                showIPAgreement();
            }
        }else {
            showNotification('Incorrect username/password, please try again', 'error');
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Handles when the users session expires
     */
    function sessionExpired() {
        server.logout();
        
        showLoginScreen();
        
        alert('Your session has expired');
    }
    
    /**
     * Handles when the login completes, performs initial setup of the snippet screen. Loading in the languages and snippets.
     */
    function loginComplete() {
        loggedIn=true;
        
        $('#LoginScreen').codeBankScreen('hide');
        $('#sidebar').fadeIn();
        $('#SnippetScreen').codeBankScreen('show');
        $('#TopNav, #TabNav').show();
        $('#changePassword').click(showChangePassword);
        
        //Setup Listeners
        $('#TabNav a').click(menuItemClick);
        
        //Enabled menu options
        $('#TabNav #snippetsButton, #TabNav #newSnippetsButton').removeClass('disabled');
        if(userName=='admin') {
            $('#TabNav #manageUserAccounts, #TabNav #manageIPAgreement, #TabNav #manageLanguages').removeClass('disabled');
            
            if(typeof(ipAgreementEditor)=='undefined') {
                //Setup the wysiwyg 
                ipAgreementEditor=$('#IPAgreementForm_IPAgreement').htmlbox({about:false, idir:'javascript/external/htmlbox/images/', icons:'', toolbars:[['cut','copy','paste','separator','bold','italic','underline','separator','left','center','right','separator','ol','ul','separator','link','unlink','separator','formats']]});
            }
        }
        
        
        if(reloadLanguagesList()==false) {
            return;
        }
        
        
        var snippetsResponse=server.getSnippits();
        
        populateLanguageTree(snippetsResponse);
        
        $('#FilterForm_Filter').change(filterLanguageTree);
        $('#SearchForm_Search').click(searchSnippets);
    }
    
    /**
     * Reloads the language list
     */
    function reloadLanguagesList() {
        //Get languages
        var langs=server.getLanguages();
        checkLogin(langs);//Session Check
        
        if(langs.status=='EROR') {
            errorHandler(langs);
            return false;
        }
        
        $('#FilterForm_Filter option').remove();
        $('#FilterForm_Filter').append($('<option>{All Languages}</option>'));
        
        for(var i=0;i<langs.data.length;i++) {
            var option=$('<option/>');
            option.text(langs.data[i].language);
            option.attr('value', langs.data[i].id);
            option.data('langData', langs.data[i]);
            option.appendTo($('#FilterForm_Filter'));
        }
        
        return true;
    }
    
    /**
     * Requests the language tree filtered by the current selection in the filter dropdown'
     */
    function filterLanguageTree() {
        var snippetsResponse=null;
        
        //Get languages
        if($('#FilterForm_Filter').val()!='{All Languages}') {
            snippetsResponse=server.getSnippitsByLanguage({id: $('#FilterForm_Filter').val()});
        }else {
            snippetsResponse=server.getSnippits();
        }
        
        checkLogin(snippetsResponse);//Session Check
        
        if(snippetsResponse.status=='EROR') {
            errorHandler(snippetsResponse);
            return;
        }
        
        populateLanguageTree(snippetsResponse);
    }
    
    /**
     * Performs a search of the snippets on the server and calls populateLanguageTree() when the response comes back
     * @param {Event} e Event Data
     * @see #populateLanguageTree()
     */
    function searchSnippets(e) {
        var snippetsResponse=server.searchSnippits({query:$('#SearchForm_Query').val()});
        
        checkLogin(snippetsResponse);//Session Check
        
        if(snippetsResponse.status=='EROR') {
            errorHandler(snippetsResponse);
            return;
        }
        
        populateLanguageTree(snippetsResponse);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Populates the language tree
     * @param {Object} Snippets response from the server
     */
    function populateLanguageTree(snippetsResponse) {
        //Loop through and create the xml for each language
        var snippets=$('<ul class="filetree"/>');
        for(var lKey in snippetsResponse.data) {
            var lang=snippetsResponse.data[lKey];
            var langNode=$('<li/>').append($('<span class="folder"/>').text(lang.language));
            var langTree=$('<ul/>');
            
            //Loop through and create the xml for each snippet's title
            for(var sKey in lang.snippits) {
                var snip=lang.snippits[sKey];
                langTree.append($('<li class="file"/>').append($('<span class="file"/>').text(snip.title).attr('id', 'snippet_'+snip.id)));
            }
            
            //Append the tree to the node
            langNode.append(langTree);
            
            //Append to the snippets XML
            snippets.append(langNode);
        }
        
        $('#snippetTree ul').remove();
        $('#snippetTree').append(snippets);
        
        //Init tree view
        snippets.treeview({
            collapsed: true,
            unique: true
        });
    }
    
    /***** IP Agreement *****/
    /**
     * Shows the IP Agreement dialog
     */
    function showIPAgreement() {
        $('#popupMask, #ipAgreementDialog').fadeIn();
        
        $('#LoginScreen').codeBankScreen('hide');
        
        var response=server.getIPMessage();
        $('#ipAgreementContent').html(response.data);
        
        $('#IPAgreement_Disagree').click(ipAgreementDisagree);
        $('#IPAgreement_Agree').click(ipAgreementAgree);
    }
    
    /**
     * Handles when the user disagrees to the IP Agreement
     * @param {Event} e Event Data
     */
    function ipAgreementDisagree(e) {
        server.logout();
        
        showLoginScreen();
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Handles when the user agrees to the IP Agreement
     * @param {Event} e Event Data
     */
    function ipAgreementAgree(e) {
        $('#popupMask, #ipAgreementDialog').fadeOut();
        
        loginComplete();
        
        e.stopPropagation();
        return false;
    }
    
    /***** Change Password Dialog *****/
    /**
     * Shows the change password dialog
     */
    function showChangePassword(e) {
        $('#popupMask, #changePasswordDialog').fadeIn();
        $('#ChangePasswordForm_CurrentPassword').focus();
        
        $('#popupMask').click(hideChangePassword);
        $('#ChangePasswordForm_Cancel').click(hideChangePassword);
        $('#ChangePasswordForm_Submit').click(doChangePassword);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the change password dialog
     */
    function hideChangePassword(e) {
        $('#popupMask, #changePasswordDialog').fadeOut();
        
        $('#changePasswordForm input').val('');
        
        $('#popupMask').unbind('click', hideChangePassword);
        $('#ChangePasswordForm_Cancel').unbind('click', hideChangePassword);
        $('#ChangePasswordForm_Submit').unbind('click', doChangePassword);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Performs the change password action
     * @param {Event} e Event Data
     */
    function doChangePassword(e) {
        if($('#changePasswordForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        if($('#ChangePasswordForm_NewPassword').val()!=$('#ChangePasswordForm_ConfirmPassword').val()) {
            showNotification('Passwords do not match', 'error');
            
            e.stopPropagation();
            return false;
        }
        
        var response=server.changeUserPassword({id: userID, password: $('#ChangePasswordForm_NewPassword').val(), currPassword: $('#ChangePasswordForm_CurrentPassword').val()});
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            
            e.stopPropagation();
            return false;
        }else {
            hideChangePassword();
            showNotification('Password Changed, please login again', 'good');
            server.logout();
            showLoginScreen();
        }
        
        e.stopPropagation();
        return false;
    }
    
    /***** Snippet Screen ******/
    /**
     * Performs initializers for the Snippet Screen
     */
    function snippetScreenInit() {
        $('#snippetCopyButton').zclip({
                                        path:'javascript/external/jquery-zclip/ZeroClipboard.swf',
                                        copy:function(){return $('#snippetRaw').val();}
                                    });
    }
    
    /**
     * Setup method when the snippet screen is shown
     */
    function snippetScreenShow() {
        $('#snippetDeleteButton').click(deleteSnippet);
        $('#snippetPrintButton').click(window.print);
        $('#snippetExportButton').click(exportSnippet);
        $('#snippetEditButton').click(editSnippet);
        $('#snippetCompareButton').click(getDiff);
    }
    
    /**
     * Destructor method for when the snippet screen is hidden
     */
    function snippetScreenHide() {
        if($('#snippetTree li.clicked')) {
            $('#snippetTree li.clicked').removeClass('clicked');
        }
        
        $('#snippetContainer').children().remove();
        $('#snippetRaw').val('');
        $('#snippetID').val('');
        $('#snippetActions button, #snippetActions select').attr('disabled','disabled');
        $('#SnippetForm_Rev option').remove();
        $('#SnippetForm_Rev').append('<option>{Current Revision}</option>');
        $('#snippetLang').text('');
        $('#snippetAuthor').text('');
        $('#snippetLastEditUser').text('');
        $('#snippetModified').text('');
        $('#snippetTitle').html('&nbsp;');
        $('#snippetDesc').html('&nbsp;');
        
        //Remove Event Listeners
        $('#snippetDeleteButton').unbind('click', deleteSnippet);
        $('#snippetPrintButton').unbind('click', window.print);
        $('#snippetExportButton').unbind('click', exportSnippet);
        $('#snippetEditButton').unbind('click', editSnippet);
        $('#snippetCompareButton').unbind('click', getDiff);
    }
    
    /**
     * Handles loading of the snippets
     * @param {int} id Snippet Database ID
     */
    function loadSnippet(id) {
        var snippet=server.getSnippitInfo({'id':id, style: 'Eclipse'});
        checkLogin(snippet);
        
        if(snippet.status=='EROR') {
            errorHandler(snippet);
        }
        
        //Clear snippet container
        $('#snippetContainer').children().remove();
        
        $('#snippetTitle').text(snippet.data[0].title);
        $('#snippetDesc').text(snippet.data[0].description);
        $('#snippetLang').text(snippet.data[0].language);
        $('#snippetAuthor').text(snippet.data[0].creator);
        $('#snippetLastEditUser').text((snippet.data[0].lastEditor==null ? snippet.data[0].creator:snippet.data[0].lastEditor));
        $('#snippetModified').text(snippet.data[0].lastModified);
        $('#snippetContainer').append($('<pre class="brush: '+snippet.data[0].shjs_code.toLowerCase()+'"/>').text(snippet.data[0].text));
        $('#snippetRaw').val(snippet.data[0].text);
        $('#snippetID').val(id);
        snippetID=id;
        
        //Highlight
        syntax_highlight();
        if(SyntaxHighlighter.brushes[snippet.data[0].shjs_code]) {
            SyntaxHighlighter.highlight();
        }else {
            setTimeout("SyntaxHighlighter.highlight();", 500);
        }
        
        adjustSnippetContainerHeight();
        
        $('#snippetActions .topButtons button').attr('disabled','');
        
        if(snippet.data[0].creatorID!=userID && userName!='admin') {
            $('#snippetDeleteButton').attr('disabled', 'disabled');
        }
        
        
        //Populate the revisions dropdown and enable it and the button if needed
        var revisions=server.getSnippitRevisions({id: id});
        $('#SnippetForm_Rev option').remove();
        
        for(var i=0;i<revisions.data.length;i++) {
            $('#SnippetForm_Rev').append('<option value="'+revisions.data[i].id+'">'+revisions.data[i].date+'</option>');
        }
        
        if(revisions.data.length>1) {
            $('#SnippetForm_Rev, #snippetCompareButton').attr('disabled', '');
        }
    }
    
    /**
     * Shows the edit snippet screen
     * @param {Event} e Event data
     */
    function editSnippet(e) {
        $('#EditSnippetScreen').codeBankScreen('show');
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Prompts the user to make sure they want to delete this snippet, if they agree the request is sent to the server to delete the snippet
     * @param {Event} e Event data
     */
    function deleteSnippet(e) {
        if(confirm('Are you sure you want to delete this snippet?')) {
            var response=server.deleteSnippit({id:$('#snippetID').val()});
            
            checkLogin(response);//Session Check
            
            if(response.status=='EROR') {
                errorHandler(response);
                return;
            }else {
                showNotification('Snippet Deleted Successfully', 'good');
                
                //Clean up the snippet screen
                $('#snippetContainer').children().remove();
                $('#snippetRaw').val('');
                $('#snippetID').val('');
                $('#snippetActions button, #snippetActions select').attr('disabled','disabled');
                $('#SnippetForm_Rev option').remove();
                $('#SnippetForm_Rev').append('<option>{Current Revision}</option>');
                
                filterLanguageTree(); //Reload the language tree
            }
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Opens a new window allowing the user to download the snippet
     * @param {Event} e Event data
     */
    function exportSnippet(e) {
        window.open('exportSnippit.php?id='+$('#snippetID').val());
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Handles loading of the diff from the server
     * @param e Event Data
     */
    function getDiff(e) {
        var response=server.getHTMLSnippitDiff({'mainRev': $('#snippetID').val(), 'compRev': $('#SnippetForm_Rev').val()});
        
        checkLogin(response);//Session Check
        
        if(response.data=='') {
            showNotification('There are no differences between the two revisions');
        }else {
            //Populate content
            $('#compareDialogContent').html('<div class="compare leftSide">'+response.data[0]+'</div>'+
                                        '<div class="compare rightSide">'+response.data[1]+'</div>');
            
            
            //Setup Listeners
            $('#CompareDialog_Close').click(hideCompareDialog);
            $('#compareDialogContent .compare.leftSide').scroll(syncRight);
            $('#compareDialogContent .compare.rightSide').scroll(syncLeft);
            
            
            //Show
            $('#popupMask, #compareDialog').fadeIn();
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Syncs the right compare with the left when the box is scrolled
     * @param e Event Data
     */
    function syncRight(e) {
        $('#compareDialogContent .compare.rightSide').scrollTop($('#compareDialogContent .compare.leftSide').scrollTop());
        $('#compareDialogContent .compare.rightSide').scrollLeft($('#compareDialogContent .compare.leftSide').scrollLeft());
    }
    
    /**
     * Syncs the left compare with the right when the box is scrolled
     * @param e Event Data
     */
    function syncLeft(e) {
        $('#compareDialogContent .compare.leftSide').scrollTop($('#compareDialogContent .compare.rightSide').scrollTop());
        $('#compareDialogContent .compare.leftSide').scrollLeft($('#compareDialogContent .compare.rightSide').scrollLeft());
    }
    
    /**
     * Hides the compare dialog
     * @param e Event Data
     */
    function hideCompareDialog(e) {
        //Hide
        $('#popupMask, #compareDialog').fadeOut();
        
        
        //Unbind listeners
        $('#CompareDialog_Close').unbind('click', hideCompareDialog);
        $('#compareDialogContent .compare.leftSide').unbind('scroll', syncRight);
        $('#compareDialogContent .compare.rightSide').unbind('scroll', syncLeft);
        
        
        //Cleanup
        $('#compareDialogContent').html('');
    }
    

    
    /***** Donate Dialog *****/
    /**
     * Shows the donation form
     */
    function showDonateDialog(e) {
        $('#popupMask, #donateDialog').fadeIn();
        
        $('#popupMask').click(hideDonateDialog);
        $('#PayPalForm_Cancel').click(hideDonateDialog);
        $('#PayPalForm_Donate').click(hideDonateDialogNoStop);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the change password dialog
     */
    function hideDonateDialog(e) {
        $('#popupMask, #donateDialog').fadeOut();
        
        $('#changePasswordForm input').val('');
        $('#changePasswordForm label.error').remove();
        
        $('#popupMask').unbind('click', hideDonateDialog);
        $('#PayPalForm_Cancel').unbind('click', hideDonateDialog);
        $('#PayPalForm_Donate').unbind('click', hideDonateDialogNoStop);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Hides the change password dialog without stopping propegation
     */
    function hideDonateDialogNoStop(e) {
        $('#popupMask, #donateDialog').fadeOut();
        
        $('#popupMask').unbind('click', hideDonateDialog);
        $('#PayPalForm_Cancel').unbind('click', hideDonateDialog);
        $('#PayPalForm_Donate').unbind('click', hideDonateDialogNoStop);
    }
    
    /**** New Snippet Screen ****/
    /**
     * Performs actions to be called when the new snippet screen is shown
     */
    function newSnippetScreenShow() {
        $('#NewSnippetScreen select, #NewSnippetScreen input, #NewSnippetScreen textarea, #NewSnippetScreen button').attr('disabled','');
        $('#sidebarBlocker').show();
        
        $('#NewSnippetForm_Language option').remove();
        $('#FilterForm_Filter option').each(function() {
                                                        $(this).clone().appendTo('#NewSnippetForm_Language');
                                                    });
        $('#NewSnippetForm_Language option:first-child').text('{Select Language}').attr('value','');
        
        $('#NewSnippetForm_Save').click(doNewSnippetSave);
    }
    
    /**
     * Performs actions to be called when the new snippet screen is hidden
     */
    function newSnippetScreenHide() {
        $('#NewSnippetScreen input, #NewSnippetScreen textarea, #NewSnippetScreen button').attr('disabled','disabled');
        $('#NewSnippetScreen select, #NewSnippetScreen input, #NewSnippetScreen textarea').val('');
        $('#NewSnippetScreen select, #NewSnippetScreen input, #NewSnippetScreen textarea').removeClass('valid');
        $('#NewSnippetScreen label.error').remove();
        $('#sidebarBlocker').hide();
        
        $('#NewSnippetForm_Save').unbind('click', doNewSnippetSave);
    }
    
    /**
     * Saves a new snippet to the server
     * @param {Event} e Event Data
     */
    function doNewSnippetSave(e) {
        if($('#NewSnippetForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        var data={
            'language':$('#NewSnippetForm_Language').val(),
            'title':$('#NewSnippetForm_Title').val(),
            'description':$('#NewSnippetForm_Description').val(),
            'code':$('#NewSnippetForm_Code').val(),
            'tags':$('#NewSnippetForm_Tags').val()
        };
        
        
        var response=server.newSnippit(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('Snippet Added Successfully', 'good');
            
            $('#SnippetScreen').codeBankScreen('show');
            
            //Change Selection
            $('#TabNav a:not(#TabNav a.disabled)').removeClass('selected');
            $('#TabNav #snippetsButton').addClass('selected');
            
            //Reload Snippets
            var snippetsResponse=server.getSnippits();
            populateLanguageTree(snippetsResponse);
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**** Edit Snippet Screen ****/
    /**
     * Performs actions to be called when the edit snippet screen is shown
     */
    function editSnippetScreenShow() {
        $('#sidebarBlocker').show();
        
        //Populate language select
        $('#EditSnippetForm_Language option').remove();
        $('#FilterForm_Filter option').each(function() {
                                                        $(this).clone().appendTo('#EditSnippetForm_Language');
                                                    });
        $('#EditSnippetForm_Language option:first-child').text('{Select Language}').attr('value','');
        
        
        //Get Snippet info from server
        var snippetResult=server.getSnippitInfo({id:snippetID, style:'Eclipse'});
        
        checkLogin(snippetResult);//Session Check
        
        if(snippetResult.status=='EROR') {
            errorHandler(snippetResult);
            return;
        }
        
        
        $('#EditSnippetForm_Language').val(snippetResult.data[0].languageID);
        $('#EditSnippetForm_Title').val(snippetResult.data[0].title);
        $('#EditSnippetForm_Description').val(snippetResult.data[0].description);
        $('#EditSnippetForm_Code').val(snippetResult.data[0].text);
        $('#EditSnippetForm_Tags').val(snippetResult.data[0].tags);
        
        
        //Enable the form
        $('#EditSnippetScreen select, #EditSnippetScreen input, #EditSnippetScreen textarea, #EditSnippetScreen button').attr('disabled','');
        $('#EditSnippetForm_Save').click(doEditSnippetSave);
        $('#EditSnippetForm_Cancel').click(cancelEditSnippet);
    }
    
    /**
     * Performs actions to be called when the edit snippet screen is hidden
     */
    function editSnippetScreenHide() {
        $('#EditSnippetScreen input, #EditSnippetScreen textarea, #EditSnippetScreen button').attr('disabled','disabled');
        $('#EditSnippetScreen select, #EditSnippetScreen input, #EditSnippetScreen textarea').val('');
        $('#EditSnippetScreen select, #EditSnippetScreen input, #EditSnippetScreen textarea').removeClass('valid');
        $('#EditSnippetScreen label.error').remove();
        $('#sidebarBlocker').hide();
        
        $('#EditSnippetForm_Save').unbind('click', doEditSnippetSave);
        $('#EditSnippetForm_Cancel').unbind('click', cancelEditSnippet);
    }
    
    /**
     * Cancels the edit snippet request
     * @param {Event} e Event Data
     */
    function cancelEditSnippet(e) {
        $('#SnippetScreen').codeBankScreen('show');
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Saves a new snippet to the server
     * @param {Event} e Event Data
     */
    function doEditSnippetSave(e) {
        if($('#EditSnippetForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        var data={
            'id':snippetID,
            'language':$('#EditSnippetForm_Language').val(),
            'title':$('#EditSnippetForm_Title').val(),
            'description':$('#EditSnippetForm_Description').val(),
            'code':$('#EditSnippetForm_Code').val(),
            'tags':$('#EditSnippetForm_Tags').val()
        };
        
        
        var response=server.saveSnippit(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('Snippet Saved Successfully', 'good');
            
            $('#SnippetScreen').codeBankScreen('show');
            
            //Reload Snippets
            var snippetsResponse=server.getSnippits();
            populateLanguageTree(snippetsResponse);
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**** IP Agreement Screen ****/
    /**
     * Performs actions to be called when the ip agreement screen is shown
     */
    function ipAgreementScreenShow() {
        var response=server.getIPMessage();

        checkLogin(response);//Session Check
        
        ipAgreementEditor.set_text(response.data);
        
        $('#sidebarBlocker').show();
        
        //Enable fields
        $('#IPAgreementForm_IPAgreement, #IPAgreementForm_Save').attr('disabled','');
        
        //Listeners
        $('#IPAgreementForm_Save').click(doIPAgreementSave);
    }
    
    /**
     * Saves the ip agreement
     * @param {Event} e Event Data
     */
    function doIPAgreementSave(e) {
        var data={
            'message':(ipAgreementEditor.get_text()=='' ? '':ipAgreementEditor.get_html())
        };
        
        
        var response=server.saveIPMessage(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('Intellectual Property Agreement Saved Successfully', 'good');
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Performs actions to be called when the ip agreement screen is hidden
     */
    function ipAgreementScreenHide() {
        $('#sidebarBlocker').hide();
        
        //Cleanup
        $('#IPAgreementForm_Save').unbind('click', doIPAgreementSave);
        
        //Disable fields
        $('#IPAgreementForm_IPAgreement, #IPAgreementForm_Save').attr('disabled','disabled');
        
        if(typeof(ipAgreementEditor)!='undefined') {
            ipAgreementEditor.set_text('');
        }
    }
    
    /**** Manage User Accounts Screen ****/
    /**
     * Performs actions to be called when the manage user accounts screen is shown
     */
    function manageUsersScreenShow() {
        $('#sidebarBlocker').show();
        $('#ManageUserAccountsForm_UndeleteUser').hide();
        
        var response=server.getUsersList();
        
        checkLogin(response);//Session Check
        
        for(var i=0;i<response.data.length;i++) {
            var user=response.data[i];
            if(user.deleted==true && $('#ManageUserAccountsForm input[name=listType]:checked').val()=='0') {
                continue;
            }
            
            $('#ManageUserAccountsTable tbody').append($('<tr'+(user.deleted==true ? ' class="deleted"':'')+'>'+
                '<td><input type="radio" name="selectedUser" id="SelectedUser_'+user.id+'" value="'+user.id+'"/><label for="SelectedUser_'+user.id+'">'+user.id+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.username+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.lastLogin+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.lastLoginIP+'</label></td>'+
            '</tr>'));
        }
        
        
        //Enable fields
        $('#ManageUserAccountsForm_AddUser').attr('disabled', '');
        
        //Listeners
        $('#ManageUserAccountsTable tbody label').live('click', onManageUserClick);
        $('#ManageUserAccountsForm input[name=listType]').change(onManageUserListTypeChange);
        $('#ManageUserAccountsForm_DeleteUser').click(onManageUserDelete);
        $('#ManageUserAccountsForm_UndeleteUser').click(onManageUserUndelete);
        $('#ManageUserAccountsForm_CUPUser').click(onManageUserChangePW);
        $('#ManageUserAccountsForm_AddUser').click(onManageUserAddAccount);
    }
    
    /**
     * Performs actions to be called when the manage user accounts screen is hidden
     */
    function manageUsersScreenHide() {
        $('#sidebarBlocker').hide();
        
        //Cleanup
        $('#ManageUserAccountsTable tbody tr').remove();
        $('#ManageUserAccountsForm_HideDeleted').attr('checked', 'checked');
        $('#ManageUsersScreen button').attr('disabled','disabled');
        $('#ManageUserAccountsForm_DeleteUser').show();
        $('#ManageUserAccountsForm_UndeleteUser').hide();
        
        //Disable fields
        $('#ManageUserAccountsTable tbody label').die('click', onManageUserClick);
        $('#ManageUserAccountsForm input[name=listType]').unbind('change', onManageUserListTypeChange);
        $('#ManageUserAccountsForm_DeleteUser').unbind('click', onManageUserDelete);
        $('#ManageUserAccountsForm_UndeleteUser').unbind('click', onManageUserUndelete);
        $('#ManageUserAccountsForm_CUPUser').unbind('click', onManageUserChangePW);
        $('#ManageUserAccountsForm_AddUser').unbind('click', onManageUserAddAccount);
    }
    
    /**
     * Handles when the user clicks on a user
     * @param {Event} e Event Data
     */
    function onManageUserClick(e) {
        $('#ManageUserAccountsTable tbody tr').removeClass('selected');
        $(this).parent().parent().addClass('selected');
        
        if($(this).parent().parent().hasClass('deleted')) {
            $('#ManageUserAccountsForm_DeleteUser').hide();
            $('#ManageUserAccountsForm_DeleteUser').attr('disabled', 'disabled');
            $('#ManageUserAccountsForm_UndeleteUser').show();
            $('#ManageUserAccountsForm_UndeleteUser').attr('disabled', '');
        }else {
            $('#ManageUserAccountsForm_DeleteUser').show();
            $('#ManageUserAccountsForm_DeleteUser').attr('disabled', '');
            $('#ManageUserAccountsForm_UndeleteUser').hide();
            $('#ManageUserAccountsForm_UndeleteUser').attr('disabled', 'disabled');
        }
        
        $('#ManageUserAccountsForm_DeleteUser').attr('disabled', '');
        $('#ManageUserAccountsForm_CUPUser').attr('disabled', '');
    }
    
    /**
     * Handles reloading the users list when the type changes
     * @param {Event} e Event Data
     */
    function onManageUserListTypeChange(e) {
        $('#ManageUserAccountsTable tbody tr').remove();
        $('#ManageUserAccountsForm_DeleteUser').show();
        $('#ManageUserAccountsForm_UndeleteUser').hide();
        $('#ManageUserAccountsForm_CUPUser, #ManageUserAccountsForm_DeleteUser, #ManageUserAccountsForm_UndeleteUser').attr('disabled', 'disabled');
        
        var response=server.getUsersList();
        
        checkLogin(response);//Session Check
        
        for(var i=0;i<response.data.length;i++) {
            var user=response.data[i];
            
            if(user.deleted==true && $('#ManageUserAccountsForm input[name=listType]:checked').val()=='0') {
                continue;
            }
            
            $('#ManageUserAccountsTable tbody').append($('<tr'+(user.deleted==true ? ' class="deleted"':'')+'>'+
                '<td><input type="radio" name="selectedUser" id="SelectedUser_'+user.id+'" value="'+user.id+'"/><label for="SelectedUser_'+user.id+'">'+user.id+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.username+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.lastLogin+'</label></td>'+
                '<td><label for="SelectedUser_'+user.id+'">'+user.lastLoginIP+'</label></td>'+
            '</tr>'));
        }
    }
    
    /**
     * Handles deletion of users
     * @param {Event} e Event Data
     */
    function onManageUserDelete(e) {
        if(confirm('Are you sure you want to delete the selected user?')==false) {
            e.stopPropagation();
            return false;
        }
        
        var data={id:$('#ManageUserAccountsTable input[name=selectedUser]:checked').val()};
        var response=server.deleteUser(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('User Deleted Successfully', 'good');
            onManageUserListTypeChange(e);
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Handles deletion of users
     * @param {Event} e Event Data
     */
    function onManageUserUndelete(e) {
        var data={id:$('#ManageUserAccountsTable input[name=selectedUser]:checked').val()};
        var response=server.undeleteUser(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('User undeleted Successfully', 'good');
            onManageUserListTypeChange(e);
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Shows the admin change password dialog
     */
    function onManageUserChangePW(e) {
        $('#popupMask, #adminChangePasswordDialog').fadeIn();
        $('#AdminChangePasswordForm_Password').focus();
        
        $('#popupMask').click(hideAdminChangePassword);
        $('#AdminChangePasswordForm_Cancel').click(hideAdminChangePassword);
        $('#AdminChangePasswordForm_Submit').click(doAdminChangePassword);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the admin change password dialog
     */
    function hideAdminChangePassword(e) {
        $('#popupMask, #adminChangePasswordDialog').fadeOut();
        
        $('#adminChangePasswordForm input').val('');
        $('#adminChangePasswordForm label.error').remove();
        
        $('#popupMask').unbind('click', hideAdminChangePassword);
        $('#AdminChangePasswordForm_Cancel').unbind('click', hideAdminChangePassword);
        $('#AdminChangePasswordForm_Submit').unbind('click', doAdminChangePassword);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Performs the admin change password action
     * @param {Event} e Event Data
     */
    function doAdminChangePassword(e) {
        if($('#adminChangePasswordForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        if($('#AdminChangePasswordForm_NewPassword').val()!=$('#AdminChangePasswordForm_ConfirmPassword').val()) {
            showNotification('Passwords do not match', 'error');
            
            e.stopPropagation();
            return false;
        }
        
        var response=server.changeUserPassword({id: $('#ManageUserAccountsTable input[name=selectedUser]').val(), password: $('#AdminChangePasswordForm_NewPassword').val()});
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            
            e.stopPropagation();
            return false;
        }else {
            hideAdminChangePassword();
            showNotification("User's password changed successfully", 'good');
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Shows the admin change password dialog
     */
    function onManageUserAddAccount(e) {
        $('#popupMask, #addUserDialog').fadeIn();
        $('#AdminAddUserForm_Username').focus();
        
        $('#popupMask').click(hideAdminAddAccount);
        $('#AdminAddUserForm_Cancel').click(hideAdminAddAccount);
        $('#AdminAddUserForm_Submit').click(doAdminAddAccount);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the admin change password dialog
     */
    function hideAdminAddAccount(e) {
        $('#popupMask, #addUserDialog').fadeOut();
        
        $('#adminAddUserForm input').val('');
        $('#adminAddUserForm label.error').remove();
        
        $('#popupMask').unbind('click', hideAdminAddAccount);
        $('#AdminAddUserForm_Cancel').unbind('click', hideAdminAddAccount);
        $('#AdminAddUserForm_Submit').unbind('click', doAdminAddAccount);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Performs the admin create user action
     * @param {Event} e Event Data
     */
    function doAdminAddAccount(e) {
        if($('#adminAddUserForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        if($('#AdminAddUserForm_Password').val()!=$('#AdminAddUserForm_ConfirmPassword').val()) {
            showNotification('Passwords do not match', 'error');
            
            e.stopPropagation();
            return false;
        }
        
        var response=server.createUser({username: $('#AdminAddUserForm_Username').val(), password: $('#AdminAddUserForm_Password').val()});
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            
            e.stopPropagation();
            return false;
        }else {
            hideAdminAddAccount();
            showNotification("User added successfully", 'good');
            onManageUserListTypeChange(e);
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**** Manage Languages Screen ****/
    /**
     * Performs actions to be called when the manage user accounts screen is shown
     */
    function manageLanguagesScreenShow() {
        $('#sidebarBlocker').show();
        
        //Load Languages
        adminManageLanguagesReload();
        
        
        //Enable fields
        $('#ManageLanguagesForm_AddLanguage').attr('disabled', '');
        
        //Listeners
        $('#ManageLanguagesTable tbody label').live('click', onManageLanguageClick);
        $('#ManageLanguagesForm_DeleteLanguage').click(onManageLanguageDelete);
        //$('#ManageUserAccountsForm_CUPUser').click(onManageUserChangePW);
        $('#ManageLanguagesForm_EditLanguage').click(onManageLanguageEditLanguage);
        $('#ManageLanguagesForm_AddLanguage').click(onManageLanguageAddLanguage);
    }
    
    /**
     * Performs actions to be called when the manage user accounts screen is hidden
     */
    function manageLanguagesScreenHide() {
        $('#sidebarBlocker').hide();
        
        //Cleanup
        $('#ManageLanguagesTable tbody tr').remove();
        $('#ManageLanguagesScreen button').attr('disabled','disabled');
        
        //Disable fields
        $('#ManageLanguagesTable tbody label').die('click', onManageLanguageClick);
        $('#ManageLanguagesForm_DeleteLanguage').unbind('click', onManageLanguageDelete);
        //$('#ManageUserAccountsForm_CUPUser').unbind('click', onManageUserChangePW);
        $('#ManageLanguagesForm_EditLanguage').unbind('click', onManageLanguageEditLanguage);
        $('#ManageLanguagesForm_AddLanguage').unbind('click', onManageLanguageAddLanguage);
    }
    
    function adminManageLanguagesReload() {
        $('#ManageLanguagesTable tbody tr').remove();
        
        var response=server.getAdminLanguages();
        
        checkLogin(response);//Session Check
        
        for(var i=0;i<response.data.length;i++) {
            var language=response.data[i];
            
            $('#ManageLanguagesTable tbody').append($('<tr>'+
                '<td><input type="radio" name="selectedLanguage" id="SelectedLanguage_'+language.id+'" value="'+language.id+'"/><label for="SelectedLanguage_'+language.id+'">'+language.language+'</label></td>'+
                '<td><label for="SelectedLanguage_'+language.id+'">'+language.file_extension+'</label></td>'+
                '<td><label for="SelectedLanguage_'+language.id+'">'+(language.user_language==0 ? 'No':'Yes')+'</label></td>'+
                '<td><label for="SelectedLanguage_'+language.id+'">'+language.snippetCount+'</label></td>'+
            '</tr>'));
        }
    }
    
    /**
     * Handles when the user clicks on a language
     * @param {Event} e Event Data
     */
    function onManageLanguageClick(e) {
        $('#ManageLanguagesTable tbody tr').removeClass('selected');
        $(this).parent().parent().addClass('selected');
        
        $('#ManageLanguagesForm_DeleteLanguage').attr('disabled', '');
        $('#ManageLanguagesForm_EditLanguage').attr('disabled', '');
    }
    
    /**
     * Shows the admin add language dialog
     */
    function onManageLanguageAddLanguage(e) {
        $('#popupMask, #adminAddLanguageDialog').fadeIn();
        $('#AdminAddLanguageForm_Password').focus();
        
        $('#popupMask').click(hideAdminAddLanguage);
        $('#AdminAddLanguageForm_Cancel').click(hideAdminAddLanguage);
        $('#AdminAddLanguageForm_Submit').click(doAdminAddLanguage);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the admin add language dialog
     */
    function hideAdminAddLanguage(e) {
        $('#popupMask, #adminAddLanguageDialog').fadeOut();
        
        $('#adminAddLanguageForm input').val('');
        $('#adminAddLanguageForm label.error').remove();
        
        $('#popupMask').unbind('click', hideAdminAddLanguage);
        $('#AdminAddLanguageForm_Cancel').unbind('click', hideAdminAddLanguage);
        $('#AdminAddLanguageForm_Submit').unbind('click', doAdminAddLanguage);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Performs the admin create user action
     * @param {Event} e Event Data
     */
    function doAdminAddLanguage(e) {
        if($('#adminAddLanguageForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        var response=server.createLanguage({language: $('#AdminAddLanguageForm_Language').val(), fileExtension: $('#AdminAddLanguageForm_FileExtension').val()});
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            
            e.stopPropagation();
            return false;
        }else {
            hideAdminAddLanguage();
            showNotification("Language added successfully", 'good');
            
            adminAddLanguagesReload();
            reloadLanguagesList();
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Handles deletion of languages
     * @param {Event} e Event Data
     */
    function onManageLanguageDelete(e) {
        if($('#ManageLanguagesTable tr.selected td:nth-child(3) label').text()=='No' || parseInt($('#ManageLanguagesTable tr.selected td:nth-child(4) label').text())!=0) {
            errorHandler({status: 'EROR', message: 'Language cannot be deleted, it is either not a user language or has snippets attached to it'});
            return;
        }
        
        if(confirm('Are you sure you want to delete the selected language?')==false) {
            e.stopPropagation();
            return false;
        }
        
        var data={id:$('#ManageLanguagesTable input[name=selectedLanguage]:checked').val()};
        var response=server.deleteLanguage(data);
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            return;
        }else {
            showNotification('Language deleted Successfully', 'good');
            
            adminManageLanguagesReload();
            reloadLanguagesList();
        }
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Shows the admin edit language dialog
     */
    function onManageLanguageEditLanguage(e) {
        if($('#ManageLanguagesTable tr.selected td:nth-child(3) label').text()=='No') {
            errorHandler({status: 'EROR', message: 'You can only edit user languages'});
            return;
        }
        
        $('#popupMask, #adminEditLanguageDialog').fadeIn();
        $('#AdminEditLanguageForm_Password').focus();
        
        
        //@TODO populate with data
        
        $('#popupMask').click(hideAdminEditLanguage);
        $('#AdminEditLanguageForm_Cancel').click(hideAdminEditLanguage);
        $('#AdminEditLanguageForm_Submit').click(doAdminEditLanguage);
        
        e.stopPropagation();
        return false;
    }
    
    /**
     * Hides the admin edit language dialog
     */
    function hideAdminEditLanguage(e) {
        $('#popupMask, #adminEditLanguageDialog').fadeOut();
        
        $('#adminEditLanguageForm input').val('');
        $('#adminEditLanguageForm label.error').remove();
        
        $('#popupMask').unbind('click', hideAdminEditLanguage);
        $('#AdminEditLanguageForm_Cancel').unbind('click', hideAdminEditLanguage);
        $('#AdminEditLanguageForm_Submit').unbind('click', doAdminEditLanguage);
        
        if(typeof(e)!='undefined') {
            e.stopPropagation();
        }
        
        return false;
    }
    
    /**
     * Performs the admin edit language action
     * @param {Event} e Event Data
     */
    function doAdminEditLanguage(e) {
        if($('#adminEditLanguageForm').valid()==false) {
            e.stopPropagation();
            return false;
        }
        
        var response=server.editLanguage({id: $('#ManageLanguagesTable input[name=selectedLanguage]:checked').val(), language: $('#AdminEditLanguageForm_Language').val(), fileExtension: $('#AdminEditLanguageForm_FileExtension').val()});
        
        checkLogin(response);//Session Check
        
        if(response.status=='EROR') {
            errorHandler(response);
            
            e.stopPropagation();
            return false;
        }else {
            hideAdminEditLanguage();
            showNotification("Language Edited successfully", 'good');
            
            adminManageLanguagesReload();
            reloadLanguagesList();
        }
        
        e.stopPropagation();
        return false;
    }
})(jQuery);