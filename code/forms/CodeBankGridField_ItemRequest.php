<?php
class CodeBankGridField_ItemRequest extends GridFieldDetailForm_ItemRequest {
    private static $allowed_actions=array(
                                        'ItemEditForm'
                                    );
    
    public function ItemEditForm() {
        $form=parent::ItemEditForm();
        
        $form->Actions()->push(new LiteralField('CodeBankVersion', '<p class="codeBankVersion">Code Bank: '.$this->getVersion().'</p>'));
        
        return $form;
    }
    
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    final protected function getVersion() {
        if(CB_VERSION=='@@VERSION@@') {
            return _t('CodeBank.DEVELOPMENT_BUILD', '_Development Build');
        }
        
        return CB_VERSION.' '.CB_BUILD_DATE;
    }
}
?>