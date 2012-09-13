<?php
require_once(dirname(__FILE__).'/includes/func.inc.php');

/**FLASH PLAYER WORK AROUND FOR BUG IN FileReference.download WHERE THE PLAYER DOES NOT SEND COOKIES IN THE REQUEST**/
if(array_key_exists('s',$_REQUEST)) {
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
                WHERE s.id=".encSQLInt(getRequestItem('id',1))."
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
}
?>