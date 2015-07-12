<?php
namespace pirrs;
class CurrentUser extends User{	
	private $_isLoggedIn = false; //If the user has been validated as being logged in.
	private $_hasCheckedAuthentication = false; //If the class has checked yet whether the user is logged in. 
	
	
	/*
	 * Constructor for the Authentication class
	 */
	public function __construct(){
		if(utilities\Session::isSessionStarted() === false){
			session_start();
		}
		
		$this->dbCon = ResourceManager::getDatabaseController();
		
		$this->checkAuthentication();
		if($this->isLoggedIn()){
			$this->renewSession();
		}
		
		parent::__construct();
	}
	
	private function checkAuthentication(){
		$this->_isLoggedIn = $this->hasOAuthAuthentication() || $this->hasAuthentication();
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
		
		$this->setClassCredentials($_SESSION['USER_ID']);
		return true;
	}
	
	private function hasOAuthAuthentication(){
		/* Implement code to check OAuth credentials here */
		return false;
	}
	
	private function isValidUser($uid){
		if(HAS_DATABASE){
			return $this->dbCon->isValidUid($uid);
		}
		//If there is no database, then there is no "valid" users.
		//This function should be modified as necessary on a per-application basis. 
		return true;
	}
	
	private function sessionNotExpired(){
		if(!isset($_SESSION['LAST_USE']) || time() - $_SESSION['LAST_USE'] >= SESSION_EXPIRATION_AGE){
			return false;
		}
		return true;
	}
	
	/** Update the LAST_USE time of the session, and regenerate the session ID **/
	private function renewSession(){
		session_regenerate_id();
		$_SESSION['LAST_USE'] = time();
	}
	
	public function isLoggedIn(){
		return $this->_isLoggedIn;
	}
	
	public function giveCredentials($uid){
		$_SESSION['USER_ID'] = $uid;
		$_SESSION['LAST_USE'] = time();
		$this->setClassCredentials($uid);
	}
	
	private function setClassCredentials($uid){
		$this->uid = $uid;
		$this->getUserInformation();
	}
	
	private function cleanSessionData(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
	}
	
	public function logOut(){
		unset($_SESSION['USER_ID']);
		unset($_SESSION['LAST_USE']);
		session_unset();
		session_destroy();
	}
}
?>