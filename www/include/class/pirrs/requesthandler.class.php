<?php
namespace pirrs;
class RequestHandler{
	private $_request = null;
	private $sqlCon = null;
	private $user = null;
	private $reqArgs = null;

	private $requestType;
	public $pageFunctionObject;

	/** Contructor
	 ** Optional arguments: string Request, PDO SQL connection
	 **/
	public function __construct(){
		for($i=0;$i<func_num_args();$i++){
			$arg = func_get_arg($i);
			/*if(is_string($arg)){
				$this->_request = $arg;
			}*/
			/*elseif(is_object($arg)){
				$class = get_class($arg);
				if($class == 'DBController'){
					$this->dbCon = $arg;
				}
			}*/
		}
		$this->dbCon = ResourceManager::getDatabaseController();
		$this->user = ResourceManager::getCurrentUser();
		$this->pageFunctionObject = null;
	}

	/** Set the request **/
	/*public function setRequest($request){
		$this->_request = $request;
	}*/

	/*
	 * Gets the include path for the script that handles the given request
	 * Returns false if there is no script for the input request
	 * Returns array of the path to the handling script, and the path to the template for the handling script
	 * The second element of the array will be false if the template file does not exist
	 */
	private function getRequestScript($requested){
		if($requested != null){
			$includeFile = INCLUDE_PATH_PHP.$requested.INCLUDE_PHP_EXTENSION;
			$templateFile = INCLUDE_PATH_TEMPLATE.$requested.INCLUDE_TEMPLATE_EXTENSION;

			$hasPhp = file_exists($includeFile);
			$hasTemplate = file_exists($templateFile);

			if($hasPhp || $hasTemplate){
				return array(($hasPhp)?$includeFile:false,($hasTemplate)?$templateFile:false);
			}
		}
		return array(false,false);
	}

  /**
   * Entry point for the RequestHandler. Determines expected file names,
   *   then hands them over to executePage().
	 * @param $request An object of type Request. Must contain the requested path.
	 * @return An object of type Response.
   */
	public function execute(Request $request){
		//Find either a template, a PageObject script, or both, to handle this request
		list($requestedScript,$requestedTemplate) = $this->getRequestScript($request->GetPath());

		//If there does not exist either a script or a template, then we have no handlers for this request
		if($requestedScript === false || $requestedTemplate === false){
			//Therefore, we return 404.
			return DefaultAPIResponses::NotFound();
		}

		if($requestedScript !== false){
			$this->interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.

			require $requestedScript; //Bring in the script that will perform the server side operations for the requested page

			$requestClass = $this->getPageFunctionClass(__NAMESPACE__.'\\'.API_REQUEST_CLASS_PARENT);
			if($requestClass === false){ //Test for API
				$this->requestType = RequestType::PAGE;
				$requestClass = $this->getPageFunctionClass(__NAMESPACE__.'\\'.PAGE_REQUEST_CLASS_PARENT);
				if($requestClass === false){ //Test for Page
					$this->executeWithoutPage($requestedTemplate); //Instead of dying, let's just display the template.
					return;
				}
				else{
					//Okay, time to display a page
					//pageFunctionObject will be of type PageObject
					$this->pageFunctionObject = new $requestClass(); //Instantiate our page handling object
					$this->pageFunctionObject->request->setArgs($this->reqArgs);

					if(call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_REQUIRE_LOGGED_IN)) === true && !$this->user->isLoggedIn()){ //If the user must be logged in to view this page, and the user is not logged in
						$this->handleOutput(DefaultResponses::Unauthorized()); //not authorized
						return;
					}
					else{
						$preexResult = call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_PRE_EXECUTE)); //Call the page specific pre-execution function.
						if($preexResult !== false){ //If preExecute() returns false, cancel loading of template\

              //Before we execute the template, we'll call the function cooresponding to the request method
              switch($this->pageFunctionObject->request->getMethod()){
                  default:
                  case(RequestMethod::GET):
                      call_user_func(array($this->pageFunctionObject,'executeGet'));
                      break;
                  case RequestMethod::POST:
                      call_user_func(array($this->pageFunctionObject,'executePost'));
                      break;
                  case RequestMethod::PUT:
                      call_user_func(array($this->pageFunctionObject,'executePut'));
                      break;
                  case RequestMethod::DELETE:
                      call_user_func(array($this->pageFunctionObject,'executeDelete'));
                      break;
              }

              //Okay, time to execute the template code
							if($requestedTemplate !== false){ //If we have a template file, import that.
								$this->pageFunctionObject->executeTemplate($requestedTemplate,$this);
							}

						}

                        //Page specific post-execution function to wrap everything up.
						call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_POST_EXECUTE));
					}
				}
			}
			else{
				$this->requestType = RequestType::API;
				//respond to an API request
				//pageFunctionObject will be of type APIObject
				$this->pageFunctionObject = new $requestClass(); //Instantiate our page handling object
				$this->pageFunctionObject->request->setArgs($this->reqArgs);

				$this->interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.
				$preexResult = call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_PRE_EXECUTE)); //Call the page specific pre-execution function.

        if(call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_REQUIRE_LOGGED_IN)) === true && !$this->user->isLoggedIn()){ //If the user must be logged in to view this page, and the user is not logged in
            $this->handleOutput(DefaultResponses::Login()); //not authorized
            return;
        }
        else{
            if($preexResult !== false){ //If preExecute() returns false, cancel loading of template
                switch($this->pageFunctionObject->request->getMethod()){
                    default:
                    case(RequestMethod::GET):
                        call_user_func(array($this->pageFunctionObject,'executeGet'));
                        break;
                    case RequestMethod::POST:
                        call_user_func(array($this->pageFunctionObject,'executePost'));
                        break;
                    case RequestMethod::PUT:
                        call_user_func(array($this->pageFunctionObject,'executePut'));
                        break;
                    case RequestMethod::DELETE:
                        call_user_func(array($this->pageFunctionObject,'executeDelete'));
                        break;
                }
            }
            call_user_func(array($this->pageFunctionObject,REQUEST_FUNC_POST_EXECUTE)); //Page specific post-execution function.
        }
			}
			$this->internalPostExecute(); //Call the global RequestHandler postExecute function. Perform any tasks we want to always occur after processing, but before sending output.
			$this->handleOutput($this->pageFunctionObject->response);
		}
		else{ //Since we know we have either the script or the template, then, here, we must have only the template.
			$this->requestType = RequestType::HTML;
			$this->executeWithoutPage($requestedTemplate);
		}
	}

	/*
	 * This will display the template file corresponding to the request, without a corresponding Page object.
	 */
	private function executeWithoutPage($requestedTemplate){
		$this->interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.
		$templateReferenceVar = new NoPage(); //This is essentially to make error reporting obvious. If the template without an associated php file tries to call functions as though there is a php file for it, this will output some nice helpful errors.
		$this->includePageFile($requestedTemplate,$templateReferenceVar);
	}

	/*
	 * Finds the name of a class that is a descendent of $parentClass.
	 * This searches the list of currently declared classes, and finds any that are
	 * chidren of $parentClass. Currently, if more than one is found, it will return
	 * the first one found.
	 * Returns the name of the class, or false is none are found.
	 */
	private function getPageFunctionClass($parentClass){
		$classes = get_declared_classes();
		$children = array();
		$parent = new \ReflectionClass($parentClass); //A class that reports information about a class

		foreach ($classes AS $class){
			$current = new \ReflectionClass($class);
			if ($current->isSubclassOf($parent)){
				$children[] = $current;
			}
		}

		if(count($children) < 1){
			//debug("No class was found that is a subclass of {$parentClass}!");
			//logWarning("No class was found that is a subclass of {$parentClass}!",'index.php',__LINE__);
			return false;
		}
		return $children[0]->name;
	}

	/*
	 * If there is anything that must happen before any page starts loading, you can do it here.
	 */
	private function interalPreExecute(){
		OutputHandler::preExecute();
	}

	/*
	 * Called after every request is finished processing, just before sending output.
	 */
	private function internalPostExecute(){
		$this->pageFunctionObject->response->headers->set('X-XRDS-Location',sprintf('http://%s/xrds.xml',$_SERVER['SERVER_NAME']));
	}

	private function handleOutput(Response $output){
		switch($output->responseType){
			case(ResponseType::RAW):
				OutputHandler::handleRawOutput($output);
				break;
			case(ResponseType::API):
				OutputHandler::handleAPIOutput($output);
				break;
            		case(ResponseType::PAGE):
			default:
				OutputHandler::handlePageOutput($output);
				break;
		}
	}

	private function unexpectedError(){
		$this->handleOutput(DefaultAPIResponses::ServerError());
	}

	private function includePageFile($file, $templateRequestHandler){
		${TEMPLATE_REFERENCE_VARIABLE} = $templateRequestHandler;
		${GLOBAL_REFERENCE_VARIABLE} = $this; //This will give the page a reference to this RequestHandler to access public methods
		${USER_REFERENCE_VARIABLE} = $this->user;
		include $file; //Execution of the template begins.
	}

	/*
	 * Calls the pageTitle() function given in the request's PageObject class. If that function is not defined for the requested page, it will return the default (in this case, the SITE_NAME).
	 */
	public function pageTitle(){
		if($this->pageFunctionObject != null){
			if(method_exists($this->pageFunctionObject,'pageTitle')){
				$this->pageFunctionObject->pageTitle();
				return;
			}
		}
		echo SITE_NAME;
	}

	/*
	 * Includes a page from the /page-include/ directory.
	 */
	public function includeFile($file){
		$path = INCLUDE_PATH_PAGE_INCLUDE . $file;
		if(file_exists($path)){
			$this->includePageFile($path,new NoPage());
			return true;
		}
		return false;
	}

	/*
	 * Set an array containing key value pairs which will be sent to the PageObject
	 * for the requested URI path.
	 */
	public function setRequestArgs($args){
		$this->reqArgs = $args;
	}
}
 ?>
