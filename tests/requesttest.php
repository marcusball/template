<?php
namespace pirrs;
class RequestTest extends \PHPUnit_Framework_TestCase{
  public function testParsePathRoot(){
    //This assumes an installation to web root, not in a subdirectory

    //Request to root, '/', no filename.
    $this->assertEquals(Request::parsePath('/','/',false),'/');
    $this->assertEquals(Request::parsePath('/','\\',false),'/'); //windows

    //Request to root index file.
    $this->assertEquals(Request::parsePath('/index.php','/',false),'/index.php');
    $this->assertEquals(Request::parsePath('/index.php','\\',false),'/index.php'); //Windows
  }

public function testParsePathRootWithParams(){
    //Request to root, '/', no filename, with query params stripped
    $this->assertEquals(Request::parsePath('/?test=true&false','/',false),'/');
    $this->assertEquals(Request::parsePath('/?test=true&false','\\',false),'/'); //Windows

    //Request to root, '/', no filename, with query params preserved
    $this->assertEquals(Request::parsePath('/?test=true&false','/',true),'/?test=true&false');
    $this->assertEquals(Request::parsePath('/?test=true&false','\\',true),'/?test=true&false'); //Windows

    //Request to root index file, with query params stripped
    $this->assertEquals(Request::parsePath('/index.php?test=true&false','/',false),'/index.php');
    $this->assertEquals(Request::parsePath('/index.php?test=true&false','\\',false),'/index.php'); //Windows

    //Request to root index file, with query params preserved
    $this->assertEquals(Request::parsePath('/index.php?test=true&false','/',true),'/index.php?test=true&false');
    $this->assertEquals(Request::parsePath('/index.php?test=true&false','\\',true),'/index.php?test=true&false'); //Windows
  }

  public function testParsePathSubdirectory(){
    //This assumes an installation to web root, not in a subdirectory

    //Request to root, '/', no filename.
    $this->assertEquals(Request::parsePath('/subdirectory/','/subdirectory',false),'/');
    //$this->assertEquals(Request::parsePath('/index.php','\\',false),'/index.php'); //windows

    //Request to root index file.
    $this->assertEquals(Request::parsePath('/subdirectory/index.php','/subdirectory',false),'/index.php');
    //$this->assertEquals(Request::parsePath('/index.php','\\',false),'/index.php'); //Windows
  }

  public function testParsePathSubdirectoryWithParams(){
      //Request to root, '/', no filename, with query params stripped
      $this->assertEquals(Request::parsePath('/subdirectory/?test=true&false','/subdirectory',false),'/');

      //Request to root, '/', no filename, with query params preserved
      $this->assertEquals(Request::parsePath('/subdirectory/?test=true&false','/subdirectory',true),'/?test=true&false');

      //Request to root index file, with query params stripped
      $this->assertEquals(Request::parsePath('/subdirectory/index.php?test=true&false','/subdirectory',false),'/index.php');

      //Request to root index file, with query params preserved
      $this->assertEquals(Request::parsePath('/subdirectory/index.php?test=true&false','/subdirectory',true),'/index.php?test=true&false');
    }
} ?>