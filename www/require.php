<?php
namespace pirrs;
use \PDO;

/** Functions and definitions that will be included for every page **/
require 'config.php';

$path = dirname(__FILE__) . PATH_INCLUDE; 
set_include_path(get_include_path() . PATH_SEPARATOR . $path); //Adds the './include' folder to the include path
// That doesn't explain much, but basically, if I say "include 'file.php';", 
// it now searches './include' for file.php, as well as the default include locations.

/*
 * Includes all of the necessary helper classes and files.
 */
function init(){
	$classPath = dirname(__FILE__) . PATH_CLASS; //Get the path to our .class.php files 
	set_include_path(get_include_path() . PATH_SEPARATOR . $classPath); //Add that path to the include path
	
	spl_autoload_extensions('.class.php,.php'); //Auto-load any of our .class.php classes
	spl_autoload_register();
	require_once 'password.php'; 
	//require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';

	//Initialize the logging object
	$logOverrides = array();
	if(defined('SERVER_LOG_PATH_ERRORS')){
		$logOverrides['error'] = SERVER_LOG_PATH_ERRORS; 
	}
	if(defined('SERVER_LOG_PATH_WARNINGS')){
		$logOverrides['warning'] = SERVER_LOG_PATH_WARNINGS;
	}
	Log::construct(SERVER_LOG_PATH,$logOverrides);
}

/*
 * Here's a happy little log function.
 * Use it for errors.
 * $description is for a written description of the problem.
 * $error is for the output of error functions.
 * $debugIndex is the number of levels on the backtrace to use as the calling information. 
 *      ex: When $debugIndex = 0, the file path that gets logged is the file in which "logError()" appears. 
 *          While, when $debugIndex = 1, the file path that is logged is the file which called the function that contains logError() 
 *          Note, if the value is higher than the level returned by debug_backtrace, then it will decrement this value until a valid level is found.
 */
function logError($description, $error, $debugIndex = 1){
	Log::warning('logError() is depreciated! Please use Log::error().');
	Log::error($description, $error, $debugIndex);
}

/*
 * Nice little log function for warnings.
 * $description is for a written description of the problem.
 * $debugIndex is the number of levels on the backtrace to use as the calling information. 
 *      ex: When $debugIndex = 0, the file path that gets logged is the file in which "logError()" appears. 
 *          While, when $debugIndex = 1, the file path that is logged is the file which called the function that contains logError() 
 *          Note, if the value is higher than the level returned by debug_backtrace, then it will decrement this value until a valid level is found.
 */
function logWarning($description, $debugIndex = 1){
	Log::warning('logWarning() is depreciated! Please use Log::warning().');
	Log::warning($description, $debugIndex);
}

/*
 * Echo safe
 * Hopefully echos information in a way that is safe to echo
 */
function es($message){
	echo htmlspecialchars($message);
}
function debug($message){
	if(is_bool($message)){
		echo (($message === true)?'TRUE':'FALSE').' <br />';
	}
	else{
		echo $message . '<br />';
	}
}

class ResourceManager{
	/** Create an SQL connection **/
	private static $SQLCON;
	private static$USER;
	private static $FORMKEYMAN;

	/*
	 * Connects to a PDO database and returns an instance of DatabaseController, from databasecontroller.php
	 * DO NOT call this function directly to access the database. 
	 * This file calls it (in getDatabaseController() ONLY), and maintains a reference to the value.
	 * Call getDatabaseController() to get a reference to it. 
	 */
	public static function SQLConnect(){
		try {
			self::$SQLCON = new DatabaseController();
			self::$SQLCON->setPDOAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			define('HAS_DATABASE',true);
			return self::$SQLCON;
		}
		catch(PDOException $e){
			logError("Could not select database (".DB_NAME.").",$e->getMessage(),time());
		}
		
		define('HAS_DATABASE',false);
		return new NoDatabaseController();
	}

	/*
	 * Access method for receiving a reference to the database controller (DatabaseController).
	 */
	public static function getDatabaseController(){
		if(self::$SQLCON == null){
			//echo 'giving current dbCon';
			self::$SQLCON = self::SQLConnect();
		}
		return self::$SQLCON;
	}

	/*
	 * Access method for receiving a reference to the CurrentUser object. 
	 */
	public static function getCurrentUser(){
		if(self::$USER == null){
			self::$USER = new CurrentUser();
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
}

function parsePath($withQueryArgs = true){
	//http://stackoverflow.com/questions/16388959/url-rewriting-with-php
	$uri = rtrim( dirname($_SERVER['SCRIPT_NAME']), '/' );
	$uri = '/' . trim( str_replace( $uri, '', $_SERVER['REQUEST_URI'] ), '/' );
	$uri = urldecode( $uri );
	if(!$withQueryArgs){
		$matchVal = preg_match('#^(?\'path\'[^\?]*)(?:\?.*)?$#i',$uri,$matches);
		if($matchVal !== 0 && $matchVal !== false){
			return $matches['path'];
		}
	}
	return $uri;
}

function cleanPath($path){
	if($path == '/') return $path;
	
	$phpExt = str_replace('.','\.',REQUEST_PHP_EXTENSION); //Convert something like '.php' to '\.php'
	$matchVal = preg_match('#^/?(?:(?\'path\'[^\?]+)'.$phpExt.')?(?:\?.*)?$#i',$path,$matches);
	if($matchVal === 0 || $matchVal === false){
		return false;
	}
	
	//If we get to here, we know the pattern matches
	//If path is not set, then nothing exists between the first character ('/'), and the query string ('?...')
	//So, if we have a path returned from the regex, then the url is something like "/xxxxx.php?ffffff"
	//Otherwise the path is "/?ffffff". 
	if(isset($matches['path'])){
		return $matches['path'];
	}
	else{
		return '/';
	}
}

function getRewritePath($path){
	global $REWRITE_RULES; //get rewrite rules from config.php
	foreach($REWRITE_RULES as $file=>$rule){
		if($file != null && $rule != null){ //If there is a full rewrite rule; "file.php" => "/some/rewrite/rule"
			$match = preg_match('#'.$rule.'#i',$path,$matches);
			if($match !== 0 && $match !== false){
				return array($file,$matches);
			}
		}
		else{ //Otherwise
			if($rule === null){ //in the case of: "file.php" => null
				if($path == $file || $path == '/'.$file || ($path === '/' && $file === 'index.php')){ //if path == "file.php" OR path == "/file.php" OR (path == / and file is "index.php")
					return array($file,array());
				}
			}
		}
	}
	return false;
}

function getCurrentUrl(){
	return parsePath();
}

/*
 * Util function. Returns true if an $object has the same class (from get_class) as specified by $type.
 * However, this removes namespaces and their slashes ('\') before comparing. 
 */
function typeIs($object, $type){
	$type = get_class($object);
	$namespaces = explode('\\',$type); //Get rid of namespaces and their slashes. 
	$objectClass = end($namespaces); //get the last item (the class name)
	
	return ($type === $objectClass);
}

/** Imports and includes **/
init(); //Import stuff
	
?>
