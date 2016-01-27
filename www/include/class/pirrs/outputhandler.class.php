<?php
namespace pirrs;
class OutputHandler{
	private static $statusCodes = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		204 => 'No Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		500 => 'Internal Server Error'
	);

	public static function preExecute(){
		//Anything that must happen before the request is executed
	}


	public static function sendResponse(Response $output){
		switch($output->responseType){
			case(ResponseType::RAW):
				self::handleRawOutput($output);
				break;
			case(ResponseType::API):
				self::handleAPIOutput($output);
				break;
			case(ResponseType::PAGE):
			default:
				self::handlePageOutput($output);
				break;
		}
	}

	private static function handlePageOutput(Response $output){
		//If we're NOT returning raw content
		//(raw content would be just returning anything that was echoed
		//  during execution. No special handling will be used.)
		if(!$output->rawContent){
			// First we'll output the response code
			$statusCode = $output->getStatusCode();
			$statusMessage = '';

			//If we have a predefined message corresponding to the response status code
			if(isset(self::$statusCodes[$statusCode])){
				$statusMessage = self::$statusCodes[$statusCode];
			}
			else{
				//Otherwise, we'll use whatever the response object gave as a message
				$statusMessage = $output->getStatusCodeMessage();

			}

			//Send the HTTP header
			header(sprintf('HTTP/1.1 %.03d %s',$statusCode,$statusMessage));


			//Set the content type header, if not already set.
			if(!$output->headers->issetHeader('Content-Type')){
				$output->headers->set('Content-Type','text/html');
			}

			//Send the remaining headers
			$headers = $output->headers->getAll();
			foreach($headers as $header => $headerValue){
				header(sprintf('%s: %s',$header,$headerValue));
			}


			switch($output->getStatusCode()){
				case 200:
					static::handleSuccess($output);
					break;
				case 210: //I made this case up; "Use alternate data"
					static::handleUseAlternateData($output->getContent());
					break;
				case 302:
					static::handleTemporaryRedirect($output->getContent());
					break;
				case 400:
					static::handleBadRequest();
					break;
				case 401:
					static::handleNotAuthorized();
					break;
				case 403:
					static::handleForbidden();
					break;
				case 404:
					static::handleNotFound();
					break;
				case 405:
					static::handleMethodNotAllowed();
					break;
				case 500:
					static::handleServerError();
					break;
			}

			$endTime = microtime(true);
			global $startTime;
			debug(sprintf('<br />Execution time: %5f seconds',($endTime - $startTime)));
		}
		else{
			echo $output->getOutputBuffer();
		}
	}
	private static function handleAPIOutput(Response $output){
		//ob_end_clean(); //Throw away any written text
		// $text = ob_get_clean();
		// echo $text;

		/* First we'll output the response code */
		$statusCode = $output->getStatusCode();
		$statusMessage = '';
		if(isset(self::$statusCodes[$statusCode])){
			$statusMessage = self::$statusCodes[$statusCode];
		}
		else{
			$statusMessage = $output->getStatusCodeMessage();

		}
		header(sprintf('HTTP/1.1 %.03d %s',$statusCode,$statusMessage));


		/* Set the content type header, if not already set. */
		if(!$output->headers->issetHeader('Content-Type')){
			$output->headers->set('Content-Type','application/json');
		}

		/* Send the remaining headers */
		$headers = $output->headers->getAll();
		foreach($headers as $header => $headerValue){
			header(sprintf('%s: %s',$header,$headerValue));
		}

		if(!$output->rawContent){
			$outputData['status_code'] = $statusCode;
			$outputData['status_message'] = $statusMessage;
			if($output->hasContent()){
				//$outputData['content'] = $output->getContent();
				$apiContent = $output->getContent();
				if(is_array($apiContent)){
					$outputData = array_merge($outputData,$apiContent);
				}
				else{
					$outputData['content'] = $apiContent;
				}
			}
			if($output->hasErrors()){
				$outputData['error'] = $output->getErrors();
			}
			$outputData['request_id'] = UUID::v4();

			$endTime = microtime(true);
			global $startTime;
			$outputData['response_time'] = sprintf('%5f seconds',($endTime - $startTime));

			echo json_encode($outputData,JSON_PRETTY_PRINT);
		}
		else{
			echo $output->getContent();
		}
	}

	private static function handleRawOutput(Response $output){
		echo $output->getOutputBuffer();

		if($output->hasContent()){
			echo $output->getContent();
		}
	}

	private static function handleBadRequest(){
		ob_end_clean();
		echo 'Stop trying to break things';
	}
	private static function handleForbidden(){
		ob_end_clean();
		echo "None shall pass";
	}
	/*
	* In this case, we just ignore any of the page output, and instead only output the
	* data returned by a page via Page->setResult(210,$alternateData).
	*/
	private static function handleUseAlternateData($data){
		ob_end_clean();
		echo $data;
	}

	private static function handleNotFound(){
		//ob_end_clean();
		static::handleSuccess();
		echo "not found";
	}

	private static function handleSuccess(Response $output){
		echo $output->getOutputBuffer();
	}

	private static function handleNotAuthorized(){
		ob_end_clean();
		echo 'You\'re not authorized to view this page!';
	}

	private static function handleTemporaryRedirect($url){
		ob_end_clean();
		header('Location: '.str_replace(array('\r','\n'),'',$url));
	}

	private static function handleMethodNotAllowed(){
		ob_end_clean();
		echo '<h1>405 Method Not Allowed</h1>';
	}
	private static function handleServerError(){
		ob_end_clean();
		echo '<h1>500 Internal Server Error</h1>';
	}
}
?>
