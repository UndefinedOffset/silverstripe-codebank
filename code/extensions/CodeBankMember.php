<?php
class CodeBankMember extends DataExtension {
    private static $db=array(
                            'UseHeartbeat'=>'Boolean'
                         );
    
    private static $defaults=array(
                                    'UseHeartbeat'=>false
                                );
    
    /**
     * Updates the CMS fields adding the fields defined in this extension
     * @param {FieldList} $fields Field List that new fields will be added to
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.Main', new CheckboxField('UseHeartbeat', _t('CodeBankMember.USE_HEARTBEAT', '_Use Code Bank Heartbeat to keep client session alive?')));
    }
}
?>