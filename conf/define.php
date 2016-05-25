<?php
define("WEB_PATH", str_replace(DIRECTORY_SEPARATOR, '/',str_replace('conf', '', dirname(__FILE__))));
define("TPL", '/template/v1');
define("TPL_PATH", WEB_PATH."html".TPL);
define("WEB_URL", 'http://'.$_SERVER['HTTP_HOST']."/");
define("WEB_API", 'http://api'.str_replace('www', '', $_SERVER['HTTP_HOST']));
?>