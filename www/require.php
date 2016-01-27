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
	$GLOBALS['spl_autoload_dir'] = $classPath;

	spl_autoload_register(
		function ($class) {
	    $class = str_replace('\\','/',strtolower($class));

	    $autoload_extensions = explode(',',spl_autoload_extensions());
	    $include_path = str_replace('\\','/',$GLOBALS['spl_autoload_dir'])."/";
	    foreach($autoload_extensions as $x) {
	        $fname = $include_path.$class.$x;

	        if(@file_exists($fname)) {
	            require_once($fname);
	            return true;
	        }
	    }
	    return false;
		}
	);

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

function getCurrentUrl($withQueryArgs = true){
	return parsePath($withQueryArgs);
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
