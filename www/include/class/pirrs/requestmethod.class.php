<?php
namespace pirrs;
abstract class RequestMethod extends BasicEnum{
	const GET = 1;
	const POST = 2;
	const PUT = 3;
	const DELETE = 4;

	const PATCH = self::PUT;
}
?>
