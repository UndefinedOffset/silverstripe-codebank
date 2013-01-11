if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
    if(typeof(console) != 'undefined') console.error('Class ss.i18n not defined');
}else {
    ss.i18n.addDictionary('en_US', {
                                    'CodeBank.SNIPPIT_COPIED': 'Copied snippet to clipboard',
                                    'CodeBankTree.EDIT': 'Edit',
                                    'CodeBankTree.ADD_CHILD': 'Add Child',
                                    'CodeBankTree.RENAME': 'Rename',
                                    'CodeBankTree.DELETE': 'Delete',
                                    'CodeBankTree.CONFIRM_FOLDER_DELETE': 'Are you sure you want to delete the folder "%s"?',
                                    'CodeBankTree.ERROR_DELETING_FOLDER': 'Error Deleting Folder'
                                });
}