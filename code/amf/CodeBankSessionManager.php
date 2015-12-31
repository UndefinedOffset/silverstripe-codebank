<?php
class CodeBankSessionManager implements CodeBank_APIClass
{
    /**
     * Attempt to login
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function login($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        $response['login']=true;
        
        //Try to login
        $member=MemberAuthenticator::authenticate(array(
                                                        'Email'=>$data->user,
                                                        'Password'=>$data->pass
                                                    ));
        
        if ($member instanceof Member && $member->ID!=0 && Permission::check('CODE_BANK_ACCESS', 'any', $member)) {
            try {
                $member->logIn();
                
                $ipAgrement=CodeBankConfig::CurrentConfig()->IPAgreement;
                
                
                //Get preferences
                $prefs=new stdClass();
                $prefs->heartbeat=$member->UseHeartbeat;
                
                //Set the response to HELO
                $response['status']='HELO';
                $response['message']=_t('CodeBankAPI.WELCOME_USER', '_Welcome {user}', array('user'=>htmlentities($member->Name))); //Set the message to "Welcome ...."
                $response['data']=array(
                                        'id'=>Member::currentUserID(),
                                        'hasIPAgreement'=>!empty($ipAgrement),
                                        'preferences'=>$prefs,
                                        'isAdmin'=>(Permission::check('ADMIN')!==false),
                                        'displayName'=>(trim($member->Name)=='' ? $member->Email:trim($member->Name))
                                    );
            } catch (Exception $e) {
                //Something happend on the server
                $response['status']='EROR';
                $response['message']=_t('CodeBankAPI.SERVER_ERROR', '_Server error has occured, please try again later');
            }
        } else {
            //Bad username/pass combo
            $response['status']='EROR';
            $response['message']=_t('CodeBankAPI.INVALID_LOGIN', '_Invalid Login');
        }
        
        
        return $response;
    }
    
    /**
     * Closes the users session
     * @return {array} Default response base
     */
    public function logout()
    {
        $response=CodeBank_ClientAPI::responseBase();
        
        //Session now expired
        $response['session']='expired';
        
        $member=Member::currentUser();
        if ($member) {
            $member->logOut();
        }
        
        return $response;
    }
    
    /**
     * Method for allowing a user to reset their password
     * @param {stdClass} $data Data passed from ActionScript
     * @return {array} Returns a standard response array
     */
    public function lostPassword($data)
    {
        $response=CodeBank_ClientAPI::responseBase();
        $response['login']=true;
        
        
        $SQL_email=Convert::raw2sql($data->user);
        $member=Member::get_one('Member', "\"Email\"='{$SQL_email}'");

        // Allow vetoing forgot password requests
        $sng=new MemberLoginForm(Controller::has_curr() ? Controller::curr():singleton('Controller'), 'LoginForm');
        $results=$sng->extend('forgotPassword', $member);
        if ($results && is_array($results) && in_array(false, $results, true)) {
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.PASSWORD_SENT_TEXT', "A reset link has been sent to '{email}', provided an account exists for this email address.", array('email'=>$data['Email']));
        }

        if ($member) {
            $token=$member->generateAutologinTokenAndStoreHash();

            $e=Member_ForgotPasswordEmail::create();
            $e->populateTemplate($member);
            $e->populateTemplate(array(
                'PasswordResetLink'=>Security::getPasswordResetLink($member, $token)
            ));
            $e->setTo($member->Email);
            $e->send();

            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.PASSWORD_SENT_TEXT', "A reset link has been sent to '{email}', provided an account exists for this email address.", array('email'=>$data->user));
        } elseif (!empty($data->user)) {
            $response['status']='HELO';
            $response['message']=_t('CodeBankAPI.PASSWORD_SENT_TEXT', "A reset link has been sent to '{email}', provided an account exists for this email address.", array('email'=>$data->user));
        } else {
            $response['status']='EROR';
            $response['message']=_t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.');
        }
        
        
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
