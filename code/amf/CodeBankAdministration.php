<?php
class CodeBankAdministration implements CodeBank_APIClass {
    /**
     * Gets a list of users in the database
     * @return {array} Returns a standard response array
     */
    public function getUsersList() {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        $members=Permission::get_members_by_permission(array('ADMIN', 'CODE_BANK_ACCESS'));
        foreach($members as $member) {
            $response['data'][]=array(
                                    'id'=>$member->ID,
                                    'username'=>$member->Email,
                                    'lastLogin'=>$member->LastVisited
                                );
        }
        
        
        return $response;
    }
    
    /**
     * Deletes a user
     * @param {stdClass} $data Data passed from ActionScript from ActionScript
     * @return {array} Returns a standard response array
     */
    public function deleteUser($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        try {
            $member=Member::get()->filter('ID', intval($data->id))->where('ID<>'.Member::currentUserID())->First();
            if(!empty($member) && $member!==false && $member->ID!=0) {
                $member->delete();
            }
            
            $response['status']='HELO';
            $response['message']='User deleted successfully';
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Changes a users password
     * @param {stdClass} $data Data passed from ActionScript from ActionScript
     * @return {array} Returns a standard response array
     */
    public function changeUserPassword($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            if(!Permission::check('ADMIN')) {
                $member=Member::currentUser();
                
                $e=PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
                if(!$e->check($member->Password, $data->currPassword, $member->Salt, $member)) {
                    $response['status']='EROR';
                    $response['message']='Current password does not match';
                    
                    return $response;
                }
            }else {
                $member=Member::get()->byID(intval($data->id));
                if(empty($member) || $member===false || $member->ID==0) {
                    $response['status']='EROR';
                    $response['message']='Member not found';
                    
                    return $response;
                }
            }
            
            if(!$member->changePassword($data->password)) {
                $response['status']='EROR';
                $response['message']='New password is not valid';
                
                return $response;
            }
            
            
            $response['status']='HELO';
            $response['message']="User's password changed successfully";
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Creates a user in the database
     * @param {stdClass} $data Data passed from ActionScript from ActionScript
     * @return {array} Returns a standard response array
     */
    public function createUser($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        try {
            if(Member::get()->filter('Email', Convert::raw2sql($data->username))) {
                $response['status']='EROR';
                $response['message']='Username already exists';
                
                return $response;
            }
            
            
            //Create and write member
            $member=new Member();
            $member->Email=$data->username;
            $member->Password=$data->Password;
            $member->UseHeartbeat=0;
            
            if(!$member->validate()) {
                $response['status']='EROR';
                $response['message']='Password is not valid';
                
                return $response;
            }
            
            $member->write();
            
            
            $response['status']='HELO';
            $response['message']="User added successfully";
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Gets the list of languages with snippet counts
     * @return {array} Standard response base
     */
    public function getAdminLanguages() {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        $languages=SnippetLanguage::get();
        foreach($languages as $lang) {
            $response['data'][]=array(
                                    'id'=>$lang->ID,
                                    'language'=>$lang->Name,
                                    'file_extension'=>$lang->FileExtension,
                                    'shjh_code'=>$lang->HighlightCode,
                                    'user_language'=>$lang->UserLanguage,
                                    'snippetCount'=>$lang->getSnippets()->Count()
                                );
        }
        
        
        return $response;
    }
    
    /**
     * Creates a language in the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function createLanguage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        try {
            if(SnippetLanguage::get()->where("Name LIKE '".Convert::raw2sql($data->language)."'")->Count()>0) {
                $response['status']='EROR';
                $response['message']='Language already exists';
                
                return $response;
            }
            
            
            $lang=new SnippetLanguage();
            $lang->Name=$data->language;
            $lang->FileExtension=$data->fileExtension;
            $lang->HighlightCode='Plain';
            $lang->UserLanguage=1;
            $lang->write();
            
            
            $response['status']='HELO';
            $response['message']="Language added successfully";
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Deletes a language from the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function deleteLanguage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        try {
            $lang=SnippetLanguage::get()->byID(intval($data->id));
            if(!empty($lang) && $lang!==false && $lang->ID!=0) {
                if($lang->UserLanguage==false || $lang->getSnippets()->Count()>0) {
                    $response['status']='EROR';
                    $response['message']='Language cannot be deleted, it is either not a user language or has snippets attached to it';
                    
                    return $response;
                }
            }else {
                $response['status']='EROR';
                $response['message']='Language not found';
                
                return $response;
            }
            
            
            $lang->delete();
            
            
            $response['status']='HELO';
            $response['message']="Language deleted successfully";
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Edits a language
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function editLanguage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Permission Denied';
            return $response;
        }
        
        
        try {
            $lang=SnippetLanguage::get()->where("Name LIKE '".Convert::raw2sql($data->language)."' AND ID<>=".intval($data->id));
            if(empty($lang) || $lang===false || $lang->ID==0) {
                $response['status']='EROR';
                $response['message']='Language already exists';
            
                return $response;
            }
            
            
            $lang=SnippetLanguage::get()->byID(intval($data->id));
            if(!empty($lang) && $lang!==false && $lang->ID!=0) {
                if($lang->UserLanguage==false) {
                    $response['status']='EROR';
                    $response['message']='Language cannot be edited, it is not a user language';
                    
                    return $response;
                }
            }else {
                $response['status']='EROR';
                $response['message']='Language not found';
                
                return $response;
            }
            
            
            //Update language and write
            $lang->Name=$data->language;
            $lang->FileExtension=$data->fileExtension;
            $lang->write();
            
            
            $response['status']='HELO';
            $response['message']="Language edited successfully";
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
}
?>