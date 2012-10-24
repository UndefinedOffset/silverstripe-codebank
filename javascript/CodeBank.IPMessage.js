(function($) {
    $.entwine('ss', function($) {
        $('.CodeBankIPAgreement #Form_EditForm_AgreementAgreed').entwine({
            onmatch: function(e) {
                $(this).closest('.cms-container').loadPanel('admin/codeBank');
            }
        });
        
        //Disagree
        $('.CodeBankIPAgreement #Form_EditForm_action_doDisagree').entwine({
            onclick: function(e) {
                $(this).closest('.cms-container').loadPanel($('#Form_EditForm_RedirectLink').val());
            }
        });
    });
})(jQuery);