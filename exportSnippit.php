<?php
//@TODO Move into CodeBank_ClientAPI
/*require_once(dirname(__FILE__).'/includes/func.inc.php');

/**FLASH PLAYER WORK AROUND FOR BUG IN FileReference.download WHERE THE PLAYER DOES NOT SEND COOKIES IN THE REQUEST**/
/*if(array_key_exists('s',$_REQUEST)) {
    //Use the session id in the request
    session_id($_REQUEST['s']);
    
    //Start the session
    session_start();
}else {
    //Treat normaly
    session_start();
    session_regenerate_id();
}
/**END**/

/**
 * Retrieves request items based on type requested
 * @param {string} $aKey Key to use to get items from request variables
 * @param {int} $aType If type is null than post will be checked first then get. If this is 0 then post will be used, if 1 then get will be used.
 * @return Returns a blank string when no value is found or the value of the request item otherwise.
 */
/*function getRequestItem($aKey,$aType) {
    if($aType==-1) {
        if(array_key_exists($aKey,$_POST)) {
            return stripslashes_deep($_POST[$aKey]);
        }else if(array_key_exists($aKey,$_GET)) {
            return stripslashes_deep($_GET[$aKey]);
        }else {
            return "";
        }
    }else if($aType==0) {
        if(array_key_exists($aKey,$_POST)) {
            return stripslashes_deep($_POST[$aKey]);
        }else {
            return "";
        }
    }else if($aType==1) {
        if(array_key_exists($aKey,$_GET)) {
            return stripslashes_deep($_GET[$aKey]);
        }else {
            return "";
        }
    }
}

/**
 * Strips Slashes even from an array
 * @return returns input without slashes.
 */
/*function stripslashes_deep($value) {
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);

    return $value;
}

/**
 * Checks to see if login is valid
 * @return {boolean} Returns true when session valid false otherwise
 */
/*function checkLogin() {
    if(!array_key_exists('id',$_SESSION) || !array_key_exists('user',$_SESSION) || !array_key_exists('loginKey',$_SESSION)) {
        return false;
    }

    $conn=openDB();

    $query="SELECT id
    FROM users
    WHERE id=".intval($_SESSION['id'])." AND username='".Convert::raw2sql($_SESSION['user'])."' AND loginKey='".Convert::raw2sql($_SESSION['loginKey'])."' AND lastLoginIP='".Convert::raw2sql($_SERVER['REMOTE_ADDR'])."'";
    $count=$conn->Execute($query)->recordCount();

    $conn->close();

    if($count==1) {
        return true;
    }else {
        return false;
    }
}

header('Cache-Control: private, no-cache');
header('Pragma: no-cache');

if(checkLogin()==true) {
    try {
        $fileID=uniqId(time());
        
        $conn=openDB();
        $query="SELECT s.title,h.text,l.language,l.file_extension
                FROM snippits s
                    INNER JOIN snippit_history h ON s.id=h.fkSnippit
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE s.id=".intval(getRequestItem('id',1))."
                ORDER BY h.date DESC
                LIMIT 1";
        $data=$conn->Execute($query)->fetchRow();
        
        if($data['language']=='ActionScript 3') {
            $zip=new ZipArchive();
            
            $res=$zip->open(dirname(__FILE__).'/temp/'.$fileID.'.zip',ZIPARCHIVE::CREATE);
            
            if($res) {
                $path='';
                $text=preg_split("/[\n\r]/",$data['text']);
                $folder=str_replace('.','/',trim(preg_replace('/^package (.*?)((\s*)\{)?$/i','\\1',$text[0])));
                
                $className=array_values(preg_grep('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i',$text));
                
                if(count($className)==0) {
                    throw new Exception('Class definition could not be found');
                }
                
                $className=trim(preg_replace('/(\s*|\t*)public(\s+)class(\s+)(.*?)(\s*)((extends|implements)(.*?)(\s*))*\{/i','\\4',$className[0]));
                
                if($className=="") {
                    throw new Exception('Class definition could not be found');
                }
                
                $zip->addFromString($folder.'/'.$className.'.'.$data['file_extension'],$data['text']);
                
                $zip->Close();
                chmod(dirname(__FILE__).'/temp/'.$fileID.'.zip',0600);
                
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment;  filename="'.$fileID.'.zip"');
                header('Content-Transfer-Encoding: binary');
                
                echo file_get_contents(dirname(__FILE__).'/temp/'.$fileID.'.zip');
                unlink(dirname(__FILE__).'/temp/'.$fileID.'.zip');
            }
        }else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment;  filename="'.$fileID.'.'.$data['file_extension'].'"');
            header('Content-Transfer-Encoding: binary');
            
            echo $data['text'];
        }
        
        $conn->Close();
    }catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
    }
}else {
    header("HTTP/1.1 401 Unauthorized");
}*/
?>