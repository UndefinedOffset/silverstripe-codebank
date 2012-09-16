<?php
class CodeBankSessionManager implements CodeBank_APIClass {
	/**
	 * Attempt to login
	 * @param {stdClass} $data Data passed from ActionScript from ActionScript
	 * @return {array} Returns a standard response array
	 */
	public function login($data) {
		$response=CodeBank_ClientAPI::responseBase();
		
		$response['login']=true;
	    
		//Try to login
		$member=MemberAuthenticator::authenticate(array(
	                                                    'Email'=>$data->user,
                                                        'Password'=>$data->pass
	                                                ), false);
		
		if($member instanceof Member && $member->ID!=0) {
		    try {
    		    $member->logIn();
    		    
                $ipAgrement=CodeBankConfig::CurrentConfig()->IPAgreement;
		        
                
                //Get preferences
                $prefs=new stdClass();
                $prefs->heartbeat=$member->UseHeartbeat;
                
		        //Set the response to HELO
		        $response['status']='HELO';
		        $response['message']='Welcome '.htmlentities($member->Name); //Set the message to "Welcome ...."
		        $response['data']=array(
                		                'id'=>Member::currentUserID(),
                		                'hasIPAgreement'=>!empty($ipAgrement),
                		                'preferences'=>$prefs
            		                );
		    }catch (Exception $e) {
		    	//Something happend on the server
		        $response['status']='EROR';
		        $response['message']='Internal Error Occured, Please try again later';
		    }
		}else {
			//Bad username/pass combo
		    $response['status']='EROR';
            $response['message']='Invalid Login';
		}
		
		
		return $response;
	}
	
	/**
     * Closes the users session
     * @return {array} Default response base
     */
	public function logout() {
		$response=CodeBank_ClientAPI::responseBase();
		
		//Session now expired
		$response['session']='expired';
		
		//Clear the user data
		$_SESSION['user']=null;
		$_SESSION['id']=null;
		
		$member=Member::currentUser();
		if($member) {
		    $member->logOut();
		}
		
		return $response;
    }
}
?>