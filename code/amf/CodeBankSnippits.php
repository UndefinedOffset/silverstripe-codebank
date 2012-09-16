<?php
class CodeBankSnippits implements CodeBank_APIClass {
    /**
     * Gets the list of languages
     * @return {array} Standard response base
     */
    public function getLanguages() {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        $response['data']=array();
        
        foreach($languages as $lang) {
            $response['data'][]=array(
                        'language'=>$lang->Name,
                        'file_extension'=>$lang->FileExtension,
                        'shjs_code'=>$lang->HighlightCode
                    );
        }
        
        return $response;
    }
    
    /**
     * Gets a list of snippits in an array of languages
     * @return {array} Standard response base
     */
    public function getSnippits() {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        foreach($languages as $lang) {
            if($lang->getSnippets()->Count()>0) {
                $snippets=$lang->getSnippets()->map('ID', 'Title')->toArray();
                $response['data'][]=array(
                                        'language'=>$lang->Name,
                                        'snippits'=>$snippets
                                    );
            }
        }
        
        
        return $response;
    }
    
    /**
     * Gets a list of snippits that are in the selected index in an array of languages
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitsByLanguage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $lang=SnippetLanguage::get()->byID(intval($data->id));
        if(!empty($lang) && $lang!==false && $lang->ID!=0 && $lang->getSnippets()->Count()>0) {
            $snippets=$lang->getSnippets()->map('ID', 'Title')->toArray();
            $response['data'][]=array(
                                    'language'=>$lang->Name,
                                    'snippits'=>$snippets
                                );
        }
        
        
        return $response;
    }
    
    /**
     * Searches for snippits that match the information the client in the search field
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function searchSnippits($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        foreach($languages as $lang) {
            $snippets=$lang->getSnippets()->where("MATCH(Title, Description, Tags) AGAINST('".Convert::raw2sql($data->query)."' IN BOOLEAN MODE)");
            if($lang->getSnippets()->Count()>0) {
                $snippets=$snippets->map('ID', 'Title')->toArray();
                $response['data'][]=array(
                                        'language'=>$lang->Name,
                                        'snippits'=>$snippets
                                    );
            }
        }
        
        
        return $response;
    }
    
    /**
     * Gets a snippit's information
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitInfo($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $snippet=Snippet::get()->byID($data->id);
        if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
            $response['data'][]=array(
                        'id'=>$snippet->ID,
                        'title'=>$snippet->Title,
                        'text'=>$snippet->getSnippetText(),
                        'description'=>$snippet->Description,
                        'tags'=>$snippet->Tags,
                        'languageID'=>$snippet->LanguageID,
                        'lastModified'=>$snippet->LastEdited,
                        'creatorID'=>$snippet->CreatorID,
                        'creator'=>($snippet->Creator() ? $snippet->Creator()->Name:''),
                        'lastEditor'=>($snippet->LastEditor() ? $snippet->LastEditor()->Name:''),
                        'language'=>$snippet->Language()->Name,
                        'fileType'=>$snippet->Language()->FileExtension,
                        'shjs_code'=>$snippet->Language()->HighlightCode
                    );
            
            $response['data'][0]['text']=preg_replace('/\r\n|\n|\r/', "\n", $response['data'][0]['text']);
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
                                                        '<pre class="brush: '.strtolower($response['data'][0]['shjs_code']).'" style="font-size:10pt;">'.htmlentities(preg_replace('/\r\n|\n|\r/', "\n", $response['data'][0]['text']), null, 'UTF-8').'</pre>'.
                                                    '</body>'.
                                                 '</html>';
            
            if($response['data'][0]['language']=='ActionScript 3') {
                $response['data'][0]['fileType']='zip';
            }
        }else {
            $response['status']='EROR';
            $response['message']='Snippet not found';
        }
        
        
        return $response;
    }
    
    /**
     * Gets a revisions of the snippit
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitRevisions($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $snippet=Snippet::get()->byID($data->id);
        if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
            $revisions=$snippet->Versions()->map('ID', 'Created');
            
            $i=0;
            foreach($revisions as $id=>$date) {
                $response['data'][]=array(
                                        'id'=>$id,
                                        'date'=>($i==0 ? '{Current Revision}':$date)
                                    );
                $i++;
            }
        }else {
            $response['status']='EROR';
            $response['message']='Snippet not found';
        }
        
        return $response;
    }
    
    /**
     * Gets the of the snippit from a revision
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippitTextFromRevision($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        $revision=SnippetVersion::get()->byID(intval($data->id));
        if(!empty($revision) && $revision!==false && $revision->ID!=0) {
            $lang=$revision->Parent()->Language();
            
            $response['data']='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
                                 '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'.
                                    '<head>'.
                                        '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
                                        '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore.css"/>'.
                                        '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shCore'.$data->style.'.css"/>'.
                                        '<link type="text/css" rel="stylesheet" href="app:/tools/external/syntaxhighlighter/themes/shTheme'.$data->style.'.css"/>'.
                                        '<link type="text/css" rel="stylesheet" href="app:/tools/syntaxhighlighter.css"/>'.
                                        '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shCore.js"></script>'.
                                        '<script type="text/javascript" src="app:/tools/external/syntaxhighlighter/brushes/shBrush'.$lang->HighlightCode.'.js"></script>'.
                                        '<script type="text/javascript" src="app:/tools/external/jquery-packed.js"></script>'.
                                        '<script type="text/javascript" src="app:/tools/highlight_helper.js"></script>'.
                                    '</head>'.
                                    '<body>'.
                                        '<pre class="brush: '.strtolower($lang->HighlightCode).'" style="font-size:10pt;">'.htmlentities(preg_replace('/\r\n|\n|\r/', "\n", $revision->Text), null, 'UTF-8').'</pre>'.
                                    '</body>'.
                                 '</html>';
        }else {
            $response['status']='EROR';
            $response['message']='Revision not found';
        }
        
        
        return $response;
    }
    
    
    /**
     * Saves a new snippit
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function newSnippit($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        try {
            $snippet=new Snippet();
            $snippet->Title=$data->title;
            $snippet->Description=$data->description;
            $snippet->Text=$data->code;
            $snippet->Tags=$data->tags;
            $snippet->LanguageID=$data->language;
            $snippet->CreatorID=Member::currentUserID();
            $snippet->write();
            
            
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
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Member::currentUser()) {
            $response['status']='EROR';
            $response['message']='Not logged in';
            
            return $response;
        }
        
        
        try {
            $snippet=Snippet::get()->byID(intval($data->id));
            if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
                $snippet->Title=$data->title;
                $snippet->Description=$data->description;
                $snippet->Text=$data->code;
                $snippet->Tags=$data->tags;
                $snippet->LanguageID=$data->language;
                $snippet->LastEditorID=Member::currentUserID();
                $snippet->write();
                
                
                $response['status']='HELO';
            }else {
                $response['status']='EROR';
                $response['message']='Snippet not found';
            }
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
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $snippet=Snippet::get()->byID(intval($data->id));
            if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
                if($snippet->CreatorID!=Member::currentUserID() && !Permission::check('ADMIN')) {
                    $response['status']="EROR";
                    $response['message']="Not authorized";
                    
                    return $response;
                }
                
                
                //Delete the snippet
                $snippet->delete();
                
                
                $response['status']='HELO';
            }else {
                $response['status']='EROR';
                $response['message']='Snippet not found';
            }
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
        $response=CodeBank_ClientAPI::responseBase();
        
        
        //Get the Main Revision
        $snippit1=SnippetVersion::get()->byID(intval($data->mainRev));
        if(empty($snippit1) || $snippit1===false || $snippit1->ID==0) {
            $response['status']='EROR';
            $response['message']='Main revision not found';
        
            return $response;
        }
        
        $snippit1=preg_replace('/\r\n|\n|\r/', "\n", $snippit1->Text);
        
        
        //Get the Comparision Revision
        $snippit2=SnippetVersion::get()->byID(intval($data->compRev));
        if(empty($snippit2) || $snippit1===false || $snippit2->ID==0) {
            $response['status']='EROR';
            $response['message']='Compare revision not found';
        
            return $response;
        }
        
        $snippit2=preg_replace('/\r\n|\n|\r/', "\n", $snippit2->Text);
        
        
        //Generate the diff file
        $diff=new Text_Diff('auto', array(preg_split('/\n/', $snippit1), preg_split('/\n/', $snippit2)));
        $renderer=new Text_Diff_Renderer_unified(array('leading_context_lines'=>1, 'trailing_context_lines'=>1));
        
        $response['data']=array('mainRev'=>$snippit1, 'compRev'=>$snippit2, 'diff'=>$renderer->render($diff));
        
        $conn->Close();
        
        return $response;
    }
}
?>