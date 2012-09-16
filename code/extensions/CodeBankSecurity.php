<?php
class CodeBankSecurity extends Extension {
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    public function getCodeBankVersion() {
        return CodeBank::getVersion();
    }
}
?>