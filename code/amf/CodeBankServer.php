<?php
class CodeBankServer implements CodeBank_APIClass {
    public function connect() {
        $response=responseBase();
        
        $response['login']=true;
        $response['data']=array(CB_VERSION, CB_BUILD_DATE);
        return $response;
    }
    
    /**
     * Gets the current php session id
     */
    public function getSessionId() {
        $response=responseBase();
        
        $response['data']=session_id();
        
        return $response;
    }
    
    /**
     * Gets the ip message
     * @return {array} Standard response base
     */
    public function getIPMessage() {
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT *
                FROM settings
                WHERE code='ipMessage'";
        $response['data']=$conn->Execute($query)->fields['value'];
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Saves the IP Message
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function saveIPMessage($data) {
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $conn->Execute("UPDATE settings SET value='".Convert::raw2sql($data->message)."' WHERE code='ipMessage'");
            
            $conn->close();
            
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
        return responseBase();
    }
    
    /**
     * Saves users server preferences
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Standard response base
     */
    public function savePreferences($data) {
        $response=responseBase();
        
        try {
            //Open db connection
            $conn=openDB();
            
            
            //Write heartbeat preference
            $conn->Execute("UPDATE preferences SET value=".intval($data->heartbeat)." WHERE code='heartbeat' AND fkUser=".$_SESSION['id']);
            
            
            //Close db connection
            $conn->Close();
            
            $response['status']='HELO';
            $response['message']='Preferences saved successfully';
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
}
?>