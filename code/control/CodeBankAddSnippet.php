<?php
class CodeBankAddSnippet extends CodeBankEditSnippet {
    public static $url_segment='codeBank/add';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=62;
    public static $session_namespace='CodeBankAddSnippet';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'AddForm',
                                        'doAdd'
                                    );
    
    /**
     * Generates the form used for adding snippets
     * @return {Form} Form used to add snippets
     */
    public function AddForm() {
        $form=LeftAndMain::getEditForm();
        
        $form->setName('AddForm');
        $form->setActions(new FieldList(
                                        FormAction::create('doAdd', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')->setUseButtonTag(true)
                                    ));
        
        $form->disableDefaultAction();
        $form->addExtraClass('cms-add-form cms-edit-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        
        //Handle Language id in url
        if($this->request->getVar('LanguageID')) {
            $langField=$form->Fields()->dataFieldByName('LanguageID');
            if($langField && $langField->Value()=='') {
                $langField->setValue($this->request->getVar('LanguageID'));
            }
        }
        
        
        //Handle folder id in url (or post)
        if($this->request->getVar('FolderID')) {
            $folder=SnippetFolder::get()->byID(intval($this->request->getVar('FolderID')));
            if(!empty($folder) && $folder!==false && $folder->ID!=0) {
                $langField=$form->Fields()->dataFieldByName('LanguageID')->setValue($folder->ParentID);
                $form->Fields()->replaceField('LanguageID', $langField->performReadonlyTransformation());
                $form->Fields()->push(new HiddenField('FolderID', 'FolderID', $folder->ID));
            }
        }else if($this->request->postVar('FolderID')) {
            $folder=SnippetFolder::get()->byID(intval($this->request->postVar('FolderID')));
            if(!empty($folder) && $folder!==false && $folder->ID!=0) {
                $langField=$form->Fields()->dataFieldByName('LanguageID')->setValue($folder->ParentID);
                $form->Fields()->replaceField('LanguageID', $langField->performReadonlyTransformation());
                $form->Fields()->push(new HiddenField('FolderID', 'FolderID', $folder->ID));
            }
        }
        
        
        $this->extend('updateAddForm', $form);
        
        
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.EditForm.js');
        
        return $form;
    }
    
    /**
     * Handles adding the snippet to the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Form submitted
     */
    public function doAdd($data, Form $form) {
        $record=$this->getRecord(null);
        $form->saveInto($record);
        $record->write();
        
        
        $this->redirect('admin/codeBank/show/'.$record->ID);
        return $this->getResponseNegotiator()->respond($this->request);
    }
    
    /**
     * Gets an empty snippet to be loaded
     * @return {Snippet} Empty snippet record
     */
    public function getRecord($id) {
        return new Snippet();
    }
    
    public function Breadcrumbs($unlinked=false) {
		$defaultTitle=self::menu_title_for_class(get_class($this));
		return new ArrayList(array(
		                            new ArrayData(array(
                                        				'Title'=>_t('CodeBank.MENUTITLE', '_Code Bank'),
                                        				'Link'=>$this->LinkMain
                                        			)),
                        			new ArrayData(array(
                                        				'Title'=>_t("{$this->class}.MENUTITLE", $defaultTitle),
                                        				'Link'=>false
                                        			))
                        		));
	}
}
?>