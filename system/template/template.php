<?php

require 'system/template/stampte/StampTE.php';

/**
* Templates is a StampTE wrapper
*/
class template
{
	const		TEMPLATE_DIR	= '/system/template/templates/';
	static		$icons			= false;
	static		$hrefs			= false;

	static function stamp($template)
	{
		$template_file = $_SERVER['DOCUMENT_ROOT'] . self::TEMPLATE_DIR . $template .".tpl";
		if (!file_exists($template_file))
		{
			throw new \Exception("Template File Not Found", 404);
			return;	
		}
		else
		{
			return new \StampTE(file_get_contents($template_file));
		}
	}

	static function icon($icon)
	{
		if (!self::$icons) 
		{
			self::$icons = self::stamp('icons');
		}

		$template = self::$icons->copy();
		
		return $template->get($icon);
	}

	static function glue($addto, $point, array $stamps)
	{
		if (is_object($addto) && 'StampTE' == get_class($addto))
		{
			foreach ($stamps as $value) 
			{
				if ('StampTE' == get_class($value))
				{
					$addto->glue($point, $value);
				}
			}
		}
		elseif (is_array($addto))
		{
			$object = array_pop($addto);
			foreach ($stamps as $value) 
			{
				if ('StampTE' == get_class($value))
				{
					$object->glue($point, $value);
				}
			}
			$addto[] = $object;
		}

	}

	static function add(&$addto, array $stamps)
	{
		if (is_object($addto) && 'StampTE' == get_class($addto))
		{
			foreach ($stamps as $value) 
			{
				if ('StampTE' == get_class($value))
				{
					$addto->add($value);
				}
			}
		}
		elseif (is_array($addto))
		{
			$object = array_pop($addto);
			foreach ($stamps as $value) 
			{
				if ('StampTE' == get_class($value))
				{
					$object->add($value);
				}
			}
			$addto[] = $object;
		}
	}
}

?>