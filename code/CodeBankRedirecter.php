<?php
class CodeBankRedirecter extends Controller {
    public function init() {
        parent::init();
        
        $this->redirect('admin');
    }
}
?>