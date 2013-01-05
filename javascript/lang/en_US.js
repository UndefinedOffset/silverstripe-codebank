if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
    if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
}else {
    ss.i18n.addDictionary('en_US', {
                                    'CodeBank.SNIPPIT_COPIED': 'Copied snippet to clipboard',
                                    'CodeBankTree.EDIT': 'Edit',
                                    'CodeBankTree.ADD_SNIPPET': 'Add Snippet'
                                });
}