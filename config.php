<?php

use \sys\BASIC;
use \sys\TEMPLATE;

require 'system/system.php';
BASIC::INIT(__DIR__);

set_error_handler('\sys\BASIC::error_handler');
set_exception_handler('\sys\BASIC::exception_handler');

require 'system/template/template.php';

TEMPLATE::init();

require 'system/database/redbean/rb.php';
R::setup("mysql:host=localhost;dbname=c4", "c4usr", "YjY3YTAxZTg4ZWQxNGI0YmY0ZTYwMTlk");
R::freeze(1);
R::setStrictTyping(false);

?>