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
	$request = Request::createRequest($_SERVER['REQUEST_URI'], dirname($_SERVER['SCRIPT_NAME']), REWRITE_ENABLE, REWRITE_ONLY);
	if($request !== false){
			$Handler = new RequestHandler();
			$Handler->setRequestArgs($request->args); //Args are any values pulled from named regex groups in configured rewrite rules.
			$Handler->executeRequest($request->getPath());
		}
		else{
			OutputHandler::handleAPIOutput(DefaultAPIResponses::NotFound());
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
