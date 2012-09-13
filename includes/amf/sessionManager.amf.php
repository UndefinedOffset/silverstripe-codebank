<?php
require_once(dirname(__FILE__).'/../amf.inc.php');

class SessionManager {
	/**
	 * Attempt to login
	 * @param {stdClass} $data Data passed from ActionScript from ActionScript
	 * @return {array} Returns a standard response array
	 */
	public function login($data) {
		$response=responseBase();
		
		$response['login']=true;
	    
		//Opens the connection to the db
		$conn=openDB();
		
		//Check users login
		$query="SELECT id,username
		        FROM users
		        WHERE username='".encSQLString($data->user)."' AND password='".encSQLString(sha1($data->pass))."' AND deleted='0'
		        LIMIT 1";
		$result=$conn->Execute($query);
		
		if($result->recordCount()==1) {
		    $id=$result->fields['id']; //Store the user id
		    $user=$result->fields['username']; //Store the username
		    
		    $time=date('Y-m-d H:i'); //Get the current time
		    
		    $key=md5($user.$id.$_SERVER['REMOTE_ADDR'].$time); //Generate the users login key based on a md5 of the username, id, remote ip, and the current time
		    
		    try {
		        //Update the users record with the current session data
		        $query="UPDATE users
		                SET loginKey='".encSQLString($key)."',
		                    lastLogin='".encSQLString($time)."',
		                    lastLoginIP='".encSQLString($_SERVER['REMOTE_ADDR'])."'
		                WHERE id=".encSQLInt($id);
		        $conn->Execute($query);
		        
		        //Store session data
		        $_SESSION['user']=$user;
		        $_SESSION['id']=$id;
		        $_SESSION['loginKey']=$key;
		        $_SESSION['loginTime']=$time;
		        
		        //Get the ip agreement
                $query="SELECT *
                        FROM settings
                        WHERE code='ipMessage'";
                $ipAgrement=$conn->Execute($query)->fields['value'];
		        
                
                //Get preferences
                $prefs=new stdClass();
                $query='SELECT code, value '.
                       'FROM preferences '.
                       'WHERE fkUser='.$id;
                $result=$conn->Execute($query);
                foreach($result as $row) {
                    $code=$row['code'];
                    
                    if($code=='heartbeat') {
                        $prefs->$code=($row['value']==1);
                    }else {
                        $prefs->$code=$row['value'];
                    }
                }
                
                $result->Free();
                
                
		        //Set the response to HELO
		        $response['status']='HELO';
		        $response['message']='Welcome '.encXMLChars($_SESSION['user']); //Set the message to "Welcome ...."
		        $response['data']=array('id'=>$id,'hasIPAgreement'=>!empty($ipAgrement),'preferences'=>$prefs);
		    }catch (Exception $e) {
		    	//Something happend on the server
		        $response['status']='EROR';
		        $response['message']='Internal Error Occured, Please try again later';
		    }
		}else {
			//Bad username/pass combo
		    $response['status']='EROR';
            $response['message']='Incorrect Password';
		}
		
		$conn->close();
		
		
		return $response;
	}
	
	/**
     * Closes the users session
     * @return {array} Default response base
     */
	public function logout() {
		$response=responseBase();
		
		//Session now expired
		$response['session']='expired';
		
		//Clear the user data
		$_SESSION['user']=null;
		$_SESSION['id']=null;
		$_SESSION['loginKey']=null;
		$_SESSION['loginTime']=null;
		
		//Unset the session
		session_unset();
		
		//Destroy the Session
		session_destroy();
		
		return $response;
    }
}
?>