<?php
class CodeBankEditSnippet extends CodeBank {
    public static $url_segment='codeBank/edit';
    public static $session_namespace='CodeBankEditSnippet';
    
    public static $allowed_actions=array(
                                        'index',
                                        'tree',
                                        'show',
                                        'EditForm',
                                        'clear'
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
        
        
        $form=LeftAndMain::getEditForm($id);
        
        
        // TODO Duplicate record fetching (see parent implementation)
        $record=$this->getRecord($id);
        if($record && !$record->canView()) {
            return Security::permissionFailure($this);
        }
        
        
        if(!$fields) {
            $fields=$form->Fields();
        }
        
        
        $actions=$form->Actions();
        
        
        if($record) {
            $fields->push($idField=new HiddenField("ID", false, $id));
            $actions=new FieldList(
                                    FormAction::create('doSave', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                                );
            
            
            if($record->canDelete()) {
                $actions->insertBefore(FormAction::create('doDelete', _t('CodeBank.DELETE', '_Delete'))->addExtraClass('ss-ui-action-destructive'), 'action_doSave');
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
            
            $form=new Form($this, 'EditForm', $fields, $actions, $validator);
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            // TODO Can't merge $FormAttributes in template at the moment
            $form->addExtraClass('center '.$this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
            
            
            $this->extend('updateEditForm', $form);
            
            
            Requirements::javascript('CodeBank/javascript/CodeBank.EditForm.js');
            
            return $form;
        }
        
        $this->redirect('admin/codeBank/show');
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
            $record->delete();
    
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.SNIPPET_DELETED', '_Snippet has been deleted')));
        }else {
            $this->response->addHeader('X-Status', rawurlencode(_t('CodeBank.PERMISSION_DENIED', '_Permission Denied')));
        }
    
        return $this->getResponseNegotiator()->respond($this->request);
    }
}
?>