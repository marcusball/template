<?php
namespace pirrs;
class RequestObject{
  public $request;
	public $response;


	protected $dbCon;

  public function __construct(){
      $this->dbCon = ResourceManager::getDatabaseController();
  }

    /*
	 * This function will be called before the template begins executing.
	 */
	public function preExecute(){}

	/*
	 * This function will be called after the template has completed execution.
	 */
	public function postExecute(){}


	public function executeGet(){ }
	public function executePost(){ return $this->executeGet(); }
	public function executePut(){ $this->response->setStatusCode(405); }
	public function executeDelete(){ $this->response->setStatusCode(405);}

  /**
	 * Overwrite this function in a page class to only allow the page to load if the viewer is logged in
	 */
	public function requireLoggedIn(){
		return false;
	}

    /*
     * Change the type of the response object for this request.
     * Creates a new response object of the class type that cooresponds
     *   to the type defined in the ResponseType enum, then copies over
     *   any date that was already defined in the previous response object.
     * Type should be an enum value from the ResponseType enumeration.
     */
    protected function setResponseType($type){
        //Save the current response object
        $temp = $this->response;

        //Create a new response object of the appropriate type
        switch($type){
            case(ResponseType::HTML):
			case(ResponseType::PAGE):
				$this->response = new PageResponse();
				break;
			case(ResponseType::RAW):
				$this->response = new PageResponse();
				break;
			case(ResponseType::API):
			default:
				$this->response = new APIResponse();
				break;
		}

        //If a response type was actually present already
        if($temp != null){
            //Copy over the old data
            $this->response->apply($temp);
        }
    }
}
?>
