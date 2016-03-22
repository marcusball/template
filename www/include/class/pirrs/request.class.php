<?php
namespace pirrs;
class Request{
	public $args;
	protected $method;
	protected $req;

	public $user;

	/**
	 * The stripped path to the resource being requested.
	 * @example "index" or "path/to/file"
	 */
	protected $path;

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

	/**
	 * Take a given @path and strip Config::core('request_php_extension'), preceding slash,
	 *   and anything other than path to file.
	 * @example "/path/to/file.php?example=true" => "path/to/file"
	 * @param $path The path to clean
	 * @param $index The default "home" return value, which will be used
	 *   for empty @path values, or results that would equal '/'.
	 */
	public static function cleanPath($path, $index = 'index'){
		if($path !== '/' && $path != ''){

			//Convert the extension into something regex-safe (Escape the periods).
		  $phpExtRegex = str_replace('.','\.',Config::core('request_php_extension')); //Convert something like '.php' to '\.php'

			//Try to match against /some/path/to/file.php?querystuff=whatever,
		    //  with the desired match results containing the named group "path"
		    //  "path" should contain something like "/some/path/to/file",
		    //  if there was a proper match with our configured php extension (".php" in this case)
			$matchVal = preg_match('#^/?(?:(?\'path\'[^\?]+)'.$phpExtRegex.')?(?:\?.*)?$#i',$path,$matches);
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
				$foundPath = $matches['path'];
				//If '.php' is still present, then Config::core('request_php_extension') is probably != '.php'
				//  however, index.php still needs to be handled correctly for rewrites,
				//  If this condition is false (that is, if the path == 'index.php'),
				//  then we will fall through to return the default 'index' value.
				if($foundPath !== 'index.php'){
					//If request is a directory, search for the index
		      if(substr($foundPath,-1) !== '/'){
						return $foundPath;
		      }
					else{
						//If the value of the path found is a directory (Eg: "path/to/directory/"),
						//  then we're looking for the index of said directory.
						return $foundPath . "index";
					}
				}
			}
		}
		return $index;
	}

	public static function getRewritePath($path, $rewriteRules){
		foreach($rewriteRules as $file=>$rules){
			//For simplicity, if $rules is not an array, we're going to make it into one
			if(!is_array($rules)){
				$rules = array($rules);
			}

			foreach($rules as $rule){
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
		}
		return false;
	}

	/**
	 * Get the actual URL path that was requested for this page.
	 * @param $withQueryArgs When FALSe, GET data (anything including and after the '?' symbol) will be removed.
	 * @return a string, similar to "/install/path/to/file.php" or "/some/request.php?query=data"
	 */
	public static function getCurrentUrl($withQueryArgs = true){
		return self::parsePath($_SERVER['REQUEST_URI'], '/', $withQueryArgs);
	}

	/**
	 * Construct a new request object for the given @requestUri.
	 * @param $requestUri Should be the value of $_SERVER['REQUEST_URI'],
	 *   expected input in the format "/request/path/file.php?any=url-data".
	 * @param $webrootDirectoy Path to the directory representing the
	 *   root directory of _this_ application.
	 *   Should be the result of `dirname($_SERVER['SCRIPT_NAME'])` when
	 *   called from a script in the www directory.
	 * @param $bEnableRewrite When TRUE, will test for and attempt to handle possible rewrite paths
	 * @param $bRewriteOnly When TRUE and @bEnableRewrite is TRUE, requests will only succeed
	 *   if they are rewritten URLs. All others will be rejected.
	 * @return a new Request object, or FALSE on "Not Found".
	 */
	public static function createRequest($requestUri, $webrootDirectoy = '/', $bEnableRewrite = true, $bRewriteOnly = false){
		//Just remember that, internally "bla.com/" will still be considered "bla.com/index.php" when checking the rewrite condition.
		$path = self::parsePath($requestUri, $webrootDirectoy, true);
		$requestArgs = array();

		if($bEnableRewrite){
			$rewriteRules = Config::rewriterules();
			if(($rewrite = self::getRewritePath($path, $rewriteRules)) !== false){ //If this requested URL is being handled as a rewrite page
				list($file,$groups) = $rewrite; //Get the file system file name , and the regex groups from the regex that matched this request.

				$path = $file; //Update the request path with the file system file (as defined in $REWRITE_RULES in config.php).
				$requestArgs = array_merge($requestArgs,$groups);
			}

			if($rewrite === false && $bRewriteOnly){
				//OutputHandler::handleAPIOutput(DefaultAPIResponses::NotFound());
				return false;
			}
		}

		$request = self::cleanPath($path);
		if($request === false){
			//OutputHandler::handleAPIOutput(DefaultAPIResponses::NotFound());
			return false;
		}
		else{
				$requestObject = new self();
				$requestObject->path = $request;
				$requestObject->args = $requestArgs;
				return $requestObject;
		}
	}

	private function readMethod(){
		if(isset($_SERVER['REQUEST_METHOD'])){
			return self::getMethodFromString($_SERVER['REQUEST_METHOD']);
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

	/**
	 * @return A tyto/RequestMethod valud from a string like 'GET' or 'POST',
	 *   or FALSE on invalid input.
	 * @note Currently, tyto/RequestMethod values are integers, so be careful
	 *   of distinguishing between FALSE and 0.
	 */
	private static function getMethodFromString(string $method){
		$method = trim(strtoupper($method));
		if(RequestMethod::isValidName($method)){
			return constant(RequestMethod::class . '::' . $method);
		}
		return false;
	}

	public function setMethod($method){
		if(is_string($method) === true){
			$method = self::getMethodFromString($method);
			if($method !== false){
				$this->method = $method;
				return;
			}
		}
		else{
			if(RequestMethod::isValidValue($method)){
				$this->method = $method;
				return;
			}
		}

		//If we've reached here, no valid method was given
		throw new \InvalidArgumentException('Invalid method provided!');
	}

	public function isGet(){
		return $this->getMethod() == RequestMethod::GET;
	}
	public function isPost(){
		return $this->getMethod() == RequestMethod::POST;
	}

	/**
	 * Append a key-value array to the $this->req request data.
	 */
	public function appendReqData(array $data){
		$this->req = array_merge($this->req, $data);
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
			$isPresent = isset($this->req[$testArg]) && !is_array($this->req[$testArg]);
			$isset = $isset && $isPresent;
			if(!$isPresent){
				$missing[] = $testArg; //Append $testArg to the list of missing arguments
			}
		}
		if($isset !== true){
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
			$isPresent = isset($this->req[$testArg]) && is_array($this->req[$testArg]);
			$isset = $isset && $isPresent;
			if(!$isPresent){
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

	/**
	 * @return The path of the resource being requested. Eg: "path/to/file"
	 * @see getCurrentUrl() if you would like the actual request url.
	 */
	public function getPath() {
		return $this->path;
	}
}
?>
