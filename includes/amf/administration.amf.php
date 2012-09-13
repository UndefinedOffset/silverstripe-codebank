<?php
class Administration {
    /**
     * Gets a list of users in the database
     * @return {array} Returns a standard response array
     */
    public function getUsersList() {
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        $conn=openDB();
        
        $query="SELECT id,username,lastLogin,lastLoginIP,deleted
                FROM users";
        $response['data']=$conn->Execute($query)->getAll();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Deletes a user
     * @param {stdClass} $data Data passed from ActionScript from ActionScript
     * @return {array} Returns a standard response array
     */
    public function deleteUser($data) {
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $conn->Execute("UPDATE users SET deleted='1' WHERE id=".encSQLInt($data->id)." AND username<>'admin'");
            
            $conn->close();
            
            $response['status']='HELO';
            $response['message']='User deleted successfully';
        }catch (Exception $e) {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
        }
        
        return $response;
    }
    
    /**
     * Undeletes a user
     * @param {stdClass} $data Data passed from ActionScript from ActionScript
     * @return {array} Returns a standard response array
     */
    public function undeleteUser($data) {
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $conn->Execute("UPDATE users SET deleted='0' WHERE id=".encSQLInt($data->id));
            
            $conn->close();
            
            $response['status']='HELO';
            $response['message']='User undeleted successfully';
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
        $response=responseBase();
        
        try {
            $conn=openDB();
            
            if($_SESSION['user']!='admin') {
                $result=$conn->Execute('SELECT password '.
                                       'FROM users '.
                                       'WHERE id='.encSQLInt($data->id));
                $result=$result->fetchRow();
                
                if($result['password']!=sha1($data->currPassword)) {
                    $response['status']='EROR';
                    $response['message']='Current password does not match';
                    
                    return $response;
                }
            }
            
            $conn->Execute("UPDATE users SET password='".encSQLString(sha1($data->password))."' WHERE id=".encSQLInt($data->id));
            
            $conn->close();
            
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
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $result=$conn->Execute("SELECT id FROM users WHERE username LIKE '".encSQLString($data->username)."'");
            
            if($result->recordCount()>0) {
                $response['status']='EROR';
                $response['message']='Username already exists';
                
                return $response;
            }else {
                $conn->Execute("INSERT INTO users (username,password) VALUES('".encSQLString($data->username)."','".encSQLString(sha1($data->password))."')");
                
                //Setup default preferences
                $conn->Execute("INSERT INTO preferences (fkUser,code,value) VALUE(".$conn->Insert_ID().",'heartbeat','0')");
            }
            
            $conn->close();
            
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
        $response=responseBase();
        
        $conn=openDB();
        
        $query="SELECT l.*, count(s.id) AS snippetCount
                FROM languages l
                    LEFT JOIN snippits s ON s.fkLanguage=l.id
                GROUP BY l.id
                ORDER BY l.language";
        $response['data']=$conn->Execute($query)->getAll();
        
        $conn->Close();
        
        return $response;
    }
    
    /**
     * Creates a language in the database
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function createLanguage($data) {
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $result=$conn->Execute("SELECT id FROM languages WHERE language LIKE '".encSQLString($data->language)."'");
            
            if($result->recordCount()>0) {
                $response['status']='EROR';
                $response['message']='Language already exists';
                
                return $response;
            }
            
            
            $conn->Execute("INSERT INTO languages(language,file_extension,shjs_code,user_language) VALUES('".encSQLString($data->language)."','".encSQLString($data->fileExtension)."','Plain',1)");
            
            $conn->close();
            
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
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $result=$conn->Execute("SELECT l.user_language, count(s.id) AS snippetCount
                                    FROM languages l
                                        LEFT JOIN snippits s ON s.fkLanguage=l.id
                                    WHERE l.id=".intval($data->id)."
                                    GROUP BY l.id");
            if($result && $result->recordCount()>0) {
                $result=$result->fetchRow();
                if($result['user_language']==false || $result['snippitCount']>0) {
                    $response['status']='EROR';
                    $response['message']='Language cannot be deleted, it is either not a user language or has snippets attached to it';
                    
                    return $response;
                }
            }else {
                $response['status']='EROR';
                $response['message']='Language not found';
                
                return $response;
            }
            
            
            $conn->Execute("DELETE FROM languages WHERE id=".intval($data->id));
            
            
            $conn->close();
            
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
        $response=responseBase();
        
        if($_SESSION['user']!='admin') {
            $response['status']='EROR';
            $response['message']='Internal server error has occured';
            return $response;
        }
        
        
        try {
            $conn=openDB();
            
            $result=$conn->Execute("SELECT id FROM languages WHERE language LIKE '".encSQLString($data->language)."' AND id<>".intval($data->id));
            
            if($result->recordCount()>0) {
                $response['status']='EROR';
                $response['message']='Language already exists';
            
                return $response;
            }
            
            
            $result=$conn->Execute("SELECT user_language
                                    FROM languages
                                    WHERE id=".intval($data->id));
            if($result && $result->recordCount()>0) {
                $result=$result->fetchRow();
                if($result['user_language']==false) {
                    $response['status']='EROR';
                    $response['message']='Language cannot be edited, it is not a user language';
                    
                    return $response;
                }
            }else {
                $response['status']='EROR';
                $response['message']='Language not found';
                
                return $response;
            }
            
            
            $conn->Execute("UPDATE languages
                            SET language='".encSQLString($data->language)."',
                                file_extension='".encSQLString($data->fileExtension)."'
                            WHERE id=".intval($data->id));
            
            
            $conn->close();
            
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