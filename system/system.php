<?php

namespace sys;

/**
* Manager for basic functionality
*/
class BASIC
{
	const		CLASSES		= "/system/class/";
	const		VERSION		= "0.0.1";
	const		CONTENT		= "/content/";
	
	static		$DIR		= "";
	static		$DEV		= false;
	
	static		$content	= "";
	static		$request_uri= false;

	/**
	 * Configures as much of the environment as possible
	 * @param string $DIR run location
	 */
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

		self::request_uri();

		self::$content = $DIR . self::CONTENT;

	}

	/**
	 * Custom error handler with production graceful exit
	 * @param  int $number  error code
	 * @param  string $string  error message
	 * @param  string $file    error file location
	 * @param  string $line    error file line
	 * @return boolean         false if error is set to not get caught
	 */
	static function error_handler( $number, $string, $file, $line )
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
		echo \sys\BASIC::backtrace();
		echo "<p>". PHP_MAJOR_VERSION .".". PHP_MINOR_VERSION .".". PHP_RELEASE_VERSION ." ( ". PHP_OS ." ) </p>\n";

		exit;
	}

	/**
	 * Custom exception handler with production graceful exit
	 * @param  Exception $exception An exception object
	 */
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

	/**
	 * Gracefully closes the page so that something not ugly is displayed if there is a problem
	 * Can potentially cause issue if the problem lies in \sys\TEMPLATE.
	 */
	static function graceful_close()
	{
		$message = "<p>Looks like we're having a bit of an issue with the site. We have had to stop the page from loading to prevent unexpected results.</p>";

		TEMPLATE::init();
		TEMPLATE::$template->settitle('Error');

		echo TEMPLATE::assemble($message);

		exit;
	}

	/**
	 * Class Autoloader, assuming some kind of structure is used in filenames and paths this should prevent me from ever typing another include
	 * Also useful for not loading stuff that we just don't need to load every single time.
	 * @param  string $classname namespace and name of the class we're trying to load
	 */
	static function autoload( $classname )
	{
		$loadme = self::$DIR . self::CLASSES . $classname . ".php";
		$loadme = str_replace('\\', '/', $loadme);
		include( $loadme );
	}

	/**
	 * fetch a backtrace to this location
	 * @return string backtrace html string
	 */
	static function backtrace()
	{
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		
		$string = "<div class='backtrace'><h3>Backtrace</h3><pre>";
		
		foreach ($backtrace as $key => $value) {
			$string .= "#{$key} {$value['file']}({$value['line']}) - {$value['class']}{$value['type']}{$value['function']}\n";
		}

		$string = "</pre></div>";

		return $string;
	}

	/**
	 * quick wrapper for $_REQUEST
	 * @param  string  $name    name of key to request
	 * @param  boolean $default default to return if not found
	 * @return mixed           whatever was found, or default
	 */
	static function request($name = "", $default = null)
	{
		if ("" == $name) 
		{
			return $_REQUEST;
		}

		if (array_key_exists($name, $_REQUEST)) 
		{
			return $_REQUEST[$name];
		}
		else
		{
			return $default;
		}
	}

	/**
	 * figure what the request uri was to sort the content.
	 * in combination with .htaccess redirect to index.php
	 */
	static function request_uri()
	{
		$query = self::request('q');
		self::$request_uri = explode('/', $query);
	}
}

/**
* Template Wrapper
*/
class TEMPLATE
{
	static		$template	= false;
	static		$head		= false;
	static		$foot		= false;

	static		$js			= array();
	static		$css		= array();

	static function init($template = "wrapper")
	{
		self::$template = \template::stamp($template);
		self::$head = \template::stamp('header');
		self::$foot = \template::stamp('footer');
	}

	static function resetHeader($template = "header")
	{
		self::$head = \template::stamp($template);
	}

	static function resetFooter($template = "footer")
	{
		self::$foot = \template::stamp($template);
	}

	static function assemble($content)
	{
		$template = self::$template;
		$head = self::$head;
		$foot = self::$foot;

		\template::add($template, array_map(function ($value) use ($template)
		{
			return $template->get('script')->setsrc($value);
		}, self::$js));

		\template::add($template, array_map(function ($value) use ($template)
		{
			return $template->get('style')->setsrc($value);
		}, self::$css));

		$template->glue('header', (string)$head);
		$template->glue('footer', (string)$foot);

		$template->glue('content', (string)$content);

		return $template;
	}

	static function js($value)
	{
		self::$js[] = $value;
	}

	static function css($value)
	{
		self::$css[] = $value;
	}

}

?>