<?php
namespace pirrs;
class PageObject extends RequestObject{

	/** Constructor
	 ** Currently takes optional PDO connection argument
	 ** There really isn't much of a reason to modify this unless you really need something initialized before performRequest()
	 **/
	public function __construct(){
        parent::__construct();

		for($i=0;$i<func_num_args();$i++){	//Loop through all of the arguments provided to the instruction ("RequestObject($arg1,$arg2,...)").
			$arg = func_get_arg($i);
			if(is_object($arg)){ 			//If this argument is of class-type object (basically anything not a primative data type).
				$class = get_class($arg); 	//Get the actual class of the argument
				if($class == 'PageRequest'){		//Hey look! It's our SQL object
					$this->request = $arg; 	//We should save this.
				}
				elseif($class == 'PageResponse'){
					$this->response = $arg;
				}
			}
		}

		if($this->request == null){
			$this->request = new PageRequest();
		}
		if($this->response == null){
			$this->setResponseType(ResponseType::PAGE);
		}
	}

	/*
	 * This is where template files get included an executed. By placing them in the PageObject class,
	 * the templates are isolated to the handling object, so calls to $this, should refer to the handling PageObject.
	 * $file is the path to the file to include
	 * $global should be a $this reference to the RequestHandler class that calls this function.
	 */
	public function executeTemplate($file,$global){
		${TEMPLATE_REFERENCE_VARIABLE} = $this;
		${GLOBAL_REFERENCE_VARIABLE} = $global; //This will give the page a reference to this RequestHandler to access public methods
		${USER_REFERENCE_VARIABLE} = $this->request->user;
		include $file; //Execution of the template begins.
	}

	/*
	 * This function will be called by most template pages
	 * Overwrite it to set a new title for each page.
	 */
	public function pageTitle(){
		echo SITE_NAME;
	}
}
?>
