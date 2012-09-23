<?php
class CodeBankConfig extends DataObject {
    public static $db=array(
                            'IPMessage'=>'HTMLText',
                            'Version'=>'Varchar(30)'
                         );
    
    protected static $_currentConfig;
    
    /**
     * Creates the default code bank config
     */
    public function requireDefaultRecords() {
        parent::requireDefaultRecords();
        
        
        if(!CodeBankConfig::get()->first()) {
            $conf=new CodeBankConfig();
            $conf->Version=CB_VERSION.' '.CB_BUILD_DATE;
            $conf->write();
            
            DB::alteration_message('Default Code Bank Config Created', 'created');
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
        return new FieldList(
                            new TabSet('Root',
                                            new Tab('Main', _t('CodeBankConfig.MAIN', '_Main'),
                                                    HtmlEditorField::create('IPMessage', _t('CodeBankConfig.IP_MESSAGE', '_Intellectual Property Message'))->addExtraClass('stacked')
                                                ),
                                            new Tab('Languages', _t('CodeBankConfig.LANGUAGES', '_Languages'),
                                                    new GridField('Languages', _t('CodeBankConfig.LANGUAGES', '_Languages'), SnippetLanguage::get(), GridFieldConfig_RecordEditor::create(30))
                                                )
                                        )
                        );
    }
}
?>