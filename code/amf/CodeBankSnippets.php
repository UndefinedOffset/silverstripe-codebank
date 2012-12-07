<?php
class CodeBankSnippets implements CodeBank_APIClass {
    /**
     * Gets the list of languages
     * @return {array} Standard response base
     */
    public function getLanguages() {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        $response['data']=array();
        
        foreach($languages as $lang) {
            $response['data'][]=array(
                        'language'=>$lang->Name,
                        'file_extension'=>$lang->FileExtension,
                        'shjs_code'=>$lang->HighlightCode,
                        'id'=>$lang->ID
                    );
        }
        
        return $response;
    }
    
    /**
     * Gets a list of snippets in an array of languages
     * @return {array} Standard response base
     */
    public function getSnippets() {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        foreach($languages as $lang) {
            if($lang->Snippets()->Count()>0) {
                $snippets=$this->arrayUnmap($lang->Snippets()->map('ID', 'Title')->toArray());
                $response['data'][]=array(
                                        'language'=>$lang->Name,
                                        'snippets'=>$snippets
                                    );
            }
        }
        
        
        return $response;
    }
    
    /**
     * Gets a list of snippets that are in the selected index in an array of languages
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippetsByLanguage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
            return $response;
        }
        
        
        $lang=SnippetLanguage::get()->byID(intval($data->id));
        if(!empty($lang) && $lang!==false && $lang->ID!=0 && $lang->Snippets()->Count()>0) {
            $snippets=$this->arrayUnmap($lang->Snippets()->map('ID', 'Title')->toArray());
            $response['data'][]=array(
                                    'language'=>$lang->Name,
                                    'snippets'=>$snippets
                                );
        }
        
        
        return $response;
    }
    
    /**
     * Searches for snippets that match the information the client in the search field
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function searchSnippets($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        foreach($languages as $lang) {
            $snippets=$lang->Snippets()->where("MATCH(Title, Description, Tags) AGAINST('".Convert::raw2sql($data->query)."' IN BOOLEAN MODE)");
            if($lang->Snippets()->Count()>0) {
                $snippets=$this->arrayUnmap($snippets->map('ID', 'Title')->toArray());
                $response['data'][]=array(
                                        'language'=>$lang->Name,
                                        'snippets'=>$snippets
                                    );
            }
        }
        
        
        return $response;
    }
    
    /**
     * Gets a snippet's information
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippetInfo($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
            return $response;
        }
        
        
        $snippet=Snippet::get()->byID($data->id);
        if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
            $packageDetails=null;
            if($snippet->Package()) {
                $packageDetails=array(
                                        'id'=>$snippet->Package()->ID,
                                        'title'=>$snippet->Package()->Title,
                                        'snippets'=>$this->overviewList($snippet->Package()->Snippets())
                                    );
            }
            
            
            $response['data'][]=array(
                                    'id'=>$snippet->ID,
                                    'title'=>$snippet->Title,
                                    'text'=>$snippet->getSnippetText(),
                                    'description'=>$snippet->Description,
                                    'tags'=>$snippet->Tags,
                                    'languageID'=>$snippet->LanguageID,
                                    'lastModified'=>$snippet->LastEdited,
                                    'creatorID'=>$snippet->CreatorID,
                                    'creator'=>($snippet->Creator() ? $snippet->Creator()->Name:'Deleted User'),
                                    'lastEditor'=>($snippet->LastEditor() ? $snippet->LastEditor()->Name:($snippet->LastEditorID!=0 ? 'Deleted User':'')),
                                    'language'=>$snippet->Language()->Name,
                                    'fileType'=>$snippet->Language()->FileExtension,
                                    'shjs_code'=>$snippet->Language()->HighlightCode,
                                    'package'=>$packageDetails
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
     * Gets a revisions of the snippet
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippetRevisions($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
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
     * Gets the of the snippet from a revision
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippetTextFromRevision($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
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
     * Saves a new snippet
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function newSnippet($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
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
     * Saves an existing snippet
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function saveSnippet($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            
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
     * Deletes a snippet from the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function deleteSnippet($data) {
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
     * Gets the snippet text for the two revisions as well as a unified diff file of the revisions
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getSnippetDiff($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        
        //Get the Main Revision
        $snippet1=SnippetVersion::get()->byID(intval($data->mainRev));
        if(empty($snippet1) || $snippet1===false || $snippet1->ID==0) {
            $response['status']='EROR';
            $response['message']='Main revision not found';
        
            return $response;
        }
        
        $snippet1=preg_replace('/\r\n|\n|\r/', "\n", $snippet1->Text);
        
        
        //Get the Comparision Revision
        $snippet2=SnippetVersion::get()->byID(intval($data->compRev));
        if(empty($snippet2) || $snippet1===false || $snippet2->ID==0) {
            $response['status']='EROR';
            $response['message']='Compare revision not found';
        
            return $response;
        }
        
        $snippet2=preg_replace('/\r\n|\n|\r/', "\n", $snippet2->Text);
        
        
        //Generate the diff file
        $diff=new Text_Diff('auto', array(preg_split('/\n/', $snippet2), preg_split('/\n/', $snippet1)));
        $renderer=new Text_Diff_Renderer_unified(array('leading_context_lines'=>1, 'trailing_context_lines'=>1));
        
        $response['data']=array('mainRev'=>$snippet1, 'compRev'=>$snippet2, 'diff'=>$renderer->render($diff));
        
        
        return $response;
    }
    
    /**
     * Converts an array where the key and value should be mapped to a nested array
     * @param {array} $array Source Array
     * @param {string} $keyLbl Array's Key mapping name
     * @param {string} $valueLbl Key's value mapping name
     * @return {array} Unmapped array
     */
    final protected function arrayUnmap($array, $keyLbl='id', $valueLbl='title') {
        $result=array();
        
        foreach($array as $key=>$value) {
            $result[]=array(
                            $keyLbl=>$key,
                            $valueLbl=>$value
                        );
        }
        
        
        //Return the resulting array
        return $result;
    }
    
    /**
     * Converts an SS_List into an array of items with and id and a title key
     * @param {SS_List} $list List to parse
     * @return {array} Nested array of items, each item has an id and a title key
     */
    final protected function overviewList(SS_List $list) {
        $result=array();
        
        foreach($list as $item) {
            $result[]=array(
                            'id'=>$item->ID,
                            'title'=>$item->Title
                        );
        }
        
        return $result;
    }
}
?>