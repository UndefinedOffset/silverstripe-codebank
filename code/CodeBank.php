<?php
class CodeBank extends LeftAndMain {
    public static $url_segment='codeBank';
    
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
        $server=new Zend_Amf_Server();
        
        //Initialize the server classes
        $classes=ClassInfo::implementorsOf('CodeBank_APIClass');
        foreach($classes as $class) {
            //Set the class to be active in the server
            $server->setClass($class);
        }
        
        $server->setProduction(Director::isDev()); //Server debug, bind to Director::isDev()
        
        //Start the response
        $response=$server->handle();
        
        //Output
        echo $response;
        
        //Save session and exit
        Session::save();
        exit;
    }
}

interface CodeBank_APIClass {}
?>