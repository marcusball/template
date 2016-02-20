<?php
namespace pirrs;
class DefaultResponses{
	public static function Success($content=null){
		$response = new Response();
		$response->setStatusCode(200);
		if(isset($content)){
			$response->setContent($content);
		}

		return $response;
	}

	public static function BadRequest(){
		$response = new Response();
		$response->setStatusCode(400);

		return $response;
	}

	public static function NotFound(){
		$response = new Response();
		$response->setStatusCode(404);

		return $response;
	}

	public static function Unauthorized(){
		$response = new Response();
		$response->setStatusCode(401);

		return $response;
	}

    public static function Login(){
        $response = new Response();
        $response->setStatusCode(302);

        $currentUrl = Request::getCurrentUrl(true);
        $loginUrl = '/authorize.php';

        if(stripos($currentUrl,$loginUrl) === 0){ //The current page is the page we're forwarding to
            $response->setContent($loginUrl);
        }
        else{
            $response->setContent($loginUrl . '?destination=' . urlencode($currentUrl));
        }

		return $response;
    }

    public static function Reauthorize(){
		return self::Login();
	}

	public static function ServerError(){
		$response = new Response();
		$response->setStatusCode(500);

		return $response;
	}
}
