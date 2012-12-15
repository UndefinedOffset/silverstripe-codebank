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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
            return $response;
        }
        
        
        $snippet=Snippet::get()->byID($data->id);
        if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
            $packageDetails=null;
            if($snippet->Package()) {
                $packageDetails=array(
                                        'id'=>$snippet->Package()->ID,
                                        'title'=>$snippet->Package()->Title,
                                        'snippets'=>$this->sortToTop($snippet->ID, 'id', $this->overviewList($snippet->Package()->Snippets()))
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
            $response['message']=_t('CodeBankAPI.SNIPPET_NOT_FOUND', '_Snippet not found');
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.SNIPPET_NOT_FOUND', '_Snippet not found');
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $response['message']=_t('CodeBankAPI.REVISION_NOT_FOUND', '_Revision not found');
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
            $snippet->PackageID=$data->packageID;
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
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
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
                $snippet->PackageID=$data->packageID;
                $snippet->write();
                
                
                $response['status']='HELO';
            }else {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.SNIPPET_NOT_FOUND', '_Snippet not found');
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
                $response['message']=_t('CodeBankAPI.SNIPPET_NOT_FOUND', '_Snippet not found');
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
            $response['message']=_t('CodeBankAPI.MAIN_REVISION_NOT_FOUND', '_Main revision not found');
        
            return $response;
        }
        
        $snippet1=preg_replace('/\r\n|\n|\r/', "\n", $snippet1->Text);
        
        
        //Get the Comparision Revision
        $snippet2=SnippetVersion::get()->byID(intval($data->compRev));
        if(empty($snippet2) || $snippet1===false || $snippet2->ID==0) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.COMPARE_REVISION_NOT_FOUND', '_Compare revision not found');
        
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
     * Gets the list of packages
     * @return {array} Standard response base
     */
    public function getPackages() {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
            return $response;
        }
        
        
        $response['data']=$this->overviewList(SnippetPackage::get());
        
        
        return $response;
    }
    
    /**
     * Gets the details of a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function getPackageInfo($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        $package=SnippetPackage::get()->byID(intval($data->id));
        if(!empty($package) && $package!==false && $package->ID!=0) {
            $response['data']=array(
                                    'id'=>$package->ID,
                                    'title'=>$package->Title,
                                    'snippets'=>$this->overviewList($package->Snippets(), 'Title', 'ID', 'Language.Title')
                                );
            
            
            $response['status']='HELO';
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGE_NOT_FOUND', '_Package not found');
        }
        
        return $response;
    }
    
    /**
     * Removes a snippet from a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function packageRemoveSnippet($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        $package=SnippetPackage::get()->byID(intval($data->packageID));
        if(!empty($package) && $package!==false && $package->ID!=0) {
            $package->Snippets()->removeByID(intval($data->snippetID));
            
            
            $response['status']='HELO';
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGE_NOT_FOUND', '_Package not found');
        }
        
        return $response;
    }
    
    /**
     * Finds a snippet begining with the data passed
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function findSnippetAutoComplete($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        //Lookup snippets
        $snippets=Snippet::get()->where("\"Snippet\".\"Title\" LIKE '".Convert::raw2sql($data->pattern)."%'")->limit(20);
        
        
        $response['status']='HELO';
        $response['data']=$this->overviewList($snippets);
        
        return $response;
    }
    
    /**
     * Adds a snippet to a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function addSnippetToPackage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            
            return $response;
        }
        
        
        $package=SnippetPackage::get()->byID(intval($data->packageID));
        if(!empty($package) && $package!==false && $package->ID!=0) {
            $snippet=Snippet::get()->byID(intval($data->snippetID));
            if(!empty($snippet) && $snippet!==false && $snippet->ID!=0) {
                $package->Snippets()->add($snippet);
                
                
                $response['status']='HELO';
            }else {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.SNIPPET_NOT_FOUND', '_Snippet not found');
            }
            
            $response['status']='HELO';
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGE_NOT_FOUND', '_Package not found');
        }
        
        
        return $response;
    }
    
    /**
     * Saves a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function savePackage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        $package=SnippetPackage::get()->byID(intval($data->packageID));
        if(!empty($package) && $package!==false && $package->ID!=0) {
            if(!empty($data->title)) {
                $package->Title=$data->title;
                $package->write();
                
                
                $response['status']='HELO';
            }else {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.PACKAGES_TITLE_REQUIRED', '_Packages must have a title');
            }
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGE_NOT_FOUND', '_Package not found');
        }
        
        return $response;
    }
    
    /**
     * Saves a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function createPackage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        if(!empty($data->title)) {
            $package=new SnippetPackage();
            $package->Title=$data->title;
            $package->write();
            
            
            $response['status']='HELO';
            $response['data']=$package->ID;
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGES_TITLE_REQUIRED', '_Packages must have a title');
        }
        
        return $response;
    }
    
    /**
     * Deletes a package
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function deletePackage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Ensure logged in
        if(!Permission::check('CODE_BANK_ACCESS')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
        
            return $response;
        }
        
        
        $package=SnippetPackage::get()->byID(intval($data->id));
        if(!empty($package) && $package!==false && $package->ID!=0) {
            $package->delete();
            
            
            $response['status']='HELO';
        }else {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PACKAGE_NOT_FOUND', '_Package not found');
        }
        
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
     * @param {string} $labelField Label Field
     * @param {string} $idField ID Field
     * @param {string} ... Overloaded for additional fields, allows for silverstripe relation formatting ex: Relationship.Field the dot is replaced with a dash in the resulting object
     * @return {array} Nested array of items, each item has an id and a title key
     */
    final protected function overviewList(SS_List $list, $labelField='Title', $idField='ID') {
        $result=array();
        $idFieldLower=strtolower($idField);
        $labelFieldLower=strtolower($labelField);
        
        
        $args=func_get_args();
        unset($args[0]);
        unset($args[1]);
        unset($args[2]);
        
        
        foreach($list as $item) {
            $obj=new stdClass();
            $obj->$idFieldLower=$item->$idField;
            $obj->$labelFieldLower=$item->$labelField;
            
            if(count($args)>0) {
                foreach($args as $field) {
                    $fieldLower=strtolower($field);
                    
                    //If the field contains a dot assume relationship and loop through till field is found
                    if(strpos($field, '.')!==false) {
                        $fieldLower=str_replace('.', '-', $fieldLower);
                        $fieldBits=explode('.', $field);
                        
                        $value=$item;
                        for($i=0;$i<count($fieldBits);$i++) {
                            $fieldBit=$fieldBits[$i];
                            if($i==count($fieldBits)-1) {
                                $value=$value->$fieldBit;
                            }else {
                                $value=$value->$fieldBit();
                            }
                        }
                        
                        if(!is_object($value)) {
                            $obj->$fieldLower=$value;
                        }
                    }else {
                        $obj->$fieldLower=$item->$field;
                    }
                }
            }
            
            $result[]=$obj;
        }
        
        
        return $result;
    }
    
    /**
     * Sorts an item to the top of the list
     * @param {mixed} $value Value to look for
     * @param {mixed} $field Field to look on
     * @param {array} $array Array to sort
     * @return {array} Finalized array
     */
    final protected function sortToTop($value, $field, $array) {
        foreach($array as $key=>$item) {
            if($item->$field==$value) {
                unset($array[$key]);
                array_unshift($array, $item);
                break;
            }
        }
        
        return $array;
    }
}
?>