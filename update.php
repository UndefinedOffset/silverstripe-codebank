<?php
require_once(dirname(__FILE__).'/includes/func.inc.php');
require_once(dirname(__FILE__).'/config/main.php');

$main=file_get_contents(dirname(__FILE__).'/config/main.php');
if(strpos($main,"define('MYSQL_SERVER'")!==false) {
    print '<i>Migrating the config format</i><br/>';
    
    //Upgrade the config
    if(!is_writable(dirname(__FILE__).'/config')) {
        print 'Cannot write to config/ please adjust the permissions of this folder and its contents';
        exit;
    }
    
    //Re-create main.php
    $f=fopen(dirname(__FILE__).'/config/main.php','w');
    fwrite($f,"<?php\n".
                "if(file_exists(dirname(__FILE__).'/database.php') && basename(\$_SERVER['PHP_SELF'])!='install.php') {\n".
                "    require_once(dirname(__FILE__).'/database.php');\n".
                "}else if(basename(\$_SERVER['PHP_SELF'])!='install.php') {\n".
                "    echo 'Could not find database config, please re-install the code bank server';\n".
                "    exit;\n".
                "}\n".
                "\n".
                "//Paths\n".
                "define('CB_ADMIN_DIR',substr(str_replace('\\config','',str_replace('/config','',dirname(__FILE__))),(strrpos(str_replace('\\config','',str_replace('/config','',dirname(__FILE__))),'\\\\')===false ? strrpos(str_replace('\\config','',str_replace('/config','',dirname(__FILE__))),'/'):strrpos(str_replace('\\config','',str_replace('/config','',dirname(__FILE__))),'\\\\'))+1));\n".
                "define('CB_ADMIN_DIR_ROOT',substr(dirname(__FILE__),0,strpos(dirname(__FILE__),CB_ADMIN_DIR)+strlen(CB_ADMIN_DIR)).'/');\n".
                "define('CB_ADMIN_HTTP_ROOT','http://'.\$_SERVER['HTTP_HOST'].substr(\$_SERVER['REQUEST_URI'],0,strpos(\$_SERVER['REQUEST_URI'],CB_ADMIN_DIR)+strlen(CB_ADMIN_DIR)).'/');\n".
                "\n".
                "define('CB_BUILD_DATE','".CB_VERSION."');\n".
                "define('CB_VERSION','".CB_BUILD_DATE."');\n".
                "?>");
    fclose($f);
    
    //Create the database.php
    $f=fopen(dirname(__FILE__).'/config/database.php','w');
    fwrite($f,"<?php\n".
                "define('MYSQL_SERVER','".MYSQL_SERVER."');\n".
                "define('MYSQL_USER','".MYSQL_USER."');\n".
                "define('MYSQL_PASSWORD','".MYSQL_PASSWORD."');\n".
                "define('MYSQL_DATABASE','".MYSQL_DATABASE."');\n".
                "?>");
    fclose($f);
}



$dbVersion='1.0 19611231';


$updateXML=simplexml_load_string(file_get_contents('http://update.edchipman.ca/codeBank/airUpdate.xml'));
$latestVersion=strip_tags($updateXML->version->asXML());
$versionTmp=explode(' ',$latestVersion);

if($versionTmp[1]<CB_BUILD_DATE) {
    print '<b>Unknown server version:</b> '.CB_VERSION.' '.CB_BUILD_DATE.', current version available for download is '.$latestVersion;
    exit;
}

if(CB_VERSION.' '.CB_BUILD_DATE!=$latestVersion) {
    print '<b>There is an update available, please install the latest server update before running this script.</b> You can find the update at <a href="http://programs.edchipman.ca/applications/code-bank/">http://programs.edchipman.ca/applications/code-bank/</a>.';
    exit;
}

$updateRequired=false;

$conn=openDB();
$query="SELECT *
        FROM settings
        WHERE code='version'";
$result=$conn->Execute($query);

if($result->recordCount()>0) {
    $row=$result->fetchRow();
    
    if($row['value']!=$latestVersion) {
        $dbVersion=$row['value'];
        $updateRequired=true;
    }
    
    $dbVerTmp=explode(' ',$dbVersion);
    if($versionTmp[1]<$dbVerTmp[1]) {
        print '<b>Unknown database version:</b> '.$dbVersion.', current version available for download is '.$latestVersion;
        exit;
    }
}else {
    $updateRequired=true;
    
    print '<i>Could not find database version assuming out of date</i><br/>';
}

if($updateRequired) {
    print 'Update required, updating from '.$dbVersion.' to '.$latestVersion.'<br />';
    
    $data=array(
                'version'=>$dbVersion,
                'db_type'=>'SERVER'
            );
    
    $data=http_build_query($data);
    
    
    $context=stream_context_create(array(
                                        'http'=>array(
                                                    'method' => 'POST',
                                                    'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
                                                        . "Content-Length: " . strlen($data) . "\r\n",
                                                    'content' => $data
                                                )
                                    ));
    
    $sql=simplexml_load_string(file_get_contents('http://update.edchipman.ca/codeBank/DatabaseUpgrade.php',false,$context));
    $conn->StartTrans();
    
    try {
        $i=1;$s=1;$sets=count($sql->query);
        foreach($sql->query as $query) {
            $queries=explode('$',$query);
            $t=count($queries);
            
            foreach($queries as $query) {
                if(empty($query)) {
                    continue;
                }
                
                print '<b>Running Query '.$i.' of '.$t.' in set '.$s.' of '.$sets.'</b><br />';
                $conn->Execute($query);
                
                $i++;
            }
            
            $s++;
        }
        
        $conn->Execute("UPDATE settings SET value='".$latestVersion."' WHERE code='version'");
        
        $conn->CompleteTrans();
    }catch (Exception $e) {
        print '<i>Error Running Update: '.$e->getMessage().'</i>';
        exit;
        $conn->FailTrans();
    }
    
    
    print 'Completed';
    
    //Remove install files
    if(@unlink(dirname(__FILE__).'/install.php')==false || @unlink(dirname(__FILE__).'/install.sql')==false || @unlink(dirname(__FILE__).'/update.php')==false) {
        print 'Could not remove the install files please remove install.php, install.sql, and update.php before using your Code Bank server.';
        exit;
    }
}else {
    print 'No Update Required</i>';
}
?>