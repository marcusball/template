<?php
namespace pirrs;
class Log{
	private static $errorLog;
	private static $warningLog;

	/*
	 * Call to initialize this class with defaults.
	 * $defaultLog is the log file that all log levels will initially be set to write to.
	 * $overrideLogs is an associative array where the key should be the log level,
	 *   and the value should be the corresponding log path.
	 *   Current valid log levels are 'error' and 'warning'.
	 */
	public static function construct($defaultLog = 'debug.log',$overrideLogs = null){
		self::$errorLog = $defaultLog;
		self::$warningLog = $defaultLog;

		if($overrideLogs !== null){
			if(!is_array($overrideLogs)){
				throw new Exception('$overrideLogs must be an array!');
			}

			//Change the output log for each overwrite we receive
			if(isset($overrideLogs['error'])){ self::$errorLog = $overrideLogs['error']; }
			if(isset($overrideLogs['warning'])){ self::$warningLog = $overrideLogs['warning']; }
		}
	}

	/*
	 * Here's a happy little log function.
	 * Use it for errors.
	 * $description is for a written description of the problem.
	 * $error is for the output of error functions.
	 * $debugIndex is the number of levels on the backtrace to use as the calling information.
	 *   ex: When $debugIndex = 0, the file path that gets logged is the file in which "logError()" appears.
	 *     While, when $debugIndex = 1, the file path that is logged is the file which called the function that contains logError()
	 *     Note, if the value is higher than the level returned by debug_backtrace, then it will decrement this value until a valid level is found.
	 */
	public static function error($description, $error = '', $debugIndex = 1){
		if($debugIndex < 0){
			throw new InvalidArgumentException('$debugIndex must be greater than, or equal to, zero!');
		}
		$debug = debug_backtrace();

		while((!isset($debug[$debugIndex]) || !isset($debug[$debugIndex]['file'])) && $debugIndex > 0){
			//Loop while the debug backtrace does not contain the number of calls equal to $debugIndex,
			//  or we're on an object item (so there's no file and line number), and $debugIndex is >= 0.

			$debugIndex -= 1; //Decrement debugIndex in hopes of finding a valid index.
		}

		$callLocation = self::cleanCallingFilePath($debug[$debugIndex]['file']).':'.$debug[$debugIndex]['line'];
		$data = sprintf("[%s][%s][%s] (%s): %s %s\n",
			'error',
			date('D, j M Y, \a\t g:i:s A'),
			self::getRemoteAddress(),
			$callLocation,
			$description,
			$error
		);

		$data = self::indentNewLines($data);
		file_put_contents(self::$errorLog, $data, FILE_APPEND);
	}

	/*
	 * Nice little log function for warnings.
	 * $description is for a written description of the problem.
	 * $debugIndex is the number of levels on the backtrace to use as the calling information.
	 *   ex: When $debugIndex = 0, the file path that gets logged is the file in which "logError()" appears.
	 *     While, when $debugIndex = 1, the file path that is logged is the file which called the function that contains logError()
	 *     Note, if the value is higher than the level returned by debug_backtrace, then it will decrement this value until a valid level is found.
	 */
	public static function warning($description, $debugIndex = 1){
		if($debugIndex < 0){
			throw new InvalidArgumentException('$debugIndex must be greater than, or equal to, zero!');
		}

		$debug = debug_backtrace();

		while((!isset($debug[$debugIndex]) || !isset($debug[$debugIndex]['file'])) && $debugIndex > 0){
				//Loop while the debug backtrace does not contain the number of calls equal to $debugIndex,
				//  or we're on an object item (so there's no file and line number), and $debugIndex is >= 0.

				$debugIndex -= 1; //Decrement debugIndex in hopes of finding a valid index.
			}

		$callLocation = self::cleanCallingFilePath($debug[$debugIndex]['file']).':'.$debug[$debugIndex]['line'];
		$data = sprintf("[%s][%s][%s] (%s): %s\n",
			'warning',
			date('D, j M Y, \a\t g:i:s A'),
			self::getRemoteAddress(),
			$callLocation,
			$description
		);

		$data = self::indentNewLines($data);
		file_put_contents(self::$warningLog, $data, FILE_APPEND);
	}

	/*
	 * Nice little log function for warnings.
	 * $description is for a written description of the problem.
	 * $debugIndex is the number of levels on the backtrace to use as the calling information.
	 *   ex: When $debugIndex = 0, the file path that gets logged is the file in which "logError()" appears.
	 *     While, when $debugIndex = 1, the file path that is logged is the file which called the function that contains logError()
	 *     Note, if the value is higher than the level returned by debug_backtrace, then it will decrement this value until a valid level is found.
	 */
	public static function debug($description, $debugIndex = 0){
		if($debugIndex < 0){
			throw new InvalidArgumentException('$debugIndex must be greater than, or equal to, zero!');
		}

		$debug = debug_backtrace();

		while((!isset($debug[$debugIndex]) || !isset($debug[$debugIndex]['file'])) && $debugIndex > 0){
				//Loop while the debug backtrace does not contain the number of calls equal to $debugIndex,
				//  or we're on an object item (so there's no file and line number), and $debugIndex is >= 0.

				$debugIndex -= 1; //Decrement debugIndex in hopes of finding a valid index.
			}

		$callLocation = self::cleanCallingFilePath($debug[$debugIndex]['file']).':'.$debug[$debugIndex]['line'];
		$data = sprintf("[%s][%s][%s] (%s): %s\n",
			'debug',
			date('D, j M Y, \a\t g:i:s A'),
			self::getRemoteAddress(),
			$callLocation,
			$description
		);

		$data = self::indentNewLines($data);
		file_put_contents(self::$warningLog, $data, FILE_APPEND);
	}


    public static function dump($variable, $debugIndex = 0){
		if($debugIndex < 0){
			throw new InvalidArgumentException('$debugIndex must be greater than, or equal to, zero!');
		}

		$debug = debug_backtrace();

		while((!isset($debug[$debugIndex]) || !isset($debug[$debugIndex]['file'])) && $debugIndex > 0){
				//Loop while the debug backtrace does not contain the number of calls equal to $debugIndex,
				//  or we're on an object item (so there's no file and line number), and $debugIndex is >= 0.

				$debugIndex -= 1; //Decrement debugIndex in hopes of finding a valid index.
			}

		$callLocation = self::cleanCallingFilePath($debug[$debugIndex]['file']).':'.$debug[$debugIndex]['line'];
		$data = sprintf("[%s][%s][%s] (%s): %s\n",
			'dump',
			date('D, j M Y, \a\t g:i:s A'),
			self::getRemoteAddress(),
			$callLocation,
			var_export($variable, true)
		);

		$data = self::indentNewLines($data);
		file_put_contents(self::$warningLog, $data, FILE_APPEND);
	}

    public static function trace($note){
        $debugIndex = 0;
        $debug = debug_backtrace();
        $callLocation = self::cleanCallingFilePath($debug[$debugIndex]['file']).':'.$debug[$debugIndex]['line'];

		$data = sprintf("[%s][%s][%s] (%s): %s\n%s\n",
			'trace',
			date('D, j M Y, \a\t g:i:s A'),
			self::getRemoteAddress(),
            $callLocation,
			$note,
			self::getPrintableDebugTrace()
		);

		$data = self::indentNewLines($data);
		file_put_contents(self::$warningLog, $data, FILE_APPEND);
	}

	/*
	 * Cleans the given filepath so it's nicely formatted for log output.
	 * $callingFilePath should the full path to the file which called the log function.
	 * return is a path relative to the root of the web directory (relative to our index.php).
	 * Ex: C:\path\to\www\dir\test.php (or /usr/local/www/dir/test.php) => /dir/test.php
	 */
	private static function cleanCallingFilePath($callingFilePath){
		//Using example of $callingFilePath = C:\path\to\www\dir\test.php
		$rootPath = $_SERVER['SCRIPT_FILENAME']; //Should be something like C:/path/to/www/index.php (Yes, output is '/' not '\'),
		$indexFile = $_SERVER['SCRIPT_NAME']; //Should be /index.php

		assert(substr($rootPath, -1 * strlen($indexFile)) == $indexFile, "The path to SCRIPT_FILENAME should end with SCRIPT_NAME!");

		$webDirPath = substr($rootPath, 0, strlen($rootPath) - strlen($indexFile)); //Should result in C:/path/to/www



		$callingFilePath = str_replace('\\','/',$callingFilePath); //If this is a Windows path, change '\' to '/'.
		return substr($callingFilePath, -1 * (strlen($callingFilePath) - strlen($webDirPath)));
	}

	private static function indentNewLines($message){
		$temp = preg_replace('/\n/',"\n    ",$message);
		return preg_replace('/\n\s+$/',"\n",$temp);
	}

    private static function getPrintableDebugTrace(){
        $debug = debug_backtrace();

        $printable = "";
        $indexShift = 2; //omit this many levels from the bottom of the trace (ignore this function).
        foreach($debug as $index=>$tracePoint){
            if($index >= $indexShift){
                $path = self::cleanCallingFilePath($tracePoint['file']);
                $line = sprintf('    #%d %s(%d): %s()',
                    $index - $indexShift,
                    $path, $tracePoint['line'],
                    $tracePoint['function']
                );
                $printable .= $line . "\n";
            }
        }
        return $printable;
    }

		/**
		 * Attempts to get the remote address of the client.
		 * Calls $_SERVER['REMOTE_ADDR'].
		 * @return The remote address of the client, or a
		 *   static string if remote address can't be found.
		 */
		private static function getRemoteAddress(){
			return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN ADDR';
		}
} Log::construct(); //Call to initialize defaults. This must be here.
