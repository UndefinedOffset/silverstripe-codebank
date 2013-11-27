<?php
class CodeBankSecurity extends Extension {
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    final public function getCodeBankVersion() {
        if(CB_VERSION=='@@VERSION@@') {
            return _t('CodeBank.DEVELOPMENT_BUILD', '_Development Build');
        }
        
        return CB_VERSION.' '.CB_BUILD_DATE;
    }
}
?>