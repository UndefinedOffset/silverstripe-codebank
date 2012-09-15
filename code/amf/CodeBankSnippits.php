<?php
class CodeBankSnippits implements CodeBank_APIClass {
    /**
     * Gets the list of languages
     * @return {array} Standard response base
     */
    public function getLanguages() {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT *
                FROM languages
                ORDER BY language";
        $response['data']=$conn->Execute($query)->getAll();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets a list of snippits in an array of languages
     * @return {array} Standard response base
     */
    public function getSnippits() {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT *
                FROM languages
                ORDER BY language";
        $result=$conn->Execute($query);
        
        while($tmp=$result->fetchRow()) {
            $snipQuery="SELECT id,title
                        FROM snippits
                        WHERE fkLanguage=".intval($tmp['id']).'
                        ORDER BY title, id';
            
            $lang=array(
                            'language'=>$tmp['language'],
                            'snippits'=>$conn->Execute($snipQuery)->getAll()
                    );
            
            if(count($lang['snippits'])>0) {
                $response['data'][]=$lang;
            }
        }
        
        $result->Free();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets a list of snippits that are in the selected index in an array of languages
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitsByLanguage($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT *
                FROM languages
                WHERE id=".intval($data->id);
        $result=$conn->Execute($query);
        
        while($tmp=$result->fetchRow()) {
            $snipQuery="SELECT id,title
                        FROM snippits
                        WHERE fkLanguage=".intval($tmp['id']);
            
            $lang=array(
                            'language'=>$tmp['language'],
                            'snippits'=>$conn->Execute($snipQuery)->getAll()
                    );
            
            if(count($lang['snippits'])>0) {
                $response['data'][]=$lang;
            }
        }
        
        $result->Free();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Searches for snippits that match the information the client in the search field
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function searchSnippits($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        
        //Migrate to snippit search if needed
        if($conn->Execute('SELECT count(*) AS totalInSearch FROM snippit_search')->fields['totalInSearch']==0) {
            $conn->Execute('INSERT INTO snippit_search(title, description, tags, SnippitID) SELECT title, description, tags, id FROM snippits');
        }
        
        
        
        $query="SELECT *
                FROM languages";
        $result=$conn->Execute($query);
        
        while($tmp=$result->fetchRow()) {
            $snipQuery="SELECT ss.SnippitID AS id,ss.title
                        FROM snippit_search ss
                        	INNER JOIN snippits s ON s.id=ss.SnippitID
                        WHERE s.fkLanguage=".intval($tmp['id'])." AND MATCH(ss.title,ss.description,ss.tags) AGAINST('".mysql_real_escape_string($data->query)."' IN BOOLEAN MODE)";
            
            $lang=array(
                            'language'=>$tmp['language'],
                            'snippits'=>$conn->Execute($snipQuery)->getAll()
                    );
            
            if(count($lang['snippits'])>0) {
                $response['data'][]=$lang;
            }
        }
        
        $result->Free();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets a snippit's information
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitInfo($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT s.id,s.title,h.text,s.description,s.tags, s.fkLanguage AS languageID,h.date as lastModified,c.id as creatorID, c.username as creator,e.username as lastEditor,l.language,l.file_extension as fileType,l.shjs_code
                FROM snippits s
                    INNER JOIN snippit_history h ON s.id=h.fkSnippit
                    INNER JOIN languages l ON s.fkLanguage=l.id
                    LEFT JOIN users c ON s.fkCreatorUser=c.id
                    LEFT JOIN users e ON s.fkLastEditUser=e.id
                WHERE s.id=".intval($data->id)."
                ORDER BY h.date DESC
                LIMIT 1";
        $response['data']=$conn->Execute($query)->getAll();
        
        $response['data'][0]['text']=preg_replace('/\r\n|\n|\r/',"\n",$response['data'][0]['text']);
        $response['data'][0]['formatedText']='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
                                     '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'.
                                        '<head>'.
                                            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
                                            '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore.css"/>'.
                                            '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore'.$data->style.'.css"/>'.
                                            '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shTheme'.$data->style.'.css"/>'.
                                            '<link type="text/css" rel="stylesheet" href="app:/tools/syntaxhighlighter.css"/>'.
                                            '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shCore.js"></script>'.
                                            '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shBrush'.$response['data'][0]['shjs_code'].'.js"></script>'.
                                            '<script type="text/javascript" src="app:/tools/external/jquery-packed.js"></script>'.
                                            '<script type="text/javascript" src="app:/tools/highlight_helper.js"></script>'.
                                        '</head>'.
                                        '<body>'.
                                            '<pre class="brush: '.strtolower($response['data'][0]['shjs_code']).'" style="font-size:10pt;">'.htmlentities(preg_replace('/\r\n|\n|\r/',"\n",$response['data'][0]['text']),null,'UTF-8').'</pre>'.
                                        '</body>'.
                                     '</html>';
        
        if($response['data'][0]['language']=='ActionScript 3') {
            $response['data'][0]['fileType']='zip';
        }
        
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets a revisions of the snippit
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitRevisions($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT id,date
                FROM snippit_history
                WHERE fkSnippit=".intval($data->id)."
                ORDER BY date DESC";
        $result=$conn->Execute($query);
        
        $i=0;
        while($tmp=$result->fetchRow()) {
            $response['data'][]=array(
                                        'id'=>$tmp['id'],
                                        'date'=>($i==0 ? '{Current Revision}':$tmp['date'])
                                    );
            $i++;
        }
        
        $result->Free();
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets the of the snippit from a revision
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitTextFromRevision($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT h.text,l.shjs_code
                FROM snippit_history h
                    INNER JOIN snippits s ON h.fkSnippit=s.id
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE h.id=".intval($data->id);
        $result=$conn->Execute($query)->fetchRow();
        $response['data']='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
                             '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'.
                                '<head>'.
                                    '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
                                    '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore.css"/>'.
                                    '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore'.$data->style.'.css"/>'.
                                    '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shTheme'.$data->style.'.css"/>'.
                                    '<link type="text/css" rel="stylesheet" href="app:/tools/syntaxhighlighter.css"/>'.
                                    '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shCore.js"></script>'.
                                    '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shBrush'.$result['shjs_code'].'.js"></script>'.
                                    '<script type="text/javascript" src="app:/tools/external/jquery-packed.js"></script>'.
                                    '<script type="text/javascript" src="app:/tools/highlight_helper.js"></script>'.
                                '</head>'.
                                '<body>'.
                                    '<pre class="brush: '.strtolower($result['shjs_code']).'" style="font-size:10pt;">'.htmlentities(preg_replace('/\r\n|\n|\r/',"\n",$result['text']), null, 'UTF-8').'</pre>'.
                                '</body>'.
                             '</html>';
        
        $conn->Close();
        
        return $response;
    }
    
    
    /**
     * Saves a new snippit
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function newSnippit($data) {
        $response=responseBase();
        
        try {
            $conn=openDB();
            
            
            $query="INSERT INTO snippits(title,description,tags,fkLanguage,fkCreatorUser)
                    VALUES('".Convert::raw2sql($data->title)."','".Convert::raw2sql($data->description)."','".Convert::raw2sql($data->tags)."',".intval($data->language).",".intval($_SESSION['id']).")";
            $conn->Execute($query);
            
            //Store snippit id
            $snippitID=$conn->Insert_ID();
            
            //Insert into snippit search table
            $query="INSERT INTO snippit_search(title,description,tags,SnippitID)
                                VALUES('".Convert::raw2sql($data->title)."','".Convert::raw2sql($data->description)."','".Convert::raw2sql($data->tags)."',".intval($snippitID).")";
            $conn->Execute($query);
            
            
            
            //Insert the new code into the snippit history
            $query="INSERT INTO snippit_history(fkSnippit,text,date)
                    VALUES(".intval($snippitID).",'".Convert::raw2sql(preg_replace('/\r\n|\n|\r/',"\n",$data->code))."','".Convert::raw2sql(date('Y-m-d H:i'))."')";
            $conn->Execute($query);
            
            $conn->Close();
            
            $response['status']="HELO";
        }catch(Exception $e) {
            $response['status']="EROR";
            $response['message']="Internal Server error occured";
        }
        
        return $response;
    }
    
    /**
     * Saves an existing snippit
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function saveSnippit($data) {
        $response=responseBase();
        
        try {
            $conn=openDB();
            
            
            //Update the snippit table
            $query="UPDATE snippits
                    SET title='".Convert::raw2sql($data->title)."',
                        description='".Convert::raw2sql($data->description)."',
                        tags='".Convert::raw2sql($data->tags)."',
                        fkLanguage=".intval($data->language).",
                        fkLastEditUser=".intval($_SESSION['id'])."
                    WHERE id=".intval($data->id);
            $conn->Execute($query);
            
            //Update the search table
            $query="UPDATE snippit_search
                    SET title='".Convert::raw2sql($data->title)."',
                        description='".Convert::raw2sql($data->description)."',
                        tags='".Convert::raw2sql($data->tags)."'
                    WHERE SnippitID=".intval($data->id);
            $conn->Execute($query);
            
            
            
            //Check to see if the code has changed
            $query="SELECT text
                    FROM snippit_history
                    WHERE fkSnippit=".intval($data->id)."
                    ORDER BY date DESC
                    LIMIT 1";
            $result=$conn->Execute($query)->fetchRow();
            
            if($result['text']!=$data->code) {
                //Insert the new code into the snippit history
                $query="INSERT INTO snippit_history(fkSnippit,text,date)
                        VALUES(".intval($data->id).",'".Convert::raw2sql(preg_replace('/\r\n|\n|\r/',"\n",$data->code))."','".Convert::raw2sql(date('Y-m-d H:i'))."')";
                $conn->Execute($query);
            }
            
            
            $conn->Close();
            
            $response['status']="HELO";
        }catch(Exception $e) {
            $response['status']="EROR";
            $response['message']="Internal Server error occured";
        }
        
        return $response;
    }
    
    /**
     * Deletes a snippit from the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function deleteSnippit($data) {
        $response=responseBase();
        
        try {
            $conn=openDB();
            
            $snippitInfo=$this->getSnippitInfo($data);
            
            if(empty($snippitInfo['data'])) {
                $response['status']="EROR";
                $response['message']="Internal Server error occured";
                
                return $response;
            }else if($snippitInfo['data'][0]['creatorID']!=$_SESSION['id'] && $_SESSION['username']!='admin') {
                $response['status']="EROR";
                $response['message']="Not authorized";
                
                return $response;
            }
            
            
            $query="DELETE FROM snippits
                    WHERE id=".intval($data->id);
            $conn->Execute($query);
            
            //Clear record from snippit search
            $query="DELETE FROM snippit_search
                    WHERE SnippitID=".intval($data->id);
            $conn->Execute($query);
            
            
            $conn->Close();
            
            $response['status']="HELO";
        }catch(Exception $e) {
            $response['status']="EROR";
            $response['message']="Internal Server error occured";
        }
        
        return $response;
    }
    
    /**
     * Gets the snippit text for the two revisions as well as a unified diff file of the revisions
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitDiff($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        //Get the Main Revision
        $query="SELECT h.text, s.title, l.language
                FROM snippit_history h
                    INNER JOIN snippits s ON h.fkSnippit=s.id
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE s.id=".intval($data->mainRev);
        $result=$conn->Execute($query)->fetchRow();
        $snippit1=preg_replace('/\r\n|\n|\r/',"\n",$result['text']);
        
        //Get the Comparision Revision
        $query="SELECT h.text, s.title, l.language
                FROM snippit_history h
                    INNER JOIN snippits s ON h.fkSnippit=s.id
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE h.id=".intval($data->compRev);
        $result=$conn->Execute($query)->fetchRow();
        $snippit2=preg_replace('/\r\n|\n|\r/',"\n",$result['text']);
        
        
        //Generate the diff file
        $diff=new Text_Diff('auto',array(preg_split('/\n/',$snippit1), preg_split('/\n/',$snippit2)));
        $renderer=new Text_Diff_Renderer_unified(array('leading_context_lines' => 1, 'trailing_context_lines' => 1));
        
        $response['data']=array('mainRev'=>$snippit1,'compRev'=>$snippit2,'diff'=>$renderer->render($diff));
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Gets the html used for displaying the difference between two revisions
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getHTMLSnippitDiff($data) {
        $response=responseBase();
        
        $conn=openDB();
        
        //Get the Main Revision
        $query="SELECT h.text, s.title, l.language
                FROM snippit_history h
                    INNER JOIN snippits s ON h.fkSnippit=s.id
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE s.id=".intval($data->mainRev);
        $result=$conn->Execute($query)->fetchRow();
        $snippit1=preg_replace('/\r\n|\n|\r/',"\n",$result['text']);
        
        //Get the Comparision Revision
        $query="SELECT h.text, s.title, l.language
                FROM snippit_history h
                    INNER JOIN snippits s ON h.fkSnippit=s.id
                    INNER JOIN languages l ON s.fkLanguage=l.id
                WHERE h.id=".intval($data->compRev);
        $result=$conn->Execute($query)->fetchRow();
        $snippit2=preg_replace('/\r\n|\n|\r/',"\n",$result['text']);
        
        
        //Generate the diff file
        $diff=new Text_Diff('auto',array(preg_split('/\n/',$snippit1), preg_split('/\n/',$snippit2)));
        $renderer=new WP_Text_Diff_Renderer_Table();
        
        $response['data']=$renderer->render($diff);
        
        if(!empty($response['data'])) {
            $lTable='<table cellspacing="0" cellpadding="0" border="0" class="diff">'.
                        '<colgroup>'.
                        	'<col class="ltype"/>'.
                        	'<col class="content"/>'.
                    	'</colgroup>'.
                		'<tbody>';
            $rTable=$lTable;
            
            header('content-type: text/plain');
            $xml=simplexml_load_string('<tbody>'.str_replace('&nbsp;',' ',$response['data']).'</tbody>');
            foreach($xml->children() as $row) {
                $i=0;
                $lTable.='<tr>';
                $rTable.='<tr>';
                
                foreach($row->children() as $td) {
                    $attr=$td->attributes();
                    
                    if($i==0) {
                        $lTable.=$td->asXML();
                    }else {
                        $rTable.=$td->asXML();
                    }
                    
                    $i++;
                }
                
                $lTable.='</tr>';
                $rTable.='</tr>';
            }
            
            $lTable.='</tbody></table>';
            $rTable.='</tbody></table>';
            
            $response['data']=array($lTable, $rTable);
        }
        
        $conn->Close();
        
        return $response;
    }
}
?>