<?php
$sring = 'dfadfa';
$orderid = '123456789101';
var_dump(preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid));
$orderid = '123456789101-0';
var_dump(preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid));
$orderid = '123456789101-123456789101';
var_dump(preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid));
$orderid = '123456789101-16789101';
var_dump(preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid));

//var_dump(preg_replace("<script[^>]*>.*</script>", '', $sring));
?>