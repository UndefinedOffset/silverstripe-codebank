<?php
if(file_exists(dirname(__FILE__).'/database.php') && basename($_SERVER['PHP_SELF'])!='install.php') {
    require_once(dirname(__FILE__).'/database.php');
}else if(basename($_SERVER['PHP_SELF'])!='install.php' && basename($_SERVER['PHP_SELF'])!='update.php') {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Could not find database config, please re-install the code bank server or run update.php';
    exit;
}

//Paths
define('CB_ADMIN_DIR',substr(str_replace('\config','',str_replace('/config','',dirname(__FILE__))),(strrpos(str_replace('\config','',str_replace('/config','',dirname(__FILE__))),'\\')===false ? strrpos(str_replace('\config','',str_replace('/config','',dirname(__FILE__))),'/'):strrpos(str_replace('\config','',str_replace('/config','',dirname(__FILE__))),'\\'))+1));
define('CB_ADMIN_DIR_ROOT',substr(dirname(__FILE__),0,strpos(dirname(__FILE__),CB_ADMIN_DIR)+strlen(CB_ADMIN_DIR)).'/');
define('CB_ADMIN_HTTP_ROOT','http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],CB_ADMIN_DIR)+strlen(CB_ADMIN_DIR)).'/');

define('CB_BUILD_DATE','@@BUILD_DATE@@');
define('CB_VERSION','@@VERSION@@');
?>