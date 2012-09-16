<?php
class CodeBank extends LeftAndMain {
    public static $url_segment='codeBank';
    
    public function init() {
        parent::init();
        
        Requirements::css('CodeBank/css/CodeBank.css');
    }
    
    /**
     * @param Int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id=null, $fields=null) {
        $fields=new FieldList(
                            $root=new TabSet('Root',
                                        new Tab('Snippets', _t('CodeBank.SNIPPETS', '_Snippets'),
                                                GridField::create('Snippets', 'Snippets', Snippet::get(), GridFieldConfig_RecordEditor::create(10))
                                            ),
                                        new Tab('Config', _t('CodeBank.CONFIG', '_Settings'),
                                                new HiddenField('Contents', 'Contents')
                                            )
                                    )
                        );
        
        $root->setTemplate('CMSTabSet');
        
        
        $actions=new FieldList();
        
        
        $form=new Form($this, "EditForm", $fields, $actions);
        $form->addExtraClass('cms-edit-form');
        $form->addExtraClass('center ss-tabset cms-tabset '.$this->BaseCSSClasses());
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
        $form->setAttribute('data-pjax-fragment', 'CurrentForm');
        
        return $form;
    }
    
    /**
     * Gets the current version of Code Bank
     * @return {string} Version Number Plus Build Date
     */
    public static function getVersion() {
        if(CB_VERSION=='@@VERSION@@') {
            return _t('CodeBank.DEVELOPMENT_BUILD', '_Development Build');
        }
        
        return CB_VERSION.' '.CB_BUILD_DATE;
    }
}

class CodeBank_ClientAPI extends Controller {
    public function init() {
        parent::init();
        
        ContentNegotiator::disable();
    }
    
    /**
     * Handles all amf requests
     */
    public function index() {
        //Start the server
        $server=new CodeBankAMFServer();
        
        //Initialize the server classes
        $classes=ClassInfo::implementorsOf('CodeBank_APIClass');
        foreach($classes as $class) {
            //Set the class to be active in the server
            $server->setClass($class);
        }
        
        //Enable Service Browser if in dev mode
        if(Director::isDev()) {
            $server->setClass('ZendAmfServiceBrowser');
            ZendAmfServiceBrowser::$ZEND_AMF_SERVER=$server;
        }
        
        $server->setProduction(!Director::isDev()); //Server debug, bind to opposite of Director::isDev()
        
        //Start the response
        $response=$server->handle();
        
        //Output
        echo $response;
        
        //Save session and exit
        Session::save();
        exit;
    }
    
    /**
     * Gets the base response array
     * @return {array} Default response array
     */
    public static function responseBase() {
        $responseBase=array(
                            'session'=>'',
                            'login'=>false,
                            'status'=>'',
                            'message'=>'',
                            'data'=>array()
                        );
        
        $responseBase['session']=(Member::currentUserID()==0 ? 'expired':'valid');
        
        return $responseBase;
    }
}

interface CodeBank_APIClass {}
?>