<?php
class CodeBankConfig extends DataObject {
    private static $db=array(
                            'IPMessage'=>'HTMLText',
                            'Version'=>'Varchar(30)'
                         );
    
    protected static $_currentConfig;
    
    
    /**
     * Checks to see if the member can view or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can view false otherwise
     */
    public function canView($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks to see if the member can edit or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can edit false otherwise
     */
    public function canEdit($member=null) {
        if(Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks to see if the member can delete or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can delete false otherwise
     */
    public function canDelete($member=null) {
        return false;
    }
    
    /**
     * Checks to see if the member can create or not
     * @param {int|Member} $member Member ID or instance to check
     * @return {bool} Returns boolean true if the member can create false otherwise
     */
    public function canCreate($member=null) {
        return false;
    }
    
    /**
     * Creates the default code bank config
     */
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        
        $codeVersion=singleton('CodeBank')->getVersion();
        
        if(!CodeBankConfig::get()->first()) {
            $conf=new CodeBankConfig();
            $conf->Version=$codeVersion;
            $conf->write();
            
            DB::alteration_message('Default Code Bank Config Created', 'created');
        }
        
        
        if(!Group::get()->filter('Code', 'code-bank-api')->first()) {
            $group=new Group();
            $group->Title='Code Bank Users';
            $group->Description='Code Bank Access Group';
            $group->Code='code-bank-api';
            $group->write();
            
            $permission=new Permission();
            $permission->Code='CODE_BANK_ACCESS';
            $permission->Type=1;
            $permission->GroupID=$group->ID;
            $permission->write();
            
            DB::alteration_message('Code Bank Users Group Created', 'created');
        }
        
        
        //Check for and perform any needed updates
        $codeVersionTmp=explode(' ', $codeVersion);
        $dbVerTmp=explode(' ', CodeBankConfig::CurrentConfig()->Version);
        if($codeVersionTmp[0]!='@@VERSION@@' && $codeVersionTmp[0]!=$dbVerTmp[0]) {
            $updateXML=simplexml_load_string(file_get_contents('http://update.edchipman.ca/codeBank/airUpdate.xml'));
            $latestVersion=strip_tags($updateXML->version->asXML());
            $versionTmp=explode(' ', $latestVersion);
            
            //Sanity Check code version against latest
            if(version_compare($codeVersionTmp[0], $versionTmp[0], '>')) {
                DB::alteration_message('Unknown Code Bank server version '.$codeVersion.', current version available for download is '.$latestVersion, 'error');
                return;
            }
            
            //Sanity Check make sure latest version is installed
            if($codeVersionTmp[0]!=$versionTmp[0]) {
                DB::alteration_message('A Code Bank Server update is available, please <a href="http://programs.edchipman.ca/applications/code-bank/">download</a> and install the update then run dev/build again.', 'error');
                return;
            }
            
            //Sanity Check database version against latest
            if(version_compare($dbVerTmp[0], $versionTmp[0], '<')) {
	            $data=array(
	                        'version'=>CodeBankConfig::CurrentConfig()->Version,
	                        'db_type'=>'SERVER'
	                    );
	            
	            $data=http_build_query($data);
	            
	            
	            $context=stream_context_create(array(
	                                                'http'=>array(
	                                                            'method'=>'POST',
	                                                            'header'=>"Content-type: application/x-www-form-urlencoded\r\n"
	                                                                        ."Content-Length: ".strlen($data)."\r\n",
	                                                            'content'=>$data
	                                                        )
	                                            ));
	            
	            
	            //Download and run queries needed
	            $sql=simplexml_load_string(file_get_contents('http://update.edchipman.ca/codeBank/DatabaseUpgrade.php', false, $context));
	            $sets=count($sql->query);
	            foreach($sql->query as $query) {
	                $queries=explode('$',$query);
	                $t=count($queries);
	            
	                foreach($queries as $query) {
	                    if(empty($query)) {
	                        continue;
	                    }
	            
	                    DB::query($query);
	                }
	            }
	            
	            
	            //Update Database Version
	            $codeBankConfig=CodeBankConfig::CurrentConfig();
	            $codeBankConfig->Version=$latestVersion;
	            $codeBankConfig->write();
	            
	            
	            DB::alteration_message('Code Bank Server database upgraded', 'changed');
            }
        }
    }
    
    /**
     * Gets the current config
     * @return {CodeBankConfig} Code Bank Config Data
     */
    public static function CurrentConfig() {
        if(empty(self::$_currentConfig)) {
            self::$_currentConfig=CodeBankConfig::get()->first();
        }
        
        return self::$_currentConfig;
    }
    
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        $langGridConfig=GridFieldConfig_RecordEditor::create(30);
        $langGridConfig->getComponentByType('GridFieldDetailForm')->setItemRequestClass('CodeBankGridField_ItemRequest');
        $langGridConfig->getComponentByType('GridFieldDataColumns')->setFieldCasting(array(
                                                                                            'UserLanguage'=>'Boolean->Nice',
                                                                                            'Hidden'=>'Boolean->Nice'
                                                                                        ));
        
        $packageGridConfig=GridFieldConfig_RecordEditor::create(30);
        $packageGridConfig->addComponent(new ExportPackageButton());
        $packageGridConfig->getComponentByType('GridFieldDetailForm')->setItemRequestClass('CodeBankGridField_ItemRequest')->setItemEditFormCallback(function(Form $form, GridFieldDetailForm_ItemRequest $itemRequest) {
                                                                                                    Requirements::javascript(CB_DIR.'/javascript/SnippetPackages.ItemEditForm.js');
                                                                                                    
                                                                                                    if($form->getRecord() && $form->getRecord()->ID>0) {
                                                                                                    	$form->Actions()->push(FormAction::create('doExportPackage', _t('CodeBank.EXPORT', '_Export'))->setForm($form));
                                                                                                    }
                                                                                                    
                                                                                                    $form->addExtraClass('CodeBankPackages');
                                                                                                });
        
        if(Permission::check('ADMIN')) {
            $fields=new FieldList(
                                new TabSet('Root',
                                        new Tab('Main', _t('CodeBankConfig.MAIN', '_IP Message'),
                                                HtmlEditorField::create('IPMessage', _t('CodeBankConfig.IP_MESSAGE', '_Intellectual Property Message'))->addExtraClass('stacked')
                                            ),
                                        new Tab('Languages', _t('CodeBankConfig.LANGUAGES', '_Languages'),
                                                new GridField('Languages', _t('CodeBankConfig.LANGUAGES', '_Languages'), SnippetLanguage::get(), $langGridConfig)
                                            ),
                                        new Tab('Packages', _t('CodeBank.PACKAGES', '_Packages'),
                                                new GridField('Packages', _t('CodeBankConfig.MANAGE_PACKAGES', '_Manage Packages'), SnippetPackage::get(), $packageGridConfig)
                                            )
                                    )
                            );
        }else {
            $fields=new FieldList(
                                new TabSet('Root',
                                                new Tab('Packages', _t('CodeBank.PACKAGES', '_Packages'),
                                                        new GridField('Packages', _t('CodeBankConfig.MANAGE_PACKAGES', '_Manage Packages'), SnippetPackage::get(), $packageGridConfig)
                                                    )
                                            )
                            );
        }
        
        return $fields;
    }
}
?>