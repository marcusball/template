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

  public function testCleanPathRoot(){
    $this->assertEquals(Request::cleanPath(''), 'index');
    $this->assertEquals(Request::cleanPath('/'), 'index');

    $this->assertEquals(Request::cleanPath('/index.php'), 'index'); //Needs to be here in case Config::core('request_php_extension') is != '.php'

    $this->assertEquals(Request::cleanPath('/index'.Config::core('request_php_extension')), 'index');

    $this->assertEquals(Request::cleanPath('/index'.Config::core('request_php_extension').'?test=true&false'), 'index');
  }

  public function testDirectory(){
    $this->assertEquals(Request::cleanPath('/subdirectory/'),'subdirectory/index');

    $this->assertEquals(Request::cleanPath('/subdirectory/index'.Config::core('request_php_extension')),'subdirectory/index');

    $this->assertEquals(Request::cleanPath('/subdirectory/test'.Config::core('request_php_extension')),'subdirectory/test');

    $this->assertEquals(Request::cleanPath('/subdirectory/nested/'),'subdirectory/nested/index');
  }

  public function testRequestIndex(){
    //Simple tests of requesting the index page
    {
      $request = Request::createRequest('index.php', '/', false);
      $this->assertEquals($request->getPath(), 'index');
    }
    {
      $request = Request::createRequest('/', '/', false);
      $this->assertEquals($request->getPath(), 'index');
    }
  }

  public function testSetMethod(){
    $makeRequestWithAndGetMethod = function($method){
      $request = Request::createRequest('index.php', '/', false);
      $request->setMethod($method);
      return $request->getMethod();
    };

    $request = Request::createRequest('index.php', '/', false);
    $this->assertEquals(RequestMethod::GET, $request->getMethod());

    $this->assertEquals($makeRequestWithAndGetMethod('GET'), RequestMethod::GET);
    $this->assertEquals($makeRequestWithAndGetMethod('get'), RequestMethod::GET);
    $this->assertEquals($makeRequestWithAndGetMethod(RequestMethod::GET), RequestMethod::GET);

    $this->assertEquals($makeRequestWithAndGetMethod('POST'), RequestMethod::POST);
    $this->assertEquals($makeRequestWithAndGetMethod('post'), RequestMethod::POST);
    $this->assertEquals($makeRequestWithAndGetMethod(RequestMethod::POST), RequestMethod::POST);

    $this->assertEquals($makeRequestWithAndGetMethod('PUT'), RequestMethod::PUT);
    $this->assertEquals($makeRequestWithAndGetMethod('put'), RequestMethod::PUT);
    $this->assertEquals($makeRequestWithAndGetMethod(RequestMethod::PUT), RequestMethod::PUT);

    $this->assertEquals($makeRequestWithAndGetMethod('PATCH'), RequestMethod::PUT);
    $this->assertEquals($makeRequestWithAndGetMethod('patch'), RequestMethod::PUT);

    $this->assertEquals($makeRequestWithAndGetMethod('DELETE'), RequestMethod::DELETE);
    $this->assertEquals($makeRequestWithAndGetMethod('delete'), RequestMethod::DELETE);
    $this->assertEquals($makeRequestWithAndGetMethod(RequestMethod::DELETE), RequestMethod::DELETE);
  }

  /**
  * @expectedException InvalidArgumentException
  */
  public function testInvalidSetMethod(){
    $makeRequestWithMethod = function($method){
      $request = Request::createRequest('index.php', '/', false);
      $request->setMethod($method);
      return $request;
    };

    $makeRequestWithMethod('head');
    $makeRequestWithMethod(100);
  }

  public function testValidUrlRewrite(){
    $rewriteRules = array(
      //Test the ability to navigate to site.com/some/other and be handled by 'test/file.php'
      'test/file.php' => '^/some/other$'
    );

    Config::set(array('rewriterules'), $rewriteRules);

    //Function to get the parsed file from a request url
    $getUrlFilePath = function($requestUrl){
      $request = Request::createRequest($requestUrl, '/', true);
      return $request->getPath();
    };

    $this->assertEquals($getUrlFilePath('/some/other'), 'test/file');
    $this->assertEquals($getUrlFilePath('/some/other/'), 'test/file');
    $this->assertEquals($getUrlFilePath('/test/file.php'), 'test/file');

    $this->assertNotEquals($getUrlFilePath('/some/other.php'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/other.lol'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/other.ph'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/othertest'), 'test/file');

    $this->assertNotEquals($getUrlFilePath('/test/file.lol'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/file.ph'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/filetest'), 'test/file');
  }

  public function testInvalidUrlRewriteSameBase(){
    $rewriteRules = array(
      //Test the ability to navigate to site.com/some/other and be handled by 'test/file.php'
      'test/file.php' => '^/some/other$'
    );

    Config::set(array('rewriterules'), $rewriteRules);

    //Function to get the parsed file from a request url
    $getUrlFilePath = function($requestUrl){
      $request = Request::createRequest($requestUrl, '/', true);
      return $request->getPath();
    };

    $this->assertNotEquals($getUrlFilePath('/some/other.php'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/other.lol'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/other.ph'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/some/othertest'), 'test/file');

    $this->assertNotEquals($getUrlFilePath('/test/file.lol'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/file.ph'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/filetest'), 'test/file');
  }

  public function testInvalidUrlRewriteFileBase(){
    $rewriteRules = array(
      //Test the ability to navigate to site.com/some/other and be handled by 'test/file.php'
      'test/file.php' => '^/some/other$'
    );

    Config::set(array('rewriterules'), $rewriteRules);

    //Function to get the parsed file from a request url
    $getUrlFilePath = function($requestUrl){
      $request = Request::createRequest($requestUrl, '/', true);
      return $request->getPath();
    };

    $this->assertNotEquals($getUrlFilePath('/test/file.lol'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/file.ph'), 'test/file');
    $this->assertNotEquals($getUrlFilePath('/test/filetest'), 'test/file');
  }

  public function testValidUrlRewriteForced(){
    $rewriteRules = array(
      //Test the ability to navigate to site.com/some/other and be handled by 'test/file.php'
      'test/file.php' => '^/some/other$'
    );

    Config::set(array('rewriterules'), $rewriteRules);

    //Function to get the parsed file from a request url
    $getUrlFilePath = function($requestUrl){
      $request = Request::createRequest($requestUrl, '/', true, true);
      return $request->getPath();
    };

    $this->assertEquals($getUrlFilePath('/some/other'), 'test/file');
    $this->assertEquals($getUrlFilePath('/some/other/'), 'test/file');
  }

  public function testInvalidUrlRewriteForced(){
    $rewriteRules = array(
      //Test the ability to navigate to site.com/some/other and be handled by 'test/file.php'
      'test/file.php' => '^/some/other$'
    );

    Config::set(array('rewriterules'), $rewriteRules);


    $request = Request::createRequest('/test/file.php', '/', true, true);
    $this->assertFalse($request); //Noi found (because direct access is disallowed)
  }
} ?>
