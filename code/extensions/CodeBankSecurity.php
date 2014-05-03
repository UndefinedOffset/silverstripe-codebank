<?php
class CodeBankSecurity extends Extension {
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    final public function getCodeBankVersion() {
        return singleton('CodeBank')->getVersion();
    }
}
?>