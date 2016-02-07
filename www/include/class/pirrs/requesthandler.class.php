<?php
namespace pirrs;
class RequestHandler{
	/*
	 * Gets the include path for the script that handles the given request
	 * Returns false if there is no script for the input request
	 * Returns array of the path to the handling script, and the path to the template for the handling script
	 * The second element of the array will be false if the template file does not exist
	 */
	private static function getRequestScript($requested){
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
	public static function execute(Request $request){
		Response::preExecute();
		$response = self::executePrivate($request);
		Response::postExecute($response);
		Log::dump($response);
		return $response;
	}

	private static function executePrivate(Request $request){
		//Find either a template, a PageObject script, or both, to handle this request
		list($requestedScript,$requestedTemplate) = self::getRequestScript($request->GetPath());

		//If there does not exist either a script or a template, then we have no handlers for this request
		if($requestedScript === false && $requestedTemplate === false){
			//Therefore, we return 404.
			return DefaultAPIResponses::NotFound();
		}

		if($requestedScript !== false){
			self::interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.

			require_once $requestedScript; //Bring in the script that will perform the server side operations for the requested page

			$pageFunctionObject = null; //declare this here for scope

			$requestClass = self::getPageFunctionClass(__NAMESPACE__.'\\'.API_REQUEST_CLASS_PARENT);
			if($requestClass === false){ //Test for API
				$requestClass = self::getPageFunctionClass(__NAMESPACE__.'\\'.PAGE_REQUEST_CLASS_PARENT);
				if($requestClass === false){ //Test for Page
					self::executeWithoutPage($requestedTemplate); //Instead of dying, let's just display the template.
					return DefaultResponses::Success();
				}

				//Okay, time to display a page
				//pageFunctionObject will be of type PageObject
				$pageFunctionObject = new $requestClass(); //Instantiate our page handling object
				$pageFunctionObject->request = $request;

				if(call_user_func(array($pageFunctionObject,REQUEST_FUNC_REQUIRE_LOGGED_IN)) === true && !$pageFunctionObject->request->user->isLoggedIn()){ //If the user must be logged in to view this page, and the user is not logged in
					return DefaultResponses::Unauthorized(); //not authorized
				}
				else{
					$preexResult = call_user_func(array($pageFunctionObject,REQUEST_FUNC_PRE_EXECUTE)); //Call the page specific pre-execution function.
					if($preexResult !== false){ //If preExecute() returns false, cancel loading of template\

            //Before we execute the template, we'll call the function cooresponding to the request method
            switch($pageFunctionObject->request->getMethod()){
                default:
                case(RequestMethod::GET):
                    call_user_func(array($pageFunctionObject,'executeGet'));
                    break;
                case RequestMethod::POST:
                    call_user_func(array($pageFunctionObject,'executePost'));
                    break;
                case RequestMethod::PUT:
                    call_user_func(array($pageFunctionObject,'executePut'));
                    break;
                case RequestMethod::DELETE:
                    call_user_func(array($pageFunctionObject,'executeDelete'));
                    break;
            }

            //Okay, time to execute the template code
						if($requestedTemplate !== false){ //If we have a template file, import that.
							$pageFunctionObject->executeTemplate($requestedTemplate);
						}

					}

                      //Page specific post-execution function to wrap everything up.
					call_user_func(array($pageFunctionObject,REQUEST_FUNC_POST_EXECUTE));
				}
			}
			else{
				//respond to an API request
				//pageFunctionObject will be of type APIObject
				$pageFunctionObject = new $requestClass(); //Instantiate our page handling object
				$pageFunctionObject->request = $request;

				self::interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.
				$preexResult = call_user_func(array($pageFunctionObject,REQUEST_FUNC_PRE_EXECUTE)); //Call the page specific pre-execution function.

        if(call_user_func(array($pageFunctionObject,REQUEST_FUNC_REQUIRE_LOGGED_IN)) === true && !$pageFunctionObject->request->user->isLoggedIn()){ //If the user must be logged in to view this page, and the user is not logged in
            return DefaultResponses::Login(); //not authorized
        }
        else{
            if($preexResult !== false){ //If preExecute() returns false, cancel loading of template
                switch($pageFunctionObject->request->getMethod()){
                    default:
                    case(RequestMethod::GET):
                        call_user_func(array($pageFunctionObject,'executeGet'));
                        break;
                    case RequestMethod::POST:
                        call_user_func(array($pageFunctionObject,'executePost'));
                        break;
                    case RequestMethod::PUT:
                        call_user_func(array($pageFunctionObject,'executePut'));
                        break;
                    case RequestMethod::DELETE:
                        call_user_func(array($pageFunctionObject,'executeDelete'));
                        break;
                }
            }
            call_user_func(array($pageFunctionObject,REQUEST_FUNC_POST_EXECUTE)); //Page specific post-execution function.
        }
			}
			self::internalPostExecute($pageFunctionObject->response); //Call the global RequestHandler postExecute function. Perform any tasks we want to always occur after processing, but before sending output.
			return $pageFunctionObject->response;
		}
		else{ //Since we know we have either the script or the template, then, here, we must have only the template.
			self::executeWithoutPage($requestedTemplate);
			return DefaultResponses::Success();
		}
	}

	/*
	 * This will display the template file corresponding to the request, without a corresponding Page object.
	 */
	private static function executeWithoutPage($requestedTemplate){
		self::interalPreExecute(); //Call the global RequestHandler pre-execution function. Take care of anything that should happen before the page begins loading.
		$templateReferenceVar = new NoPage(); //This is essentially to make error reporting obvious. If the template without an associated php file tries to call functions as though there is a php file for it, this will output some nice helpful errors.
		$templateReferenceVar->executeTemplate($requestedTemplate);
	}

	/*
	 * Finds the name of a class that is a descendent of $parentClass.
	 * This searches the list of currently declared classes, and finds any that are
	 * chidren of $parentClass. Currently, if more than one is found, it will return
	 * the first one found.
	 * Returns the name of the class, or false is none are found.
	 */
	private static function getPageFunctionClass($parentClass){
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
		return $children[count($children) - 1]->name;
	}

	/*
	 * If there is anything that must happen before any page starts loading, you can do it here.
	 */
	private static function interalPreExecute(){
		OutputHandler::preExecute();
	}

	/*
	 * Called after every request is finished processing, just before sending output.
	 * @param $response The response that is to be sent.
	 */
	private static function internalPostExecute(Response $response){
		if($response !== null){
			if(isset($_SERVER['SERVER_NAME'])){
				$response->headers->set('X-XRDS-Location',sprintf('http://%s/xrds.xml',$_SERVER['SERVER_NAME']));
			}
		}
	}

	private static function includePageFile($file){
		include $file; //Execution of the template begins.
	}
}
 ?>
