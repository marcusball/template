<?php
namespace pirrs;
class Config{
  private static $_config = null;

  /**
   * The string which, when detected in a config string value,
   *   will be replaced with the value of __DIR__ from a file
   *   in the root of this template system.
   */
  private static $baseDirReplace = '{BASEDIR}';
  /**
   * Get the value of config.ini members. Use the call format:
   *   Config::{section name}() or Config::{section name}(variable name).
   *
   * For example:
   *   Config::database('database_username')
   *
   * When any function 'foo' is called, using a technique like 'Config::foo()',
   *   the value of $name will be 'foo'. 'Config::foo("bar")' will have
   *   the array $arguments taking the value {"bar"}.
   */
  public static function __callStatic($name, $arguments){
    if(self::$_config === null){
      self::loadConfig();
    }

    if(isset(self::$_config[$name])){
      $requestedVar = self::$_config[$name];
      if(count($arguments) > 0 && isset($requestedVar[$arguments[0]])){
        $requestedVar = self::$_config[$name][$arguments[0]];
      }

      if(is_string($requestedVar) || is_array($requestedVar)){
        $requestedVar = str_replace(self::$baseDirReplace, BASE_DIRECTORY, $requestedVar);
      }
      return $requestedVar;
    }

    Log::error('Invalid config variable \''.$name.'\'!');
    throw new \Exception('Invalid config variable \''.$name.'\'!');
    return null;
  }

  private static function loadConfig(){
    self::$_config = parse_ini_file(SERVER_INI_FILE, true, INI_SCANNER_TYPED);
  }
}
?>
