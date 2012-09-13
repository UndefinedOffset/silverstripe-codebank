<?php
require_once(dirname(__FILE__).'/func.inc.php');

//Set error handlers
//set_error_handler('error_handler');
//set_exception_handler('exception_handler');

//Disable display errors
ini_set('display_errors','false');
ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).'/external');

if(!defined('JSON_SERVER')) {
    //Include the Zend AMF Framework
    require_once('Zend/Amf/Server.php');
}else {
    //Include the Zend Json Framework
    require_once('Zend/Json/Server.php');
}

/**
 * Auto loads AMF classes in the amf dir and sets the classes
 * @param {Zend_AMF_Server} $server Zend Server instance
 */
function server_setup(&$server) {
    //Get a directory list of the amf folder
    $dir=scandir(dirname(__FILE__).'/amf');
    
    foreach($dir as $file) {
        if(!is_dir(dirname(__FILE__).'/amf/'.$file) && preg_match('/\.amf\.php$/',$file)==true) {
            //Require the file
            require_once(dirname(__FILE__).'/amf/'.$file);
            
            //Set the class to be active in the server
            $server->setClass(str_replace('.amf.php','',ucfirst($file)));
        }
    }
}

/**
 * Gets the base response array
 * @return {array} Default response array
 */
function responseBase() {
	$responseBase=array (
						'session'=>'',
						'login'=>false,
						'status'=>'',
						'message'=>'',
						'data'=>array()
                );

	$responseBase['session']=(checkLogin()===false ? 'expired':'valid');
	
	return $responseBase;
}

class ServerController {
    public function connect() {
        $response=responseBase();

        $response['login']=true;
        $response['data']=array(CB_VERSION,CB_BUILD_DATE);
        return $response;
    }

    public function getSessionId() {
        $response=responseBase();

        $response['data']=session_id();

        return $response;
    }
}
?>