<?php
class CodeBankIPAgreement extends CodeBank {
    public static $url_segment='codeBank/agreement';
    public static $url_rule='/$Action/$ID/$OtherID';
    public static $url_priority=64;
    public static $session_namespace='CodeBankIPAgreement';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
    public static $allowed_actions=array(
                                        'EditForm'
                                    );
    
    
    /**
     * Initializes the code bank admin
     */
    public function init() {
        LeftAndMain::init();
    
        Requirements::css(CB_DIR.'/css/CodeBank.css');
        Requirements::customScript("var CB_DIR='".CB_DIR."';", 'cb_dir');
        
        if(empty(CodeBankConfig::CurrentConfig()->IPMessage) || Session::get('CodeBankIPAgreed')===true) {
            $this->redirect('admin/codeBank');
        }
    }
    
    /**
     * Gets the form used for agreeing or disagreeing to the ip agreement
     * @param {int} $id ID of the record to fetch
     * @param {FieldList} $fields Fields to use
     * @return {Form} Form to be used
     */
    public function getEditForm($id=null, $fields=null) {
        $defaultPanel=Config::inst()->get('AdminRootController', 'default_panel');
        if($defaultPanel=='CodeBank') {
            $defaultPanel='SecurityAdmin';
            $sng=singleton($defaultPanel);
        }
        
        
        $fields=new FieldList(
                            new TabSet('Root',
                                            new Tab('Main',
                                                        new HeaderField('IPMessageTitle', _t('CodeBank.IP_MESSAGE_TITLE', '_You must agree to the following terms before using Code Bank'), 2),
                                                        new LiteralField('IPMessage', '<div class="ipMessage"><div class="middleColumn">'.CodeBankConfig::CurrentConfig()->dbObject('IPMessage')->forTemplate().'</div></div>'),
                                                        new HiddenField('RedirectLink', 'RedirectLink', $sng->Link())
                                                    )
                                        )
                        );
        
        
        if(Session::get('CodeBankIPAgreed')===true) {
            $fields->addFieldToTab('Root.Main', new HiddenField('AgreementAgreed', 'AgreementAgreed', Session::get('CodeBankIPAgreed')));
        }
        
        
        $actions=new FieldList(
                                FormAction::create('doDisagree', _t('CodeBankIPAgreement.DISAGREE', '_Disagree'))->addExtraClass('ss-ui-action-destructive'),
                                FormAction::create('doAgree', _t('CodeBankIPAgreement.AGREE', '_Agree'))->addExtraClass('ss-ui-action-constructive')
                            );
        
        $form=new Form($this, 'EditForm', $fields, $actions);
        $form->disableDefaultAction();
        $form->addExtraClass('cms-edit-form');
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->addExtraClass('center '.$this->BaseCSSClasses());
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        
        //Display message telling user to run dev/build because the version numbers are out of sync
        if(CB_VERSION!='@@VERSION@@' && CodeBankConfig::CurrentConfig()->Version!=CB_VERSION.' '.CB_BUILD_DATE) {
            $form->setMessage(_t('CodeBank.UPDATE_NEEDED', '_A database upgrade is required please run {startlink}dev/build{endlink}.', array('startlink'=>'<a href="dev/build?flush=all">', 'endlink'=>'</a>')), 'error');
        }else if($this->hasOldTables()) {
            $form->setMessage(_t('CodeBank.MIGRATION_AVAILABLE', '_It appears you are upgrading from Code Bank 2.2.x, your old data can be migrated {startlink}click here to begin{endlink}, though it is recommended you backup your database first.', array('startlink'=>'<a href="dev/tasks/CodeBankLegacyMigrate">', 'endlink'=>'</a>')), 'warning');
        }
        
        
        Requirements::javascript(CB_DIR.'/javascript/CodeBank.IPMessage.js');
        
        return $form;
    }
    
    public function doAgree($data, Form $form) {
        Session::set('CodeBankIPAgreed', true);
        
        return $this->getResponseNegotiator()->respond($this->request);
    }
    
    /**
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked=false) {
        $defaultTitle=LeftAndMain::menu_title_for_class('CodeBankIPAgreement');
        $title=_t('CodeBankIPAgreement.MENUTITLE', $defaultTitle);
        $items=new ArrayList(array(
                                    new ArrayData(array(
                                                        'Title'=>$title,
                                                        'Link'=>($unlinked ? false:'admin/codeBank/show/'.$this->currentPageID())
                                                    ))
                                ));
        
        $record=$this->currentPage();
        if($record && $record->exists()) {
            if($record->hasExtension('Hierarchy')) {
                $ancestors=$record->getAncestors();
                $ancestors=new ArrayList(array_reverse($ancestors->toArray()));
                $ancestors->push($record);
                foreach($ancestors as $ancestor) {
                    $items->push(new ArrayData(array(
                                                    'Title'=>$ancestor->Title,
                                                    'Link'=>($unlinked ? false:Controller::join_links($this->Link('show'), $ancestor->ID))
                                                )));
                }
            }else {
                $items->push(new ArrayData(array(
                                                'Title'=>$record->Title,
                                                'Link'=>($unlinked ? false:Controller::join_links($this->Link('show'), $record->ID))
                                            )));
            }
        }

        return $items;
    }
}
?>