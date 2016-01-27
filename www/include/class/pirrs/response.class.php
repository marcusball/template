<?php
namespace pirrs;
class Response{
	public $headers;
	protected $content = null;
	protected $errors;
	protected $statusCode = 200;
	protected $statusCodeMes = "It Is A Mystery"; //If OutputHandler does not have a message associated with the returned $statusCode, it will read this.

	public $rawContent = false; //Set to true to disable json_encode-ing of $content
	public $responseType;

	/**
	 * The contents of ob_end_clean() after a response has been executed.
	 */
	protected $outputBuffer;

	public function __construct(){
		$this->headers = new ResponseHeaders();
	}

	/**
	 * Perform any actions that should be done before a request's page is executed.
	 */
	public static function preExecute(){
		ob_start();
	}

	/**
	 * Perform any actions that should be done after a request's page is executed.
	 * @param $response The Response object on which these post-run processes should apply.
	 */
	public static function postExecute(self $response){
		$response->outputBuffer = ob_get_clean();
	}

	/*
	 * This is basically a copy constructor.
	 * Copies the data from $apiResponse into this.
	 * Note: will overwrite headers, but will not delete
	 *   existing headers that don't exist in the given $apiResponse.
     * This also does NOT copy the $responseType value.
	 */
	public function apply(Response $apiResponse,$forceOverwrite = false){
		$this->headers->apply($apiResponse->headers);
		$this->setStatusCode($apiResponse->getStatusCode(), $this->statusCodeMes);

		if($forceOverwrite || $apiResponse->hasContent()){
			$this->setContent($apiResponse->getContent());
		}
		if($forceOverwrite || $apiResponse->hasErrors()){
			$this->addErrors($apiResponse->getErrors());
		}
	}

	public function setStatusCode($code, $mes = null){
		if($code >= 100 && $code < 600){
			$this->statusCode = $code;
			if($mes != null){
				$this->statusCodeMes = $mes;
			}
		}
	}

	public function getStatusCode(){
		return $this->statusCode;
	}

	public function getStatusCodeMessage(){
		return $this->statusCodeMes;
	}

	public function setContent($content){
		$this->content = $content;
	}

	public function getContent(){
		return $this->content;
	}

	/**
	 * Output buffer of anything that was printed during response execution.
	 */
	public function getOutputBuffer(){
		return $this->outputBuffer;
	}

	public function hasContent(){
		return isset($this->content) && $this->content != null;
	}

	/*
	 * Add a single error, $message.
	 */
	public function addError($message){
		if(!isset($this->errors)){
			$this->errors = array();
		}
		if(is_array($message)){
			return false;
		}

		$this->errors[] = $message;
		return true;
	}

	/*
	 * Add an array of errors, given by $messages
	 */
	public function addErrors($messages){
		if(!is_array($messages)){
			return $this->addErrors(array($messages));
		}
		foreach($messages as $message){
			$this->addError($message);
		}
		return true;
	}

	public function getErrors(){
		return $this->errors;
	}

	public function hasErrors(){
		return isset($this->errors) && count($this->errors) > 0;
	}

    /*
     * Forward to a different page, using a 302 redirect.
     * $url is the url to which to forward.
     * $data (optional, default null) should be an array
     *   containing url query parameters in key=>value format.
     *   May also be passed simply as the string to append to $url.
     */
    public function forwardTo($url, $data = null){
        if($data != null){
            $hasPreexistingQuery = (strpos($url,'?') !== false); //If the $url already has a query string (indicated by the existence of a '?' character).
            if(is_string($data)){ //If the data variable is a string
                if($hasPreexistingQuery){
                    if(substr($data,0,1) === '?'){ //If the string does start with a query question mark
                        $url = $url . '&' . substr($data,1); //Remote the question mark and append
                    }
                    elseif(substr($data,0,1) === '&'){ //If the string does not start with a query question mark
                        $url = $url . $data;
                    }
                    else{
                        $url = $url . '&' . $data;
                    }
                }
                else{
                    if(substr($data,0,1) === '?'){ //If the string does start with a query question mark
                        $url = $url . $data;
                    }
                    elseif(substr($data,0,1) === '&'){ //If the string does not start with a query question mark
                        $url = $url . '?' . substr($data,1);
                    }
                    else{
                        $url = $url . '?' . $data;
                    }
                }
            }
            elseif(is_array($data)){
                $queryArray = array();
                foreach($data as $key=>$value){
                    $queryArray[] = $key . '=' . $value;
                }
                $queryString = implode('&',$queryArray);
                $url = $url . ((!$hasPreexistingQuery)?'?':'&') . $queryString;
            }
        }
        $this->responseType = ResponseType::PAGE;
        $this->setStatusCode(302);
        $this->setContent($url);
    }
}
?>
