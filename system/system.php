<?php

namespace sys;

/**
* Manager for basic functionality
*/
class BASIC
{
	const		CLASSES		= "/system/class/";
	const		VERSION		= "0.0.1";
	
	static		$DIR		= "";
	static		$DEV		= false;

	static function INIT($DIR)
	{
		self::$DIR = $DIR;
		
		if (false !== strpos($_SERVER['HTTP_HOST'], '.dev') || false !== strpos($_SERVER['HTTP_HOST'], '.spi-net.net')) 
		{
			self::$DEV = true;
		}

		error_reporting( E_ALL & ~E_STRICT & ~E_NOTICE );
		ini_set('display_errors', self::$DEV);
		ini_set('log_errors', true);
		ini_set('html_errors', false);

		spl_autoload_register('self::autoload');

	}

	static function error_handler( $number, $string, $file, $line, $context )
	{
		if ( !(error_reporting() & $number) )  
		{
			return false;
		}

		if ( !ini_get('display_errors') ) 
		{
			self::graceful_close();
		}

		$title = call_user_func(function ($number)
		{
			switch ($number) 
			{
				case E_ERROR: return "E_ERROR"; break;
				case E_WARNING: return "E_WARNING"; break;
				case E_PARSE: return "E_PARSE"; break;
				case E_NOTICE: return "E_NOTICE"; break;
				case E_CORE_ERROR: return "E_CORE_ERROR"; break;
				case E_CORE_WARNING: return "E_CORE_WARNING"; break;
				case E_COMPILE_ERROR: return "E_COMPILE_ERROR"; break;
				case E_COMPILE_WARNING: return "E_COMPILE_WARNING"; break;
				case E_USER_ERROR: return "E_USER_ERROR"; break;
				case E_USER_WARNING: return "E_USER_WARNING"; break;
				case E_USER_NOTICE: return "E_USER_NOTICE"; break;
				case E_STRICT: return "E_STRICT"; break;
				case E_RECOVERABLE_ERROR: return "E_RECOVERABLE_ERROR"; break;
				case E_DEPRECATED: return "E_DEPRECATED"; break;
				case E_USER_DEPRECATED: return "E_USER_DEPRECATED"; break;
				case E_ALL: return "E_ALL"; break;
			}
		}, $number);

		echo "<h1>{$title}</h1>\n";
		echo "<pre>[ {$number} ] {$string}</pre>\n";
		echo "<pre>{$file}({$line})</pre>\n";
		\sys\BASIC::backtrace("{$file}{$line}");
		echo "<p>". PHP_MAJOR_VERSION .".". PHP_MINOR_VERSION .".". PHP_RELEASE_VERSION ." ( ". PHP_OS ." ) </p>\n";

		exit;
	}

	static function exception_handler($exception)
	{
		if ( !ini_get('display_errors') ) 
		{
			self::graceful_close();
		}

		echo "<h1>". get_class($exception) ."</h1>\n";
		echo "<pre>[ {$exception->getCode()} ] {$exception->getMessage()}</pre>\n";
		echo "<pre>{$exception->getFile()}({$exception->getLine()})</pre>\n";
		echo "<hr /><h3>Backtrace</h3><pre>". $exception->getTraceAsString() ."</pre>";
		echo "<p>". PHP_MAJOR_VERSION .".". PHP_MINOR_VERSION .".". PHP_RELEASE_VERSION ." ( ". PHP_OS ." ) </p>\n";

		exit;
	}

	static function graceful_close()
	{
		$wrapper = \template::stamp('wrapper');
		$wrapper->settitle("Caster 4");

		$header = \template::stamp('header');
		$footer = \template::stamp('footer');

		$wrapper->glue('content', (string)$header);
		$wrapper->glue('content', "Looks like we're having a bit of an issue with the site. We have had to stop the page from loading to prevent unexpected results.");
		$wrapper->glue('content', (string)$footer);

		echo $wrapper;

		exit;
	}

	static function autoload( $classname )
	{
		$loadme = self::$DIR . self::CLASSES . $classname . ".php";
		$loadme = str_replace('\\', '/', $loadme);
		include( $loadme );
	}

	static function backtrace()
	{
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		echo "<hr /><h3>Backtrace</h3><pre>";
		foreach ($backtrace as $key => $value) {
			echo "#{$key} {$value['file']}({$value['line']}) - {$value['class']}{$value['type']}{$value['function']}\n";
		}
		echo "</pre>";
	}

}

?>