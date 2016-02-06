<?php
require 'serverconfig.php';

date_default_timezone_set('America/New_York');

define('SERVER_INI_FILE','server/config.ini');

//Use this array to set regular expressions for rewrite rules.
//Use the format "/path/to/file.php" => "/file/(?'option'\w+)"
//If REWRITE_ONLY is true, you can allow a specific file through using the syntax: "file.php" => null.
$REWRITE_RULES = array(
	'test.php' => "/test/(?'num'\d+)"
);

//Whether or not rewriting should be attempted at all.
define('REWRITE_ENABLE',true);
//ONLY allow pages to be accessed if they're in the rewrite rules.
//  will be ignored if REWRITE_ENABLE is FALSE.
define('REWRITE_ONLY',false);

/****************************************************************/
/** Below are all functionality tweaks. I recommend you don't  **/
/** change any of this unless you know what you're doing.      **/
/****************************************************************/
//This is the extension that requests must have in order to be considered files to execute
//Requests that do not end in this extension will not be executed.
//For example, if the value of this was '.php', only a request like '/page.php' will be executed, '/page.xyz' would not.
//Following the same logic, if this is left empty (''), then no extension is used.
//Note, however, no matter what value here, the corresponding file is still found using INCLUDE_PHP_EXTENSION.
//So, if this is '.php', then 'page.php' is still executed using 'page.func.php'.
//  if this is '', 'page' is 'page.func.php' and 'page.xzy' is 'page.xyz.func.php'.
define('REQUEST_PHP_EXTENSION','.php');

//The extension for the php files that will be included
define('INCLUDE_PHP_EXTENSION','.func.php');
//The path to the php include files for each requested page
define('INCLUDE_PATH_PHP','page-functions/');
//The extension for the php template files that will be included
define('INCLUDE_TEMPLATE_EXTENSION','.php');
//The path for the template files corresponding to each php include file
define('INCLUDE_PATH_TEMPLATE','page-content/');

//The path for files that may be included in a page (ex: common header and footer files).
define('INCLUDE_PATH_PAGE_INCLUDE','page-include/');

//The external path that stylesheets will be located in. Make sure it ends with a '/'.
define('STYLESHEET_PATH','/res/styles/');


// Disable account after too many unsuccessful logins
define('DISABLED_ACCOUNT_PERIOD',60 * 5);
// How many unsuccessful logins before an account is disabled
define('DISABLED_ACCOUNT_TRIES',5);
// Within how many seconds must the last unsuccessful login have occurred
define('DISABLED_ACCOUNT_ATTEMPT_MARGIN',5 * 60 * 60);

/****************************************************************/
/** Session config details.                                    **/
/****************************************************************/
//The largest amount of time we will allow a session to go without use.
//A user will need to log in again if he/she has not used that session within this amount of time.
define('SESSION_EXPIRATION_AGE',1209600); //1209600 = 2 weeks.


/****************************************************************/
/** Authentication config details.                             **/
/****************************************************************/
define('INPUT_EMAIL_MAX_LENGTH',255);
define('INPUT_EMAIL_MIN_LENGTH',6); // a@b.cd

define('INPUT_FULL_NAME_MAX_LENGTH',128);
define('INPUT_ADDRESSING_NAME_MAX_LENGTH',64);
define('INPUT_NAME_MIN_LENGTH',2);

define('INPUT_PASSWORD_MAX_LENGTH',255);
define('INPUT_PASSWORD_MIN_LENGTH',8);

define('AUTH_HASH_COMPLEXITY',14);

/****************************************************************/
/** The config definitions below here should NOT be modified!  **/
/** Unless you really know what you're doing, and are prepared **/
/** to make the changes required in other files, changing      **/
/** anything here will just break everything. Don't do it.     **/
/****************************************************************/


//Define the name of the class that will enclose any script that handles a page request
define('PAGE_REQUEST_CLASS_PARENT','PageObject');
//Define the name of the class that will enclose any script that handles an API request
define('API_REQUEST_CLASS_PARENT','APIObject');

//???
define('REQUEST_FUNC_GET_TEMPLATE','getTemplate');
//Function that the controller will call to determine if a user must be logged in to view the requested page. Called before preExecute.
define('REQUEST_FUNC_REQUIRE_LOGGED_IN','requireLoggedIn');
define('REQUEST_FUNC_PRE_EXECUTE','preExecute');
define('REQUEST_FUNC_POST_EXECUTE','postExecute');
//Define the name of the function within REQUEST_CLASS that will return the output data after the request has been handled
define('REQUEST_FUNC_RET_DATA','getData');
//Define the name of the function within REQUEST_CLASS that will return the output status after the request has been handled
define('REQUEST_FUNC_RET_STATUS','getStatus');

//Define the name of the variable that will be used to reference the template's page object.
//define('TEMPLATE_REFERENCE_VARIABLE','Page'); //Ex: if this is 'Page', then inside the template, all functions calls will be made by calling $Page ($Page->doSomething()).
//define('GLOBAL_REFERENCE_VARIABLE','GlobalPage'); //Same as the above, except this will reference global functions provided by the RequestHandler.
//define('USER_REFERENCE_VARIABLE','User'); //The name of the variable that will contain information about the current user

define('STATUS_OKAY',200);
define('STATUS_NOT_FOUND',404);
define('STATUS_FAILURE',500);
?>
