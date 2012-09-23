<?php
class CodeBankEditSnippet extends CodeBank {
    public static $url_segment='codeBank/edit';
    public static $session_namespace='CodeBankEditSnippet';
    
    public static $allowed_actions=array(
                                        'tree',
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
        }else if($id) {
            return new Form($this, 'EditForm', new FieldList(
                                                            new LabelField('DoesntExistLabel', _t('CodeBank.SNIPPIT_NOT_EXIST', '_Snippit does not exist'))
                                                        ), new FieldList());
        }
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
     * Returns the link to view/edit snippets
     * @return {string} Link to view/edit snippets
     */
    public function getEditLink() {
        $parentLink=parent::Link('show');
        return Controller::join_links($parentLink, $this->currentPageID());
    }
    
    /**
     * Returns the link to settings
     * @return {string} Link to settings
     */
    public function getLinkSettings() {
        return parent::Link('settings');
    }

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$defaultTitle=LeftAndMain::menu_title_for_class('CodeBank');
		$title=_t('CodeBank.MENUTITLE', $defaultTitle);
		$items=new ArrayList(array(
			new ArrayData(array(
				'Title'=>$title,
				'Link'=>($unlinked ? false:'admin/codeBank/clear')
			))
		));
		$record = $this->currentPage();
		if($record && $record->exists()) {
			if($record->hasExtension('Hierarchy')) {
				$ancestors = $record->getAncestors();
				$ancestors = new ArrayList(array_reverse($ancestors->toArray()));
				$ancestors->push($record);
				foreach($ancestors as $ancestor) {
					$items->push(new ArrayData(array(
						'Title' => $ancestor->Title,
						'Link' => ($unlinked) ? false : Controller::join_links($this->Link('show'), $ancestor->ID)
					)));
				}
			} else {
				$items->push(new ArrayData(array(
					'Title' => $record->Title,
					'Link' => ($unlinked) ? false : Controller::join_links($this->Link('show'), $record->ID)
				)));
			}
		}

		return $items;
	}
}
?>