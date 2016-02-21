<?php
namespace pirrs;
class RegistrationPage extends PageObject{
	private $registrationSuccessful = false;

	public function pageTitle(){
		echo "Registration";
	}

	public function preExecute(){
		if($this->request->user->isLoggedIn()){
			//$this->setResult(302,'/');
            $this->response->forwardTo('/'); //Forward the user home, they're already logged in.
			return false;
		}
		if($this->request->issetReq('submit_registration')){
			$this->registrationSuccessful = $this->doRegistration();
		}
	}

	public function registrationSuccessful(){
		return $this->registrationSuccessful;
	}

	public function doRegistration(){
		$isset = $this->request->issetReqList('full_name','addressing_name','email','password','password_conf');
		if($isset !== true){
			$this->response->addError('Request is missing the following ' . ((count($isset)==1)?'field':'fields') . ': ' . implode(', ',$isset));
			return false;
		}

		list($fullName, $addressingName, $emailAddress, $password, $passwordConf) = $this->request->getReqList('full_name','addressing_name','email','password','password_conf');
		/* Perform some validation on the different inputs */
		if(($fullName = \pirrs\Utilities\Validation::isValidName($fullName,Config::input('full_name_max_length'),Config::input('name_min_length'))) === false){
			$this->response->addError('There is a problem with your full name! Maybe it contains some invalid characters?');
		}
		if(($addressingName = \pirrs\Utilities\Validation::isValidName($addressingName,Config::input('addressing_name_max_length'),Config::input('name_min_length'))) === false){
			$this->response->addError('There is a problem with the name you said we should address you with! Maybe it contains some invalid characters?');
		}
		if(!\pirrs\Utilities\Validation::isValidEmail($emailAddress,Config::input('email_max_length'),Config::input('email_min_length'))){
			$this->response->addError('Your email address does not appear to be valid!');
		}
		if(($emailCheck = $this->dbCon->checkIfEmailExists($emailAddress)) !== 0){
			if($emailCheck === 1){
				$this->response->addError('An account already exists associated with this email!');
			}
			elseif($emailCheck === false){
				$this->response->addError('Something has gone wrong! Please try to register again.');
			}
		}
		if(!\pirrs\Utilities\Validation::isValidLength($password,Config::input('password_max_length'),Config::input('password_min_length'))){
			$this->response->addError('Your password is either too short or too long. Make sure it is at least ' . Config::input('password_min_length') . ' characters long, maximum length ' . Config::input('password_max_length') . ' characters.');
		}
		if($password !== $passwordConf){
			$this->response->addError('Your passwords do not match!');
		}

		if($this->response->hasErrors()){
			return false;
		}

        $uid = $this->generateNewUid();

		$registeredUid = $this->dbCon->registerNewUser($uid, $fullName, $addressingName, $emailAddress, $password);
		if($registeredUid !== false){
			$this->request->user->giveCredentials($registeredUid);
			return true;
		}
		else{
			$this->response->addError('There was an error during registration! Please try again.');
			return false;
		}
	}

    private function generateNewUid(){
        //Do something there. Or don't. That's up to you.
        return 1;
    }
}
?>
