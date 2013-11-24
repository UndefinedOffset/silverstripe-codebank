<?php
class CodeBankEditSnippet extends CodeBank {
    private static $url_segment='codeBank/edit';
    private static $url_rule='/$Action/$ID/$OtherID';
    private static $url_priority=61;
    private static $session_namespace='CodeBankEditSnippet';
    
    private static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    private static $allowed_actions=array(
                                        'index',
                                        'tree',
                                        'show',
                                        'EditForm',
                                        'moveSnippet',
                                		'savetreenode',
                                		'getsubtree',
                                		'moveSnippet'
                                    );
    
    /**
     * Gets the form used for viewing snippets
     * @param {int} $id ID of the record to fetch
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used
     */
    public function getEditForm($id=null, $fields=null) {
        if(!$id) {
            $id=$this->currentPageID();
        }
        
        
        $record=$this->getRecord($id);
        if($record && !$record->canView()) {
            return Security::permissionFailure($this);
        }
        
        
        
        if($record) {
            $fields=$record->getCMSFields();
            $actions=new FieldList(
                                    FormAction::create('doSave', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept'),
                                    FormAction::create('doCancel', _t('CodeBank.CANCEL', '_Cancel'))
                                );
            
            
            if($record->canDelete()) {
                $actions->push(FormAction::create('doDelete', _t('CodeBank.DELETE', '_Delete'))->addExtraClass('ss-ui-action-destructive'));
            }
            
            
            // Use <button> to allow full jQuery UI styling
            $actionsFlattened=$actions->dataFields();
            if($actionsFlattened) {
                foreach($actionsFlattened as $action) {
                    if($action instanceof FormAction) {
                        $action->setUseButtonTag(true);
                    }
                }
            }
            
            
            if($record->hasMethod('getCMSValidator')) {
                $validator=$record->getCMSValidator();
            }else {
                $validator=new RequiredFields();
            }
            
            
            $fields->push(new HiddenField('ID', 'ID'));
            
            $form=CMSForm::create($this, 'EditForm', $fields, $actions, $validator)->setHTMLID('Form_EditForm');
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setResponseNegotiator($this->getResponseNegotiator());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
            
            
            $this->extend('updateEditForm', $form);
            
            
            //Display message telling user to run dev/build because the version numbers are out of sync
            if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
                $form->insertBefore(new LiteralField('<p class="message error">'._t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')).'</p>'), 'LanguageID');
            }else if($this->hasOldTables()) {
                $form->insertBefore(new LiteralField('<p class="message warning">'._t('CodeBank.MIGRATION_AVAILABLE', '_It appears you are upgrading from Code Bank 2.2.x, your old data can be migrated {startlink}click here to begin{endlink}, though it is recommended you backup your database first.', array('startlink'=>'<a href="dev/tasks/CodeBankLegacyMigrate">', 'endlink'=>'</a>')).'</p>'), 'LanguageID');
            }
            
            $form->Actions()->push(new LiteralField('CodeBankVersion', '<p class="codeBankVersion">Code Bank: '.$this->getVersion().'</p>'));
            
            
            Requirements::javascript(CB_DIR.'/javascript/CodeBank.EditForm.js');
            
            return $form;
        }
        
        $form=$this->EmptyForm();
        if(Session::get('CodeBank.deletedSnippetID')) {
            $form->Fields()->push(new HiddenField('ID', 'ID', Session::get('CodeBank.deletedSnippetID')));
        }
        
        
        //Display message telling user to run dev/build because the version numbers are out of sync
        if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
            $form->push(new LiteralField('<p class="message error">'._t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')).'</p>'));
        }else if($this->hasOldTables()) {
            $form->push(new LiteralField('<p class="message warning">'._t('CodeBank.MIGRATION_AVAILABLE', '_It appears you are upgrading from Code Bank 2.2.x, your old data can be migrated {startlink}click here to begin{endlink}, though it is recommended you backup your database first.', array('startlink'=>'<a href="dev/tasks/CodeBankLegacyMigrate">', 'endlink'=>'</a>')).'</p>'));
        }
        
        $this->redirect('admin/codeBank/');
        return $form;
    }
    
    /**
     * Returns the link to view/edit snippets
     * @return {string} Link to view/edit snippets
     */
    public function getEditLink() {
        return 'admin/codeBank/edit/show/'.$this->currentPageID();
    }
    
    /**
     * Saves the snippet to the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
    public function doSave($data, Form $form) {
        $record=$this->currentPage();
        
        if($record->canEdit()) {
            $form->saveInto($record);
            $record->write();
            
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.SNIPPET_SAVED', '_Snippet has been saved')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
        
        return $this->getResponseNegotiator()->respond($this->request);
    }
    
    /**
     * Deletes the snippet from the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
    public function doDelete($data, Form $form) {
        $record=$this->currentPage();
    
        if($record->canDelete()) {
            Session::set('CodeBank.deletedSnippetID', $record->ID);
            $record->delete();
    
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.SNIPPET_DELETED', '_Snippet has been deleted')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
        
        $this->redirect('admin/codeBank/');
        return $this->getResponseNegotiator()->respond($this->request);
    }
}
?>