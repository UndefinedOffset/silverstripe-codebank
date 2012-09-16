<?php
class CodeBank extends LeftAndMain implements PermissionProvider {
    public static $url_segment='codeBank';
    
    public static $required_permission_codes=array(
                                                    'CODE_BANK_ACCESS'
                                                );
    
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
    /**
	 * Returns a map of permission codes to add to the dropdown shown in the Security section of the CMS.
	 * array(
	 *   'VIEW_SITE' => 'View the site',
	 * );
	 */
	public function providePermissions() {
	    return array(
	                'CODE_BANK_ACCESS'=>_t('CodeBank.ACCESS_CODE_BANK', '_Access Code Bank')
	            );
	}
}

class CodeBank_ClientAPI extends Controller {
    public static $allowed_actions=array(
                                        'index',
                                        'export_snippet'
                                    );
    
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
     * Handles exporting of snippets
     * @param {SS_HTTPRequest} $request HTTP Request Data
     */
    public function export_snippet(SS_HTTPRequest $request) {
        if($request->getVar('s')) {
            //Use the session id in the request
            Session::start($request->getVar('s'));
        }
        
        
        if(!Permission::check('CODE_BANK_ACCESS')) {
            header("HTTP/1.1 401 Unauthorized");
            
            
            //Save session and exit
            Session::save();
            exit;
        }
        
        
        try {
            $fileID=uniqId(time());
            
            $snippet=Snippet::get()->byID(intval($request->getVar('id')));
            if(empty($snippet) || $snippet===false || $snippet->ID==0) {
                header("HTTP/1.1 404 Not Found");
                
                
                //Save session and exit
                Session::save();
                exit;
            }
            
            
            //If the temp dir doesn't exist create it
            if(!file_exists(ASSETS_PATH.'/.codeBankTemp')) {
                mkdir(ASSETS_PATH.'/.codeBankTemp', 0644);
            }
            
            
            if($snippet->Language()->Name=='ActionScript 3') {
                $zip=new ZipArchive();
                
                $res=$zip->open(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip', ZIPARCHIVE::CREATE);
                
                if($res) {
                    $path='';
                    $text=preg_split("/[\n\r]/", $snippet->getSnippetText());
                    $folder=str_replace('.', '/', trim(preg_replace('/^package (.*?)((\s*)\{)?$/i', '\\1', $text[0])));
                    
                    $className=array_values(preg_grep('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i', $text));
                    
                    if(count($className)==0) {
                        throw new Exception('Class definition could not be found');
                    }
                    
                    $className=trim(preg_replace('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i','\\4', $className[0]));
                    
                    if($className=="") {
                        throw new Exception('Class definition could not be found');
                    }
                    
                    $zip->addFromString($folder.'/'.$className.'.'.$snippet->Language()->FileExtension, $snippet->getSnippetText());
                    
                    $zip->Close();
                    chmod(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip',0600);
                    
                    
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment;  filename="'.$fileID.'.zip"');
                    header('Content-Transfer-Encoding: binary');
                    
                    readfile(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip');
                    unlink(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip');
                }
            }else {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment;  filename="'.$fileID.'.'.$snippet->Language()->FileExtension.'"');
                header('Content-Transfer-Encoding: binary');
                
                print $snippet->getSnippetText();
            }
        }catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
        }
        
        
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