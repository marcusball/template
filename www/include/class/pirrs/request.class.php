<?php
namespace pirrs;
class Request{
	private $args;
	private $method;
	private $req;

	public $user;

	private static $methodMap = array(
		'GET' => RequestMethod::GET,
		'POST' => RequestMethod::POST,
		'PUT' => RequestMethod::PUT,
		'PATCH' => RequestMethod::PUT,
		'DELETE' => RequestMethod::DELETE
	);

	public function __construct(){
		$this->args = array();
		$this->method = $this->readMethod();
		$this->user = ResourceManager::getCurrentUser();

		$this->req = array_merge($_GET,$_POST); //did this instead of $_REQUEST because I don't want $_COOKIES included
	}

	/**
	 * Parse the request URI, to get the request relative to web root.
	 * @param $requestUri Should be the value of $_SERVER['REQUEST_URI']
	 * @param $webrootDirectoy Path to the directory representing the
	 *   root directory of _this_ application.
	 *   Should be `dirname($_SERVER['SCRIPT_NAME'])`
	 * @param $withQueryArgs When true, will include GET params in output,
	 *   otherwise anything including and after the '?' will be trimmed off.
	 * @example If @withQueryArgs is false, "/path/to/file.php?myquery"
	 *   will be "/path/to/file.php". If this system is installed within
	 *   a subdirectory such that the request is "/website/path/to/file.php",
	 *   then the output will still be "/path/to/file.php"
	 */
	public static function parsePath($requestUri, $webrootDirectory, $withQueryArgs = true){
		//http://stackoverflow.com/questions/16388959/url-rewriting-with-php
		$uri = rtrim( $webrootDirectory, '/' );
    $uri = str_replace('\\','/',$uri); //Replace the windows directory character ('\') with a '/'

    //Make sure $uri is not empty, to avoid "Empty needle" warning
    $dirPos = strpos($requestUri,'/'); //If $uri is empty, then we're at the root; assume position of first '/' slash.
    if(isset($uri) && trim($uri) !== ''){
        $dirPos = strpos($requestUri, $uri); //Get the position of the current file directory name in the url
    }

    //trim out the current file system directory, so we have a completely relative path to the requested file
    if($dirPos !== false){
        $uri = '/' . trim( substr_replace($requestUri, '', $dirPos, strlen($uri)), '/' ); //Remove only the first occurrence of the current directory
        //http://stackoverflow.com/a/1252710/451726
    }

		$uri = urldecode( $uri );

		if(!$withQueryArgs){
			$matchVal = preg_match('#^(?\'path\'[^\?]*)(?:\?.*)?$#i',$uri,$matches);
			if($matchVal !== 0 && $matchVal !== false){
				return $matches['path'];
			}
		}
		return $uri;
	}

	public function cleanPath($path){
		if($path == '/') return $path;
	    $phpExt = str_replace('.','\.',REQUEST_PHP_EXTENSION); //Convert something like '.php' to '\.php'

		//Try to match against /some/path/to/file.php?querystuff=whatever,
	    //  with the desired match results containing the named group "path"
	    //  "path" should contain something like "/some/path/to/file",
	    //  if there was a proper match with our configured php extension (".php" in this case)
		$matchVal = preg_match('#^/?(?:(?\'path\'[^\?]+)'.$phpExt.')?(?:\?.*)?$#i',$path,$matches);
		if($matchVal === 0 || $matchVal === false){
	        //If no valid file path was found, try for a directory path
	        $dirMatchVal = preg_match('#^/?(?:(?\'path\'[^\?]+))?(?:\?.*)?$#i',$path,$matches);
	        if($dirMatchVal === 0 || $dirMatchVal === false){
	            return false;
	        }
	        //If a path was found
	        if(isset($matches['path'])){
	            //Append a slash to indicate it's a directory
	            if(substr($matches['path'],-1) !== '/'){
	                $matches['path'] = $matches['path'] . '/';
	            }
	        }
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

	public function getRewritePath($path){
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

	public function getCurrentUrl($withQueryArgs = true){
		return $this->parsePath($withQueryArgs);
	}



	private function readMethod(){
		$method = trim(strtoupper($_SERVER['REQUEST_METHOD']));

		if(isset(self::$methodMap[$method])){
			return self::$methodMap[$method];
		}
		return RequestMethod::GET;
	}

	/*
	 * Returns an APIRequestMethod enum value.
	 */
	public function getMethod(){
		if($this->method == null){
			$this->method = $this->readMethod();
		}
		return $this->method;
	}

	public function isGet(){
		return $this->getMethod() == RequestMethod::GET;
	}
	public function isPost(){
		return $this->getMethod() == RequestMethod::POST;
	}

	public function setArgs(array $args){
		$this->args = array_merge($this->args,$args);
	}

	public function issetArg($arg){
		return isset($this->args[$arg]);
	}

	/*
	 * Gets the request arg specified by $arg.
	 * If the value of that arg is an array, null is returned; Use getArrayArg().
	 */
	public function getArg($arg){
		if(isset($this->args[$arg])){
			if(is_array($this->args[$arg])){ //If an arg is somehow an array, you should use a getArrayArg function.
				return null;
			}
			return $this->args[$arg];
		}
		return null;
	}

	/*
	 * Gets the request arg specified by $arg.
	 * If the value of that arg is NOT an array, null is returned; Use getArg().
	 */
	public function getArgArray($arg){
		if(isset($this->args[$arg])){
			if(!is_array($this->args[$arg])){
				return null;
			}
			return $this->args[$arg];
		}
		return null;
	}

	/*
	 * Checks if datum identified by $req is present in $_REQUEST.
	 * returns true iff isset is true for $_REQUEST[$req] and if $_REQUEST[$req] is NOT an array.
	 * return false, otherwise.
	 */
	public function issetReq($req){
		return isset($this->req[$req]) && !is_array($this->req[$req]);
	}

	/*
	 * Checks if datum identified by $req is present in $_REQUEST.
	 * returns true iff isset is true for $_REQUEST[$req] and if $_REQUEST[$req] IS an array.
	 * return false, otherwise.
	 */
	public function issetReqArray($req){
		return isset($this->req[$req]) && is_array($this->req[$req]);
	}

	/*
	 * Checks if the data specified by the params list is present in $_REQUEST.
	 * returns true iff isset is true for $_REQUEST[$param[$i]] and NONE are arrays.
	 * returns list of missing req values if false.
	 */
	public function issetReqList(){
		$toTest = func_get_args();
		$missing = array();
		$isset = true;
		foreach($toTest as $testArg){
			$isset = $isset & ($isMissing = isset($this->req[$testArg]) && !is_array($this->req[$testArg]));
			if($isMissing){
				$missing[] = $testArg; //Append $testArg to the list of missing arguments
			}
		}
		if(!$isset){
			return $missing;
		}
		return true;
	}

	/*
	 * Checks if the data specified by the params list is present in $_REQUEST.
	 * returns true iff isset is true for $_REQUEST[$param[$i]] and ALL are arrays.
	 * returns list of missing req values if false.
	 */
	public function issetReqArrayList(){
		$toTest = func_get_args();
		$missing = array();
		$isset = true;
		foreach($toTest as $testArg){
			$isset = $isset & ($isMissing = isset($this->req[$testArg]) && is_array($this->req[$testArg]));
			if($isMissing){
				$missing[] = $testArg; //Append $testArg to the list of missing arguments
			}
		}
		if(!$isset){
			return $missing;
		}
		return true;
	}

	/*
	 * Gets the datum in $_REQUEST as identified by $req.
	 * Returns the data iff it exists (isset is true), and it is NOT an array.
	 * Returns null otherwise.
	 */
	public function getReq($req){
		if(isset($this->req[$req])){
			if(is_array($this->req[$req])){
				return null;
			}
			return $this->req[$req];
		}
		return null;
	}

	/*
	 * Gets the datum in $_REQUEST as identified by $req.
	 * Returns the data iff it exists (isset is true), and it IS an array.
	 * Returns null otherwise.
	 */
	public function getReqArray($req){
		if(isset($this->req[$req])){
			if(!is_array($this->req[$req])){
				return null;
			}
			return $this->req[$req];
		}
		return null;
	}

    /*
     * When given a number of string req keys as parameters, this will
     *   return an array populated with the corresponding req values.
     * If a there does not exist a value corresponding to an input key,
     *   null will be returned in its place.
     * Note: If the value corresponding to a given is an array,
     *   it will NOT be returned, null will be given. This function will not
     *   return any arrays as values. If you are expecting an array,
     *   use the getReqArrayList(...) function.
     * The intent of this is to enable code such as this:
     *   list($value1,$value2,..) = $request->getReqList('key1','key2',..);
     */
    public function getReqList(){
		$toGet = func_get_args();
        $toReturn = array();
		foreach($toGet as $testArg){
			$isset = isset($this->req[$testArg]) && !is_array($this->req[$testArg]);
			if($isset){
                $toReturn[] = $this->req[$testArg];
            }
            else{
                $toReturn[] = null;
            }
		}
		return $toReturn;
	}

    /*
     * When given a number of string req keys as parameters, this will
     *   return an array populated with the corresponding req values.
     * If a there does not exist a value corresponding to an input key,
     *   null will be returned in its place.
     * Note: If the value corresponding to a given is NOT an array,
     *   it will NOT be returned, null will be given. This function will ONLY
     *   return a value if it is an array. If you are expecting a non-array,
     *   use the getReqList(...) function.
     * The intent of this is to enable code such as this:
     *   list($valueArray1,$valueArray2,..) = $request->getReqList('key1','key2',..);
     */
    public function getReqArrayList(){
		$toGet = func_get_args();
        $toReturn = array();
		foreach($toGet as $testArg){
			$isset = isset($this->req[$testArg]) && is_array($this->req[$testArg]);
			if($isset){
                $toReturn[] = $this->req[$testArg];
            }
            else{
                $toReturn[] = null;
            }
		}
		return $toReturn;
	}

	/*
	 * Assuming a form is submitted, with a form key named according to FORM_KEY_DEFAULT_INPUT_NAME,
	 * This will verify the $_POSTed form key is valid.
	 */
	public function isValidFormSubmission(){
		$manager = ResourceManager::getFormKeyManager();
		return $manager->isValidFormRequest();
	}

    /*
     * Get the current url, encoded in a safely-printable fashion.
     * $append: extra data to append to the url (optional).
     */
    public function getSafeUrl($append = null){
		$url = getCurrentUrl();
		if($append !== null){
			$url .= $append;
		}
		return htmlentities(urlencode($url));
	}
}
?>
