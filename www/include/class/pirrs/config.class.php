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
      self::loadConfig(SERVER_INI_FILE);
    }

    do{
      if(isset(self::$_config[$name])){
        $requestedVar = self::$_config[$name];
        if(count($arguments) > 0){
          //Will recursively dive into config arrays
          foreach($arguments as $argument){
            if(isset($requestedVar[$argument])){
              $requestedVar = $requestedVar[$argument];
            }
            else{
              //Someone asked for a config variable that doesn't exist!
              //Break out of the while(false) loop
              break 2;
            }
          }
        }

        if(is_string($requestedVar) || is_array($requestedVar)){
          $requestedVar = str_replace(self::$baseDirReplace, BASE_DIRECTORY, $requestedVar);
        }
        return $requestedVar;
      }
    } while (false); //Executes body once, but enables "break" to fall through,

    $invalidVar = $name;
    if(count($arguments) > 0){
      $invalidVar = $name . '->' . implode('->',$arguments);
    }
    Log::error('Invalid config variable \''.$invalidVar.'\'!');
    throw new \Exception('Invalid config variable \''.$invalidVar.'\'!');

    return null;
  }

  /**
   * Overwrite an existing configuration value with a $newValue.
   * @param $configKeys An array heirarchially stating which key should be changed,
   *   with the highest level first, followed by the next highest level, until the
   *   reaching the actual name of the configuration variable to be changed.
   * @param $newValue The value which should be set
   * @example To change the value of the "environment" config value within the "database"
   *   configuration section, $configKeys should be array('database','environment').
   */
  public static function set(array $configKeys, $newValue){
    if(self::$_config === null){
      self::loadConfig(SERVER_INI_FILE);
    }

    //Make sure a config key was given, to prevent overwriting entire configuration
    if(count($configKeys) > 0){
      $requestedVar = &self::$_config;

      //Recursively dive into configuration values
  		foreach($configKeys as $configKey){
        if(isset($requestedVar[$configKey])){
          $requestedVar = &$requestedVar[$configKey];
        }
        else{
          $invalidVar = implode('->',$configKeys);
          Log::error('Invalid config variable \''.$invalidVar.'\'!');
          throw new \Exception('Invalid config variable \''.$invalidVar.'\'!');
        }
  		}

      //Assign the new value
      $requestedVar = $newValue;
    }
  }

  private static function loadConfig($filename){
    self::$_config = parse_ini_file($filename, true, INI_SCANNER_TYPED);
  }
}
?>
