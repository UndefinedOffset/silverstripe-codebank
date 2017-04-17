<?php
class CodeBankAdministration implements CodeBank_APIClass
{
    /**
     * Gets a list of users in the database
     * @return {array} Returns a standard response array
     */
    public function getUsersList()
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        $members=Permission::get_members_by_permission(array('ADMIN', 'CODE_BANK_ACCESS'));
        foreach ($members as $member) {
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
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function deleteUser($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $member=Member::get()->filter('ID', intval($data->id))->filter('ID:not', Member::currentUserID())->First();
            if (!empty($member) && $member!==false && $member->ID!=0) {
                $member->delete();
            }
            
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.USER_DELETED', '_User deleted successfully');
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Changes a users password
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function changeUserPassword($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $member=Member::get()->byID(intval($data->id));
            if (empty($member) || $member===false || $member->ID==0) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.MEMBER_NOT_FOUND', '_Member not found');
                
                return $response;
            }
            
            if (!$member->changePassword($data->password)) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.NEW_PASSWORD_NOT_VALID', '_New password is not valid');
                
                return $response;
            }
            
            
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.PASSWORD_CHANGED', '_User\'s password changed successfully');
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Creates a user in the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function createUser($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            if (Member::get()->filter('Email', Convert::raw2sql($data->username))->count()>0) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.EMAIL_EXISTS', '_An account with that email already exists');
                
                return $response;
            }
            
            
            //Create and write member
            $member=new Member();
            $member->FirstName=(!empty($data->firstname) ? $data->firstname:$data->username);
            $member->Surname=(!empty($data->surname) ? $data->surname:null);
            $member->Email=$data->username;
            $member->Password=$data->Password;
            $member->UseHeartbeat=0;
            
            if (!$member->validate()) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.PASSWORD_NOT_VALID', '_Password is not valid');
                
                return $response;
            }
            
            $member->write();
            
            
            $response['status']='HELO';
            $response['message']="User added successfully";
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Gets the list of languages with snippet counts
     * @return {array} Standard response base
     */
    public function getAdminLanguages()
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        $languages=SnippetLanguage::get();
        foreach ($languages as $lang) {
            $response['data'][]=array(
                                    'id'=>$lang->ID,
                                    'language'=>$lang->Name,
                                    'file_extension'=>$lang->FileExtension,
                                    'shjh_code'=>$lang->HighlightCode,
                                    'user_language'=>$lang->UserLanguage,
                                    'snippetCount'=>$lang->Snippets()->Count(),
                                    'hidden'=>$lang->Hidden
                                );
        }
        
        
        return $response;
    }
    
    /**
     * Creates a language in the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function createLanguage($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            if (SnippetLanguage::get()->filter('Name:nocase', Convert::raw2sql($data->language))->Count()>0) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists');
                
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
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Deletes a language from the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function deleteLanguage($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $lang=SnippetLanguage::get()->byID(intval($data->id));
            if (!empty($lang) && $lang!==false && $lang->ID!=0) {
                if ($lang->canDelete()==false) {
                    $response['status']='EROR';
                    $response['message']=_t('CodeBankAPI.LANGUAGE_DELETE_ERROR', '_Language cannot be deleted, it is either not a user language or has snippets attached to it');
                    
                    return $response;
                }
            } else {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.LANGUAGE_NOT_FOUND', '_Language not found');
                
                return $response;
            }
            
            
            $lang->delete();
            
            
            $response['status']='HELO';
            $response['message']="Language deleted successfully";
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Edits a language
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function editLanguage($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            if (SnippetLanguage::get()->filter('Name:nocase', Convert::raw2sql($data->language))->Count()>0) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.LANGUAGE_EXISTS', '_Language already exists');
            
                return $response;
            }
            
            
            $lang=SnippetLanguage::get()->byID(intval($data->id));
            if (empty($lang) || $lang===false || $lang->ID==0) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.LANGUAGE_NOT_FOUND', '_Language not found');
                
                return $response;
            }
            
            
            //Update language and write
            if ($lang->UserLanguage==true) {
                $lang->Name=$data->language;
                $lang->FileExtension=$data->fileExtension;
            }
            
            $lang->Hidden=$data->hidden;
            $lang->write();
            
            
            $response['status']='HELO';
            $response['message']="Language edited successfully";
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Gets the permissions required to access the class
     * @return {array} Array of permission names to check
     */
    public function getRequiredPermissions()
    {
        return array(
                    'CODE_BANK_ACCESS',
                    'ADMIN'
                );
    }
}
