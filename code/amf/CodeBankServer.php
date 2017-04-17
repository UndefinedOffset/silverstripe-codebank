<?php
class CodeBankServer implements CodeBank_APIClass
{
    /**
     * Gets the ip message
     * @return {array} Standard response base
     */
    public function getIPMessage()
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        $response['data']=CodeBankConfig::CurrentConfig()->IPMessage;
        
        return $response;
    }
    
    /**
     * Saves the IP Message
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function saveIPMessage($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        if (!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.PERMISSION_DENINED', '_Permission Denied');
            return $response;
        }
        
        
        try {
            $codeBankConfig=CodeBankConfig::CurrentConfig();
            $codeBankConfig->IPMessage=$data->message;
            $codeBankConfig->write();
            
            
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.IP_MESSAGE_CHANGE', '_Intellectual Property message changed successfully');
        } catch (Exception $e) {
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
        }
        
        return $response;
    }
    
    /**
     * Handles heartbeat requests
     * @return {array} Standard response base
     */
    public function heartbeat()
    {
        return CodeBank_ClientAPI::responseBase();
    }
    
    /**
     * Saves users server preferences
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function savePreferences($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $member=Member::currentUser();
            if ($member && $member->ID!=0) {
                $member->UseHeartbeat=($data->heartbeat==1 ? true:false);
                $member->write();
            } else {
                throw new Exception('Not Logged In!');
            }
            
            
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.PREFERENCES_SAVED', '_Preferences saved successfully');
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
    public function changePassword($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $member=Member::currentUser();
            
            $e=PasswordEncryptor::create_for_algorithm($member->PasswordEncryption);
            if (!$e->check($member->Password, $data->currPassword, $member->Salt, $member)) {
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.CURRENT_PASSWORD_MATCH', '_Current password does not match');
                
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
     * Gets the permissions required to access the class
     * @return {array} Array of permission names to check
     */
    public function getRequiredPermissions()
    {
        return array(
                    'CODE_BANK_ACCESS'
                );
    }
}

class CodeBankServerController implements CodeBank_APIClass
{
    
    public function connect()
    {
        $response=CodeBank_ClientAPI::responseBase();
    
        $response['login']=true;
        $response['data']=explode(' ', singleton('CodeBank')->getVersion());
        return $response;
    }
    
    /**
     * Gets the current php session id
     */
    public function getSessionId()
    {
        $response=CodeBank_ClientAPI::responseBase();
    
        $response['data']=session_id();
    
        return $response;
    }
    
    /**
     * Gets the permissions required to access the class
     * @return {array} Array of permission names to check
     */
    public function getRequiredPermissions()
    {
        return null;
    }
}
