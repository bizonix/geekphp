<?php

set_time_limit(0);
//------获取料号-------
mysql_connect("173.254.246.105","root", "huanhuan123019");
mysql_select_db("file_tfs");
mysql_query("set names utf8");
$table_extention = array(1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f');
$baseDir = 'http://ebayimg.cndirect.com/newcdn/';
$saveDir = '/data/images/';
if(!is_dir($saveDir)){
	mkdir($saveDir);
}
foreach($table_extention as $v){
	$table	= "tfs_original_image_".$v;	
	$sql	= "select original_name from $table where platform = '2' ";
	$query	= mysql_query($sql);
	$arr	= array();
	while(1 && $retData	=	mysql_fetch_array($query)){
		$arr[]	= $retData;
	}
	foreach($arr as $ret){
		$originalName = $ret['original_name'];
		$originalName = explode(".",$originalName);
		$firstPre	= substr($originalName[0],0,2);
		$saveDir1	= $saveDir.$firstPre."/";
		if(!is_dir($saveDir1)){
			mkdir($saveDir1);
		}
		$secondPre  = substr($originalName[0],2,1);
		$saveDir1	= $saveDir1.$secondPre."/";
		if(!is_dir($saveDir1)){
			mkdir($saveDir1);
		}
		
		$sku = explode("-",$originalName[0]);
		$spu = explode("_",$sku[0]);
		$saveDir1 .= $spu[0]."/";
		if(!is_dir($saveDir1)){
			mkdir($saveDir1);
		}

		$imgDir = $baseDir.$firstPre."/".$secondPre."/".$originalName[0]."-zeagoo889.jpg";
		
		$saveRes = GrabImage($imgDir,$saveDir1.$ret['original_name']); 
		file_put_contents($saveDir."aaa_".date("Y_m_d").".txt",$ret['original_name']."\r\n",FILE_APPEND);
	}
}
function GrabImage($url,$filename="") { 
	if($url=="") return false; 

	if($filename=="") { 
		$ext=strrchr($url,"."); 
		if($ext!=".gif" && $ext!=".jpg" && $ext!=".png") return false; 
		$filename=date("YmdHis").$ext; 
	} 

	ob_start(); 
	readfile($url); 
	$img = ob_get_contents(); 
	ob_end_clean(); 
	$size = strlen($img); 

	$fp2=@fopen($filename, "a"); 
	fwrite($fp2,$img); 
	fclose($fp2); 

	return $filename; 
} 
