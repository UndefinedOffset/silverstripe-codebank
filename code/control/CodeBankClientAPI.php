<?php
class CodeBank_ClientAPI extends Controller
{
    private static $allowed_actions=array(
                                        'index',
                                        'export_package',
                                        'export_snippet',
                                        'export_to_client'
                                    );
    
    private $_testAMFRequest=false;
    
    public function init()
    {
        parent::init();
        
        ContentNegotiator::disable();
    }
    
    /**
     * Handles all amf requests
     */
    public function index()
    {
        //Ensure the session is started
        Session::start();
        
        //Start the server
        $server=new CodeBankAMFServer();
        
        //Initialize the server classes
        $classes=ClassInfo::implementorsOf('CodeBank_APIClass');
        foreach ($classes as $class) {
            //Set the class to be active in the server
            $server->setClass($class);
        }
        
        //Enable Service Browser if in dev mode
        if (Director::isDev()) {
            $server->setClass('ZendAmfServiceBrowser');
            ZendAmfServiceBrowser::$ZEND_AMF_SERVER=$server;
        }
        
        if (class_exists('CodeBankAPITest') && $this->_testAMFRequest!==false) {
            $server->setRequest($this->_testAMFRequest);
            $server->setResponse(new Zend_Amf_Response());
        }
        
        $server->setProduction(!Director::isDev()); //Server debug, bind to opposite of Director::isDev()

        //Start the response
        $response=$server->handle();
        
        
        //If not in test mode add the application/x-amf content type
        if (!class_exists('CodeBankAPITest')) {
            $this->response->addHeader('Content-Type', 'application/x-amf');
        }
        
        
        //Save session
        Session::save();
        
        //Output
        return $response;
    }
    
    /**
     * Handles exporting of snippets
     * @param {SS_HTTPRequest} $request HTTP Request Data
     */
    public function export_snippet(SS_HTTPRequest $request)
    {
        if ($request->getVar('s')) {
            //Use the session id in the request
            Session::start($request->getVar('s'));
        }
        
        
        if (!Permission::check('CODE_BANK_ACCESS')) {
            header("HTTP/1.1 401 Unauthorized");
            
            
            //Save session and exit
            Session::save();
            exit;
        }
        
        
        try {
            $snippet=Snippet::get()->byID(intval($request->getVar('id')));
            if (empty($snippet) || $snippet===false || $snippet->ID==0) {
                header("HTTP/1.1 404 Not Found");
                
                
                //Save session and exit
                Session::save();
                exit;
            }
            
            
            $urlFilter=URLSegmentFilter::create();
            $fileID=$urlFilter->filter($snippet->Title);
            
            
            //If the temp dir doesn't exist create it
            if (!file_exists(ASSETS_PATH.'/.codeBankTemp')) {
                mkdir(ASSETS_PATH.'/.codeBankTemp', Config::inst()->get('Filesystem', 'folder_create_mask'));
            }
            
            
            if ($snippet->Language()->Name=='ActionScript 3') {
                $zip=new ZipArchive();
                
                $res=$zip->open(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip', ZIPARCHIVE::CREATE);
                
                if ($res===true) {
                    $path='';
                    $text=preg_split("/[\n\r]/", $snippet->getSnippetText());
                    $folder=str_replace('.', '/', trim(preg_replace('/^package (.*?)((\s*)\{)?$/i', '\\1', $text[0])));
                    
                    $className=array_values(preg_grep('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i', $text));
                    
                    if (count($className)==0) {
                        throw new Exception('Class definition could not be found');
                    }
                    
                    $className=trim(preg_replace('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i', '\\4', $className[0]));
                    
                    if ($className=="") {
                        throw new Exception('Class definition could not be found');
                    }
                    
                    $zip->addFromString($folder.'/'.$className.'.'.$snippet->Language()->FileExtension, $snippet->getSnippetText());
                    
                    if ($zip->Close()!==false) {
                        chmod(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip', 0600);
                        
                        
                        //Send File
                        SS_HTTPRequest::send_file(file_get_contents(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip'), $fileID.'.zip', 'application/octet-stream')->output();
                        unlink(ASSETS_PATH.'/.codeBankTemp/'.$fileID.'.zip');
                    } else {
                        header("HTTP/1.1 500 Internal Server Error");
                    }
                } else {
                    header("HTTP/1.1 500 Internal Server Error");
                }
            } else {
                SS_HTTPRequest::send_file($snippet->getSnippetText(), $fileID.'.'.$snippet->Language()->FileExtension, 'text/plain')->output();
            }
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
        }
        
        
        //Save session and exit
        Session::save();
        exit;
    }
    
    /**
     * Builds a json export file for importing on the client
     */
    public function export_to_client()
    {
        if (!Permission::check('CODE_BANK_ACCESS')) {
            return Security::permissionFailure($this);
        }
        
        
        //Bump up time limit we may need it
        set_time_limit(480);
        
        
        //Dump Data
        $languages=$this->queryToArray(DB::query('SELECT "ID", "Name", "FileExtension", "HighlightCode", "UserLanguage" FROM "SnippetLanguage"'));
        $snippets=$this->queryToArray(DB::query('SELECT "ID", "Title", "Description", "Tags", "LanguageID", "PackageID", "FolderID" FROM "Snippet"'));
        $versions=$this->queryToArray(DB::query('SELECT "ID", "Created", "Text", "ParentID" FROM "SnippetVersion"'));
        $packages=$this->queryToArray(DB::query('SELECT "ID", "Title" FROM "SnippetPackage"'));
        $folders=$this->queryToArray(DB::query('SELECT "ID", "Name", "ParentID", "LanguageID" FROM "SnippetFolder"'));
        
        
        //Build final response array
        $response=array(
                        'format'=>'ToClient',
                        'data'=>array(
                                    'languages'=>$languages,
                                    'snippets'=>$snippets,
                                    'versions'=>$versions,
                                    'packages'=>$packages,
                                    'folders'=>$folders
                                )
                    );
        
        
        //Send File
        SS_HTTPRequest::send_file(json_encode($response), date('Y-m-d_hi').'.cbexport', 'application/json')->output();
        
        
        //Save session and exit
        Session::save();
        exit;
    }
    
    /**
     * Handles exporting of snippets
     * @param {SS_HTTPRequest} $request HTTP Request Data
     */
    public function export_package(SS_HTTPRequest $request)
    {
        if ($request->getVar('s')) {
            //Use the session id in the request
            Session::start($request->getVar('s'));
        }
        
        
        if (!Permission::check('CODE_BANK_ACCESS')) {
            header("HTTP/1.1 401 Unauthorized");
            
            
            //Save session and exit
            Session::save();
            exit;
        }
        
        
        try {
            $package=SnippetPackage::get()->byID(intval($request->getVar('id')));
            if (empty($package) || $package===false || $package->ID==0) {
                header("HTTP/1.1 404 Not Found");
                
                
                //Save session and exit
                Session::save();
                exit;
            }
            
            
            $urlFilter=URLSegmentFilter::create();
            $fileID=$urlFilter->filter($package->Title);
            
            
            //If the temp dir doesn't exist create it
            if (!file_exists(ASSETS_PATH.'/.codeBankTemp')) {
                mkdir(ASSETS_PATH.'/.codeBankTemp', Config::inst()->get('Filesystem', 'folder_create_mask'));
            }
            
            
            $zip=new ZipArchive();
            $res=$zip->open(ASSETS_PATH.'/.codeBankTemp/package-'.$fileID.'.zip', ZIPARCHIVE::CREATE);
            
            if ($res===true) {
                $snippets=$package->Snippets();
                
                foreach ($snippets as $snippet) {
                    $snipFileID=$urlFilter->filter($snippet->Title);
                    
                    if ($snippet->Language()->Name=='ActionScript 3') {
                        $path='';
                        $text=preg_split("/[\n\r]/", $snippet->getSnippetText());
                        $folder=str_replace('.', '/', trim(preg_replace('/^package (.*?)((\s*)\{)?$/i', '\\1', $text[0])));
                        
                        $className=array_values(preg_grep('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i', $text));
                        
                        if (count($className)==0) {
                            throw new Exception('Class definition could not be found');
                        }
                        
                        $className=trim(preg_replace('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i', '\\4', $className[0]));
                        
                        if ($className=="") {
                            throw new Exception('Class definition could not be found');
                        }
                        
                        $zip->addFromString($folder.'/'.$className.'.'.$snippet->Language()->FileExtension, $snippet->getSnippetText());
                    } else {
                        $zip->addFromString($snipFileID.'.'.$snippet->Language()->FileExtension, $snippet->getSnippetText());
                    }
                }
                
                if ($zip->Close()!==false) {
                    chmod(ASSETS_PATH.'/.codeBankTemp/package-'.$fileID.'.zip', 0600);
                    
                    
                    //Send File
                    SS_HTTPRequest::send_file(file_get_contents(ASSETS_PATH.'/.codeBankTemp/package-'.$fileID.'.zip'), $fileID.'.zip', 'application/octet-stream')->output();
                    unlink(ASSETS_PATH.'/.codeBankTemp/package-'.$fileID.'.zip');
                } else {
                    header("HTTP/1.1 500 Internal Server Error");
                }
            } else {
                header("HTTP/1.1 500 Internal Server Error");
            }
        } catch (Exception $e) {
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
    public static function responseBase()
    {
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
    
    /**
     * Implented for test only, sets the request to pass to the amf server
     * @param {Test_Amf_Request} $request Test AMF Request
     */
    public function setTestRequest(Zend_Amf_Request $request)
    {
        if (class_exists('CodeBankAPITest')) {
            $this->_testAMFRequest=$request;
        }
    }
    
    /**
     * Merges a database resultset into an array
     * @param {SS_Query} $source SS_Query containing the result set
     * @return {array} Merged array
     */
    private function queryToArray(SS_Query $source)
    {
        $result=array();
        
        foreach ($source as $row) {
            $result[]=$row;
        }
        
        return $result;
    }
}
