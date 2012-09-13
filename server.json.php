<?php
define('JSON_SERVER', true);

require_once(dirname(__FILE__).'/includes/amf.inc.php');
require_once(dirname(__FILE__).'/includes/CodeBankJsonServer.php');

//Check for install files
/*@@if(file_exists(dirname(__FILE__).'/install.php') || file_exists(dirname(__FILE__).'/install.sql') || file_exists(dirname(__FILE__).'/update.php')) {
    header('HTTP/1.1 503 Service Unavailable');
    print 'Install files remain on the server, please remove install.php, install.css, install.sql, and update.php';
    exit;
}@@*/

session_start(); //Start the session
session_regenerate_id(); //Regenerate the session id

//Start the server
$server=new CodeBankJsonServer();

//Add the controller class
$server->setClass('ServerController');

//Initialize the server classes
server_setup($server);

if($_SERVER['REQUEST_METHOD']=='GET') {
    // Indicate the URL endpoint, and the JSON-RPC version used:
    $server->setTarget('server.json.php')->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);

    // Grab the SMD
    $smd = $server->getServiceMap();

    // Return the SMD to the client
    header('Content-Type: application/json');
    echo $smd;exit;
}

$server->setProduction(true); //Server debug

//Start the response
$response=$server->handle();

//Output
echo $response;
?>