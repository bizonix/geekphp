<?php
error_reporting(-1);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
//require_once "H:/svn/weclu2/weclu/framework.php";
require_once "../../framework.php";
Core::getInstance();
$fun='GetCategories';
//$fun='GetItem';
$account='betterlift99';
//$param='301692432774';
$param = array("siteId"=>2);
var_dump(A("EbayButt")->runOrigin($fun,$account,$param));
?>