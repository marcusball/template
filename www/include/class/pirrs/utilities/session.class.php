<?php
namespace pirrs\utilities;
class Session{
	/*
	 * Check if a session exists before running session_start().
	 * Source: http://php.net/manual/en/function.session-status.php
	 */
	public static function isSessionStarted(){
		if(php_sapi_name() !== 'cli'){
			if(version_compare(phpversion(), '5.4.0', '>=') && function_exists('\session_status')){
				return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
			}
			else{
                if(function_exists('\session_id')){
                    return session_id() === '' ? FALSE : TRUE;
                }
			}
		}
		return FALSE;
	}
    
    /*
     * Wrapper for session_start(), with a check to make sure PHP sessions extension exists.
     */
    public static function startSession(){
        if(function_exists('\session_start')){
            session_start();
        }
        else{
            //If we're not in a production environment
            if(!IS_PRODUCTION){
                \pirrs\Log::warning('PHP sessions appear to be disabled!');
            }
        }
    }
}