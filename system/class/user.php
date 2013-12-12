<?php

use spin\core;

/**
* 	User management
*/
class user extends core
{
	const		NOUN		= "user";
	const		EXCERPT		= "Manage your users";

	const		VERSION		= "0.0.1";

	const		TABLENAME	= "user";
	const		TABLECOLS	= "id, fname, lname, email, password, request, enabled";


	const		GETACCESS	= "fname, lname, email, request, enabled";
	const		SETACCESS	= "fname, lname, email, request, enabled";

	protected	$fname		= null;
	protected	$lname		= null;
	protected	$email		= null;
	protected	$password	= null;
	protected	$request	= null;
	protected	$enabled	= 0;
}

?>