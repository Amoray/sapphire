<?php

$template = \template::stamp('admin/default');


$blocks		= array(
	new page(),
	new section(),
	new user(),
	new menu()
);

\template::add($template, array_map(function ($value) use ($template)
{
	
	$item	= $template->get('section')
			->settitle($value::NOUN)
			->setversion($value::VERSION)
	;

	return $item;

}, $blocks));

\sys\TEMPLATE::$template->settitle('Admin');

echo \sys\TEMPLATE::assemble($template);

?>