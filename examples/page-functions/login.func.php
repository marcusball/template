<?php
namespace pirrs;
class LoginPage extends PageObject{
	private $loginSuccessful = false;

	public function pageTitle(){
		echo "Log in";
	}

	public function preExecute(){
		if($this->request->user->isLoggedIn()){
			$this->response->forwardTo('/');
			return false;
		}
		if($this->request->issetReq('email','password')){
			$this->loginSuccessful = $this->doLogin();

            if($this->request->issetReq('destination')){
                $dest = $this->request->getReq('destination');

                $this->response->forwardTo('//' . SITE_DOMAIN . $dest);
            }
		}
	}

	public function loginSuccessful(){
		return $this->loginSuccessful;
	}

	public function doLogin(){
		$isset = $this->request->issetReqList('email','password');
		if($isset !== true){
			$this->addError('Request is missing the following ' . ((count($isset)==1)?'field':'fields') . ': ' . implode(', ',$isset));
			return false;
		}

		list($emailAddress, $password) = $this->request->getReqList('email','password');
		/* Perform some validation on the different inputs */

		if(!\pirrs\utilities\Validation::isValidEmail($emailAddress,Config::input('email_max_length'),Config::input('email_min_length'))){
			$this->addError('Your email address does not appear to be valid!');
		}
		if(($emailCheck = $this->dbCon->checkIfEmailExists($emailAddress)) !== 1){
			if($emailCheck === 0){
				$this->addError('Your email or password is incorrect!');
			}
			elseif($emailCheck === false){
				$this->addError('Something has gone wrong! Please try to register again.');
			}
		}

		if($this->response->hasErrors()){
			return false;
		}

		$loginReturn = $this->dbCon->isValidLogin($emailAddress,$password);
		if($loginReturn !== false && $loginReturn > 0){ //Successful
			$userid = $loginReturn; //isValidLogin returns the UID value of the user on success
			//debug("Welcome, user {$userid}!");

			$this->request->user->giveCredentials($userid);

			return true;
		}
		else{
			if($loginReturn === 0){ //Invalid email or password
				$this->addError('Your email or password is incorrect!');
			}
			elseif($loginReturn === -1){
				/** Someone has failed to provide authentication for this account too many times and it has been locked
				 ** We will provide a message saying how much time the user must wait before trying to log in again **/
				$uid = $this->dbCon->getUidFromEmail($emailAddress);
				$seconds = ($this->dbCon->getLastAttemptTime($uid) + DISABLED_ACCOUNT_PERIOD) - time();
				$minutes = floor($seconds / 60);
				$remainingSeconds = $seconds % 60;
				$minString = ($minutes > 1) ? "$minutes minutes, " : (($minutes == 0) ? "" : "$minutes minute, ");
				$secString = ($seconds > 1) ? "$remainingSeconds seconds" : (($seconds == 0) ? "" : "$remainingSeconds second ");
				$this->addError("Your account has been locked due to too many failed login attempts. You must wait $minString $secString before you may try to log in!");
			}
			else{ //return === false; an error occurred
				$this->addError('Something went wrong while trying to log you in! Please try again.');
			}
			return false;
		}
	}
}
?>
