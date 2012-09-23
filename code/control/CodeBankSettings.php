<?php
class CodeBankSettings extends CodeBank {
    public static $url_segment='codeBank/settings';
    public static $session_namespace='CodeBankEditSnippet';
    
    /**
     * Generates the edit form
     * @param {int} $id ID of the object to edit
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used as the edit form
     */
    public function getEditForm($id=null, $fields=null) {
        if(!$id) {
            $id=$this->currentPageID();
        }
    
        $form=parent::getEditForm($id);
    
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
    
            // getAllCMSActions can be used to completely redefine the action list
            if($record->hasMethod('getAllCMSActions')) {
                $actions=$record->getAllCMSActions();
            }else {
                $actions=$record->getCMSActions();
            }
    
            // Use <button> to allow full jQuery UI styling
            $actionsFlattened=$actions->dataFields();
            if($actionsFlattened) {
                foreach($actionsFlattened as $action) {
                    $action->setUseButtonTag(true);
                }
            }
    
            if($record->hasMethod('getCMSValidator')) {
                $validator=$record->getCMSValidator();
            }else {
                $validator=new RequiredFields();
            }
    
            $form = new Form($this, "EditForm", $fields, $actions, $validator);
            $form->loadDataFrom($record);
            $form->disableDefaultAction();
            $form->addExtraClass('cms-edit-form');
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            // TODO Can't merge $FormAttributes in template at the moment
            $form->addExtraClass('center ' . $this->BaseCSSClasses());
            $form->setAttribute('data-pjax-fragment', 'CurrentForm');
    
            if(!$record->canEdit()) {
                $readonlyFields=$form->Fields()->makeReadonly();
                $form->setFields($readonlyFields);
            }
    
            $this->extend('updateEditForm', $form);
            return $form;
        }else if($id) {
            return new Form($this, 'EditForm', new FieldList(
                    new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
            ), new FieldList());
        }
    }
    
    /**
     * Returns the link to view/edit snippets
     * @return {string} Link to view/edit snippets
     */
    public function getEditLink() {
        Session::clear(CodeBank::$session_namespace.'.currentPage');
        
        return parent::Link();
    }
    
    /**
     * Returns the link to settings
     * @return {string} Link to settings
     */
    public function getLinkSettings() {
        return parent::Link('settings');
    }
}
?>
