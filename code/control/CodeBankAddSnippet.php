<?php
class CodeBankAddSnippet extends CodeBank
{
    private static $url_segment='codeBank/add';
    private static $url_rule='/$Action/$ID/$OtherID';
    private static $url_priority=62;
    private static $session_namespace='CodeBankAddSnippet';
    
    private static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    private static $allowed_actions=array(
                                        'AddForm',
                                        'doAdd'
                                    );
    
    
    /**
     * Generates the form used for adding snippets
     * @return {Form} Form used to add snippets
     */
    public function AddForm()
    {
        $sng=singleton('Snippet');
        $fields=$sng->getCMSFields();
        $validator=$sng->getCMSValidator();
        
        $actions=new FieldList(
                                FormAction::create('doAdd', _t('CodeBank.CREATE', '_Create'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')->setUseButtonTag(true)
                            );
        
        $form=CMSForm::create($this, 'AddForm', $fields, $actions)->setHTMLID('Form_AddForm');
        $form->setValidator($validator);
        $form->disableDefaultAction();
        $form->addExtraClass('cms-add-form cms-edit-form');
        $form->setResponseNegotiator($this->getResponseNegotiator());
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        
        //Handle Language id in url
        if ($this->request->getVar('LanguageID')) {
            $langField=$form->Fields()->dataFieldByName('LanguageID');
            if ($langField && $langField->Value()=='') {
                $langField->setValue(intval(str_replace('language-', '', $this->request->getVar('LanguageID'))));
            }
        }
        
        
        //Handle folder id in url (or post)
        if ($this->request->getVar('FolderID')) {
            $folder=SnippetFolder::get()->byID(intval($this->request->getVar('FolderID')));
            if (!empty($folder) && $folder!==false && $folder->ID!=0) {
                $langField=$form->Fields()->dataFieldByName('LanguageID')->setValue($folder->ParentID);
                $form->Fields()->replaceField('LanguageID', $langField->performReadonlyTransformation());
                $form->Fields()->push(new HiddenField('FolderID', 'FolderID', $folder->ID));
            }
        } elseif ($this->request->postVar('FolderID')) {
            $folder=SnippetFolder::get()->byID(intval($this->request->postVar('FolderID')));
            if (!empty($folder) && $folder!==false && $folder->ID!=0) {
                $langField=$form->Fields()->dataFieldByName('LanguageID')->setValue($folder->ParentID);
                $form->Fields()->replaceField('LanguageID', $langField->performReadonlyTransformation());
                $form->Fields()->push(new HiddenField('FolderID', 'FolderID', $folder->ID));
            }
        }
        
        
        $this->extend('updateAddForm', $form);
        
        
        //Display message telling user to run dev/build because the version numbers are out of sync
        if (CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
            $form->insertBefore(new LiteralField('<p class="message error">'._t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')).'</p>'), 'LanguageID');
        } elseif ($this->hasOldTables()) {
            $form->insertBefore(new LiteralField('<p class="message warning">'._t('CodeBank.MIGRATION_AVAILABLE', '_It appears you are upgrading from Code Bank 2.2.x, your old data can be migrated {startlink}click here to begin{endlink}, though it is recommended you backup your database first.', array('startlink'=>'<a href="dev/tasks/CodeBankLegacyMigrate">', 'endlink'=>'</a>')).'</p>'), 'LanguageID');
        }
        
        $form->Actions()->push(new LiteralField('CodeBankVersion', '<p class="codeBankVersion">Code Bank: '.$this->getVersion().'</p>'));
        
        
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.EditForm.js');
        
        return $form;
    }
    
    /**
     * Handles adding the snippet to the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Form submitted
     */
    public function doAdd($data, Form $form)
    {
        $record=$this->getRecord(null);
        $form->saveInto($record);
        $record->write();
        
        $editController=singleton('CodeBank');
        $editController->setCurrentPageID($record->ID);
        
        return $this->redirect(Controller::join_links(singleton('CodeBank')->Link('show'), $record->ID));
    }
    
    /**
     * Gets an empty snippet to be loaded
     * @return {Snippet} Empty snippet record
     */
    public function getRecord($id)
    {
        return new Snippet();
    }
    
    public function Breadcrumbs($unlinked=false)
    {
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
