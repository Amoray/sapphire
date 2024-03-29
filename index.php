<?php

use \sys\TEMPLATE;

require 'config.php';



// Do a quick check for special requests (admin, service)
list($uri_special) = \sys\BASIC::$request_uri;
switch ($uri_special) 
{
	case 'admin':

		TEMPLATE::resetHeader("admin/header");
		TEMPLATE::resetFooter("admin/footer");
		TEMPLATE::css('admin.css');

		// folder exists
		if (file_exists( \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) ."/index.php" )) 
		{
			require \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) ."/index.php";
		}
		// file exists
		elseif (file_exists( \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) .".php")) 
		{
			require \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) .".php";
		}
		// hurr durr
		else
		{
			require \sys\BASIC::$content . "admin/index.php";
		}

		break;
	
	case 'service':

		TEMPLATE::resetHeader("service/header");
		TEMPLATE::resetFooter("service/footer");

		// folder exists
		if (file_exists( \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) ."/index.php" )) 
		{
			require \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) ."/index.php";
		}
		// file exists
		elseif (file_exists( \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) .".php")) 
		{
			require \sys\BASIC::$content . implode('/', \sys\BASIC::$request_uri) .".php";
		}
		// hurr durr
		else
		{
			require \sys\BASIC::$content . "service/index.php";
		}

		break;

	default:
		
		break;
}

?>