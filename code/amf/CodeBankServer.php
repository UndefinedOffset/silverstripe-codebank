<?php
class CodeBankServer implements CodeBank_APIClass {
    /**
     * Gets the ip message
     * @return {array} Standard response base
     */
    public function getIPMessage() {
        $response=CodeBank_ClientAPI::responseBase();
        
        $response['data']=CodeBankConfig::CurrentConfig()->IPMessage;
        
        return $response;
    }
    
    /**
     * Saves the IP Message
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function saveIPMessage($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        if(!Permission::check('ADMIN')) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $codeBankConfig=CodeBankConfig::CurrentConfig();
            $codeBankConfig->IPMessage=$data->message;
            $codeBankConfig->write();
            
            
            $response['status']='HELO';
            $response['message']='Intellectual Property message changed successfully';
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Handles heartbeat requests
     * @return {array} Standard response base
     */
    public function heartbeat() {
        return CodeBank_ClientAPI::responseBase();
    }
    
    /**
     * Saves users server preferences
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function savePreferences($data) {
        $response=CodeBank_ClientAPI::responseBase();
        
        try {
            $member=Member::currentUser();
            if($member && $member->ID!=0) {
                $member->UseHeartbeat=($data->heartbeat==1 ? true:false);
                $member->write();
            }else {
                throw new Exception('Not Logged In!');
            }
            
            
            $response['status']='HELO';
            $response['message']='Preferences saved successfully';
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
}

class CodeBankServerController implements CodeBank_APIClass {
    
    public function connect() {
        $response=CodeBank_ClientAPI::responseBase();
    
        $response['login']=true;
        $response['data']=array(CB_VERSION, CB_BUILD_DATE);
        return $response;
    }
    
    /**
     * Gets the current php session id
     */
    public function getSessionId() {
        $response=CodeBank_ClientAPI::responseBase();
    
        $response['data']=session_id();
    
        return $response;
    }
}
?>