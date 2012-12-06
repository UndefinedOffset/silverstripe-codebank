<?php
class CodeBankSettings extends CodeBank {
    public static $url_segment='codeBank/settings';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=63;
    public static $session_namespace='CodeBankSettings';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'EditForm'
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
                                FormAction::create('doExportToClient', _t('CodeBank.EXPORT_TO_CLIENT', '_Export To Desktop Client'))->setAttribute('data-exporturl', Director::absoluteURL('code-bank-api/export-to-client'))->setAttribute('data-icon', 'export')
                            );
        
		$form=new Form($this, 'EditForm', $fields, $actions);
		$form->addExtraClass('root-form');
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		// don't add data-pjax-fragment=CurrentForm, its added in the content template instead

		//if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($config);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		// Use <button> to allow full jQuery UI styling
		$actions = $actions->dataFields();
		if($actions) foreach($actions as $action) $action->setUseButtonTag(true);

		$this->extend('updateEditForm', $form);

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
