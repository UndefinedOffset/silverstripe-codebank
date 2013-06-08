<?php
class CodeBankSettings extends CodeBank {
    private static $url_segment='codeBank/settings';
    private static $url_rule='/$Action/$ID/$OtherID';
    private static $url_priority=63;
    private static $session_namespace='CodeBankSettings';
    
    private static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    private static $allowed_actions=array(
                                        'EditForm',
                                        'import_from_client',
                                        'ImportFromClientForm',
                                        'doImportData'
                                    );
    
    public function init() {
        parent::init();
        
        Requirements::css(CB_DIR.'/css/CodeBank.css');
        Requirements::block(CB_DIR.'/javascript/CodeBank.Tree.js');
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.Settings.js');
    }
    
    /**
     * @return {PjaxResponseNegotiator}
     *
     * @see LeftAndMain::getResponseNegotiator()
     */
    public function getResponseNegotiator() {
		$neg=parent::getResponseNegotiator();
		$controller=$this;
		$neg->setCallback('CurrentForm', function() use(&$controller) {
                                                            			return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
                                                            		});
		
		return $neg;
	}
	
	/**
	 * @return Form
	 */
	public function getEditForm($id = null, $fields = null) {
		$config=CodeBankConfig::CurrentConfig();
		$fields=$config->getCMSFields();
        $actions=new FieldList(
                                FormAction::create('doSave', _t('CodeBank.SAVE', '_Save'))->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept'),
                                FormAction::create('doExportToClient', _t('CodeBank.EXPORT_TO_CLIENT', '_Export To Desktop Client'))->setAttribute('data-exporturl', Director::absoluteURL('code-bank-api/export-to-client'))->setAttribute('data-icon', 'export'),
                                FormAction::create('doImportFromClient', _t('CodeBank.IMPORT_FROM_CLIENT', '_Import From Desktop Client'))->setAttribute('data-icon', 'import')->setAttribute('data-importurl', $this->Link('import-from-client'))
                            );
        
		$form=new Form($this, 'EditForm', $fields, $actions);
		$form->addExtraClass('root-form');
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		// don't add data-pjax-fragment=CurrentForm, its added in the content template instead
		
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($config);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		
		// Use <button> to allow full jQuery UI styling
		$actions = $actions->dataFields();
		if($actions) foreach($actions as $action) $action->setUseButtonTag(true);
		
		$this->extend('updateEditForm', $form);
		
		
		//Display message telling user to run dev/build because the version numbers are out of sync
		if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
		    $form->setMessage(_t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')), 'error');
		}else if($this->hasOldTables()) {
		    $form->setMessage(_t('CodeBank.MIGRATION_AVAILABLE', '_It appears you are upgrading from Code Bank 2.2.x, your old data can be migrated {startlink}click here to begin{endlink}, though it is recommended you backup your database first.', array('startlink'=>'<a href="dev/tasks/CodeBankLegacyMigrate">', 'endlink'=>'</a>')), 'warning');
		}
		
		
		return $form;
	}
	
	/**
     * Saves the snippet to the database
     * @param {array} $data Data submitted by the user
     * @param {Form} $form Submitting form
     * @return {SS_HTTPResponse} Response
     */
	public function doSave($data, $form) {
		$config=CodeBankConfig::CurrentConfig();
		$form->saveInto($config);
		$config->write();
		
		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));
		return $this->getResponseNegotiator()->respond($this->request);
	}
	
	/**
	 * Handles requests for the import from client popup
	 * @return {string} Rendered template
	 */
	public function import_from_client() {
	    $form=$this->ImportFromClientForm();
	    if(Session::get('reloadOnImportDialogClose')) {
	        Requirements::javascript(CB_DIR.'/javascript/CodeBank.ImportDialog.js');
	        Session::clear('reloadOnImportDialogClose');
	    }
	    
	    return $this->customise(array(
                    	            'Content'=>' ',
                    	            'Form'=>$form
	                            ))->renderWith('CMSDialog');
	}
	
	/**
	 * Form used for importing data from the client
	 * @return {Form} Form to be used in the popup
	 */
	public function ImportFromClientForm() {
	    $uploadField=new FileField('ImportFile', _t('CodeBank.EXPORT_FILE', '_Client Export File'));
	    $uploadField->getValidator()->setAllowedExtensions(array('cbexport'));
	    File::$allowed_extensions[]='cbexport';
	    
	    $fields=new FieldList(
	                        new LiteralField('ImportWarning', '<p class="message warning">'._t('CodeBank.IMPORT_DATA_WARNING', '_Warning clicking import will erase all snippets in the database, it is recommended you backup your database before proceeding').'</p>'),
	                        new TabSet('Root',
                                                new Tab('Main',
                                                                $uploadField
                                                            )
	                                        )
                        );
	    
	    $actions=new FieldList(
	                        FormAction::create('doImportData', _t('CodeBank.IMPORT', '_Import'))->addExtraClass('ss-ui-button ss-ui-action-constructive')->setAttribute('data-icon', 'accept')->setUseButtonTag(true)
	                    );
	    
	    
	    $validator=new RequiredFields(
                	                'ImportFile'
                	            );
	    
	    
	    $form=new Form($this, 'ImportFromClientForm', $fields, $actions, $validator);
	    $form->addExtraClass('member-profile-form');
	    
	    return $form;
	}
	
	/**
	 * Processes the upload request
	 * @param {array} $data Submitted data
	 * @param {Form} $form Submitting form
	 * @return {SS_HTTPResponse} Response
	 */
	public function doImportData($data, Form $form) {
	    $fileData=$form->Fields()->dataFieldByName('ImportFile')->Value();
	    //Check that the file uploaded
	    if(!array_key_exists('tmp_name', $fileData) || !file_exists($fileData['tmp_name'])) {
	        $form->sessionMessage(_t('CodeBank.IMPORT_READ_ERROR', '_Could not read the file to be imported'), 'bad');
	        return $this->redirectBack();
	    }
	    
	    
	    //Load the file into memory
	    $fileData=file_get_contents($fileData['tmp_name']);
	    if($fileData===false || empty($fileData)) {
	        $form->sessionMessage(_t('CodeBank.IMPORT_READ_ERROR', '_Could not read the file to be imported'), 'bad');
	        return $this->redirectBack();
	    }
	    
	    
	    //Decode the json
	    $fileData=json_decode($fileData);
	    if($fileData===false || !is_object($fileData)) {
	        $form->sessionMessage(_t('CodeBank.IMPORT_READ_ERROR', '_Could not read the file to be imported'), 'bad');
	        return $this->redirectBack();
	    }
	    
	    
	    //Verify the format is ToServer
	    if($fileData->format!='ToServer') {
	        $form->sessionMessage(_t('CodeBank.IMPORT_FILE_FORMAT_INCORRECT', '_Import file format is incorrect'), 'bad');
	        return $this->redirectBack();
	    }
	    
	    
	    //Bump Up the time limit this may take time
	    set_time_limit(480);
	    
	    
	    //Start transaction if supported
        if(DB::getConn()->supportsTransactions()) {
            DB::getConn()->transactionStart();
        }
        
        
        //Empty the tables
        DB::query('DELETE FROM Snippet');
        DB::query('DELETE FROM SnippetVersion');
        DB::query('DELETE FROM SnippetLanguage');
        DB::query('DELETE FROM SnippetPackage');
        DB::query('DELETE FROM SnippetFolder');
        
        
        //Import Languages
        foreach($fileData->data->languages as $lang) {
            DB::query('INSERT INTO "SnippetLanguage" ("ID", "ClassName", "Created", "LastEdited", "Name", "FileExtension", "HighlightCode", "UserLanguage") '.
                    "VALUES(".intval($lang->id).",'SnippetLanguage', '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".Convert::raw2sql($lang->language)."','".Convert::raw2sql($lang->file_extension)."','".Convert::raw2sql($lang->shjs_code)."',".intval($lang->user_language).")");
        }
        
        
        //Import Packages
        foreach($fileData->data->packages as $pkg) {
            DB::query('INSERT INTO "SnippetPackage" ("ID", "ClassName", "Created", "LastEdited", "Title") '.
                    "VALUES(".intval($pkg->id).",'SnippetPackage', '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".Convert::raw2sql($pkg->title)."')");
        }
        
        
        //Import Snippets
        foreach($fileData->data->snippets as $snip) {
            DB::query('INSERT INTO "Snippet" ("ID", "ClassName", "Created", "LastEdited", "Title", "Description", "Tags", "LanguageID", "CreatorID", "LastEditorID", "PackageID", "FolderID") '.
                    "VALUES(".intval($snip->id).",'Snippet', '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".Convert::raw2sql($snip->title)."', '".Convert::raw2sql($snip->description)."', '".Convert::raw2sql($snip->tags)."', ".intval($snip->fkLanguage).", ".Member::currentUserID().", ".Member::currentUserID().", ".intval($snip->fkPackageID).", ".intval($snip->fkFolderID).")");
        }
        
        
        //Import Snippet Versions
        foreach($fileData->data->versions as $ver) {
            DB::query('INSERT INTO "SnippetVersion" ("ID", "ClassName", "Created", "LastEdited", "Text", "ParentID") '.
                    "VALUES(".intval($ver->id).",'SnippetVersion', '".Convert::raw2sql($ver->date)."','".Convert::raw2sql($ver->date)."','".Convert::raw2sql($ver->text)."', ".intval($ver->fkSnippit).")");
        }
        
        
        //Import Folders
        foreach($fileData->data->folders as $folder) {
            DB::query('INSERT INTO "SnippetFolder" ("ID", "ClassName", "Created", "LastEdited", "Name", "ParentID", "LanguageID") '.
                    "VALUES(".intval($folder->id).",'SnippetFolder', '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."','".Convert::raw2sql($folder->name)."', ".intval($folder->fkParentId).", ".intval($folder->fkLanguageId).")");
        }
        
        
        //End transaction if supported
        if(DB::getConn()->supportsTransactions()) {
            DB::getConn()->transactionEnd();
        }
        
        
        //Display success after redirecting back
        Session::set('reloadOnImportDialogClose', true);
        $form->sessionMessage(_t('CodeBank.IMPORT_COMPLETE', '_Import Completed'), 'good');
        return $this->redirectBack();
	}
	
	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked=false) {
		$defaultTitle=self::menu_title_for_class(get_class($this));
		return new ArrayList(array(
                        			new ArrayData(array(
                                        				'Title'=>_t("{$this->class}.MENUTITLE", $defaultTitle),
                                        				'Link'=>false
                                        			))
                        		));
	}
}
?>
