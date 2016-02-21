<?php
namespace pirrs;
use \PDO;
class ResourceManager{
	/** Create an SQL connection **/
	private static $USER = null;
	private static $FORMKEYMAN = null;
	private static $OAUTHMANAGER = null;

	/*
	* Access method for receiving a reference to the database controller (DatabaseController).
	*/
	public static function getDatabaseController(){
		Log::warning('ResourceManager::getDatabaseController() is deprecated! Use DatabaseController::get()');
		return DatabaseController::get();
	}

	/*
	 * Access method for receiving a reference to the CurrentUser object.
	 */
	public static function getCurrentUser(){
		if(static::$USER == null){
			self::$USER = new CurrentUser();
			self::$USER->initialize();
		}
		return self::$USER;
	}

	/*
	 * Access method for receiving a reference to the FormKeyManager object.
	 */
	public static function getFormKeyManager(){
		if(self::$FORMKEYMAN == null){
			self::$FORMKEYMAN = new FormKeyManager();
		}
		return self::$FORMKEYMAN;
	}

	/*
	 * Access method for receiving a reference to an OAuthManager object for the current user.
	 */
	public static function getOAuthManager(){
		if(self::$OAUTHMANAGER == null){
			$user = static::getCurrentUser();
			self::$OAUTHMANAGER = new OAuthManager($user);
		}
		return self::$OAUTHMANAGER;
	}
}
?>
