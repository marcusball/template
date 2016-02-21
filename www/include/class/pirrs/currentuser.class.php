<?php
namespace pirrs;
use pirrs\utilities\Session;
class CurrentUser extends User{
	private $_isLoggedIn = false; //If the user has been validated as being logged in.
	private $_hasCheckedAuthentication = false; //If the class has checked yet whether the user is logged in.


	/***********************************************************/
	/* Construction methods                                    */
	/***********************************************************/
	public function __construct(){
		parent::__construct();
	}

    public function initialize(){
      if(Session::isSessionStarted() === false){
				Session::startSession();
			}

      $this->checkAuthentication();
			if($this->isLoggedIn()){
				$this->renewSession();
			}
    }

    /***********************************************************/
	/* Information methods (public functions)                  */
	/***********************************************************/

	/*
     * Check if the the current session belongs to a logged in user.
     */
	public function isLoggedIn(){
		return $this->_isLoggedIn;
	}

    /*
     * Log a user in, with the credentials of user $uid.
     */
	public function giveCredentials($uid){
		$_SESSION['USER_ID'] = $uid;
		$_SESSION['LAST_USE'] = time();
		$this->setClassCredentials($uid);
	}

    /*
     * Log the user out, end the session
     */
	public function logOut(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
		session_unset();
		session_destroy();
    }

    /***********************************************************/
	/* Helper methods (private functions)                      */
	/***********************************************************/
	private function checkAuthentication(){
		$this->_isLoggedIn = $this->hasAuthentication();
		$this->_hasCheckedAuthentication = true;
	}

	private function hasAuthentication(){
		if(!isset($_SESSION['USER_ID']) || (trim($_SESSION['USER_ID'])=='')) {
			return false;
		}
		if(!$this->sessionNotExpired()){
			$this->cleanSessionData();
			return false;
		}

		if(!$this->isValidUser($_SESSION['USER_ID'])){
			$this->cleanSessionData();
			return false;
		}

        if(OAUTH_CLIENT_ENABLE){
            $oauth = ResourceManager::getOAuthManager();
            if($oauth->accessTokenIsExpired()){
                return false;
            }
        }

		$this->setClassCredentials($_SESSION['USER_ID']);
		return true;
	}

	/*
     * Check if the $uid stored in a user's session is valid.
     */
	private function isValidUser($uid){
		if(Config::database('enable')){
			$dbCon = ResourceManager::getDatabaseController();
			return $dbCon->isValidUid($uid);
		}
		//If there is no database, then there is no "valid" users.
		//This function should be modified as necessary on a per-application basis.
		return true;
	}

	private function sessionNotExpired(){
		if(!isset($_SESSION['LAST_USE']) || time() - $_SESSION['LAST_USE'] >= Config::user('session_expiration_age')){
			return false;
		}
		return true;
	}

	/** Update the LAST_USE time of the session, and regenerate the session ID **/
	private function renewSession(){
		session_regenerate_id();
		$_SESSION['LAST_USE'] = time();
	}

	private function setClassCredentials($uid){
		$this->uid = $uid;
		$this->getUserInformation();
	}

	private function cleanSessionData(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
	}
}
?>
