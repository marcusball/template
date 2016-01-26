<?php
namespace pirrs;
/** This is the page that will handle all incoming api requests **/
$startTime = microtime(true);
require 'require.php';

/*
 * For all intents and purposes, this is the public static void main() function.
 * Everything relating to actual functionality begins here, with the exception of
 *   the error handling and call of this function, all of which takes place
 *   just following this function's body.
 */
function runPageLogicProcedure(){
	//Just remember that, internally "bla.com/" will still be considered "bla.com/index.php" when checking the rewrite condition.
	$path = parsePath(false);
	$requestArgs = array();

	if(REWRITE_ENABLE){
		if(($rewrite = getRewritePath($path)) !== false){ //If this requested URL is being handled as a rewrite page
			list($file,$groups) = $rewrite; //Get the file system file name , and the regex groups from the regex that matched this request.

			$path = $file; //Update the request path with the file system file (as defined in $REWRITE_RULES in config.php).
			$requestArgs = array_merge($requestArgs,$groups);
		}

		if($rewrite === false && REWRITE_ONLY){
			OutputHandler::handleAPIOutput(DefaultAPIResponses::NotFound());
			return;
		}
	}
	else{
		$request = cleanPath($path);
		if($request === false){
			OutputHandler::handleAPIOutput(DefaultAPIResponses::NotFound());
		}
		else{
			if($request == '/' || $request == '' ||
				(REQUEST_PHP_EXTENSION !== '.php' && $request === 'index.php') // The fun case of a non-'.php' extension,
																			   // But with support for apache rewrite 'some.website/' compatibility.
			){ $request = cleanPath('index'.REQUEST_PHP_EXTENSION); }

            //If request is a directory, search for the index
            //@TODO: searching for index does not work if REQUEST_PHP_EXTENSION is ''
            if(substr($request,-1) === '/'){
                $request = cleanPath($request.'index'.REQUEST_PHP_EXTENSION);
            }

            Log::debug($request);

			$Handler = new RequestHandler();
			$Handler->setRequestArgs($requestArgs); //Args are any values pulled from named regex groups in configured rewrite rules.
			$Handler->executeRequest($request);
		}
	}
}

/*
 * Error handling and execution procedure starting point.
 */
try{
	runPageLogicProcedure(); //GO
}
catch(\Exception $e){ //We fucked up.
	Log::error('An otherwise unhandled exception has occurred.',$e->__toString());

	OutputHandler::handleAPIOutput(DefaultAPIResponses::ServerError());
}

/*$endTime = microtime(true);
debug(sprintf('<br />Execution time: %5f seconds',($endTime - $startTime)));*/
?>
