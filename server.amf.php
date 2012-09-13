<?php
require_once(dirname(__FILE__).'/includes/amf.inc.php');

//Check for install files
/*@@if(file_exists(dirname(__FILE__).'/install.php') || file_exists(dirname(__FILE__).'/install.sql') || file_exists(dirname(__FILE__).'/update.php')) {
    header('HTTP/1.1 503 Service Unavailable');
    print 'Install files remain on the server, please remove install.php, install.css, install.sql, and update.php';
    exit;
}@@*/


session_start(); //Start the session
session_regenerate_id(); //Regenerate the session id

//Start the server
$server=new Zend_Amf_Server();

//Add the controller class
$server->setClass('ServerController');

//Initialize the server classes
server_setup($server);

$server->setProduction(false); //Server debug

//Start the response
$response=$server->handle();

//Output
echo $response;
?>