<?php
header("Content-type:text/html;charset=utf-8");
$string = 'US1+OS00002:NW';
//preg_match("/#\[([0-9,]+)\]#/", $ordercheck['ebay_noteb'], $ebayids)
if(preg_match("/^US1\+.*/", $string, $matchArr)){
	echo "<pre>"; print_r($matchArr); echo "<br>";
	$matchStr=substr($matchArr[0],4);//去除前面
	$n=strpos($matchStr,':');//寻找位置
	if($n){$matchStr=substr($matchStr,0,$n);}//删除后面
	echo $matchStr;
}else{
	echo "不匹配！";	
}