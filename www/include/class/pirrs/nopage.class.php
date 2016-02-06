<?php
namespace pirrs;
class NoPage extends RequestObject{
  public function __set($name, $value) {
    echo 'Error: Unable to set object property, '.$name.'! This template has no associated class file extending '.PAGE_REQUEST_CLASS_PARENT.' or '.API_REQUEST_CLASS_PARENT.'!';
    return null;
  }

  public function __get($name) {
    echo 'Error: Unable to get object property, '.$name.'! This template has no associated class file extending '.PAGE_REQUEST_CLASS_PARENT.' or '.API_REQUEST_CLASS_PARENT.'!';
    return null;
  }

  public function __isset($name) {
    echo 'Error: Unable to check isset of object property, '.$name.'! This template has no associated class file extending '.PAGE_REQUEST_CLASS_PARENT.' or '.API_REQUEST_CLASS_PARENT.'!';
    return false;
  }

  public function __unset($name) {
    echo 'Error: Unable to unset object property, '.$name.'! This template has no associated class file extending '.PAGE_REQUEST_CLASS_PARENT.' or '.API_REQUEST_CLASS_PARENT.'!';
    return null;
  }

  public function __call($name, $arguments) {
    echo 'Error: Unable to call object function, '.$name.'()! This template has no associated class file extending '.PAGE_REQUEST_CLASS_PARENT.' or '.API_REQUEST_CLASS_PARENT.'!';
    return null;
  }
}
?>
