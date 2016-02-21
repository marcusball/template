<?php
require 'serverconfig.php';

date_default_timezone_set('America/New_York');

define('SERVER_INI_FILE',__DIR__.'/server/config.ini');

//Define the name of the class that will enclose any script that handles a page request
define('PAGE_REQUEST_CLASS_PARENT','PageObject');
//Define the name of the class that will enclose any script that handles an API request
define('API_REQUEST_CLASS_PARENT','APIObject');

//Function that the controller will call to determine if a user must be logged in to view the requested page. \
//  Called before preExecute.
define('REQUEST_FUNC_REQUIRE_LOGGED_IN','requireLoggedIn');
define('REQUEST_FUNC_PRE_EXECUTE','preExecute');
define('REQUEST_FUNC_POST_EXECUTE','postExecute');
?>
