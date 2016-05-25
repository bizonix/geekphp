<?php
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");

$appKey			=	895611;	
$appSecret		=	"EcwaA6#3H:p";
$redirectUrl	=	"xxx";
//$refresh_token	=	"0a439cb1-592d-45c1-9fb0-c204093c0b45";
$callback_url	=	"http://project.manage.valsun.cn:88/aliexpress/callback.php";

$getTokenUrl1	=	"https://gw.api.alibaba.com/openapi/http/1/system.oauth2/getToken/".$appKey;
$getTokenUrl2	=	"https://gw.api.alibaba.com/openapi/param2/1/system.oauth2/refreshToken/".$appKey;

$code	=	isset($_GET['code']) ? $_GET['code']: "";
if(!empty($code)){
	$json	=	getToken($appKey, $appSecret, $redirectUrl, $code, $getTokenUrl1);
	if(isset($json['refresh_token'])){
		//$json	=	refreshToken($appKey, $appSecret, $json['refresh_token'], $getTokenUrl2);
		echo "refresh_token: ".$json['refresh_token'];
	}else{
		echo "cann't get the refresh_token\n";
	}
}else{
	echo getCode($appKey, $appSecret, $callback_url);
}

function Curl($url,$vars=''){
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,1);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
	curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($vars));
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
	$content=curl_exec($ch);
	curl_close($ch);
	return $content;
}

/***********************************************************
 *	获取临时token
 */
function getToken($appKey, $appSecret, $redirectUrl, $code, $getTokenUrl){
	
	$data =array(
		'grant_type'		=>'authorization_code',	
		'need_refresh_token'=>'true',				
		'client_id'			=>$appKey,				
		'client_secret'		=>$appSecret,			
		'redirect_uri'		=>$redirectUrl,			
		'code'				=>$code,
	);
	//过期时间， 一小时
	return	json_decode(Curl($getTokenUrl,$data),true);
}


/************************************************************
 *	获取长效token
 */
function refreshToken($appKey, $appSecret, $refresh_token, $getTokenUrl){
	$data =array(
		'grant_type'		=>'refresh_token',			
		'client_id'			=>$appKey,			
		'client_secret'		=>$appSecret,			
		'refresh_token'		=>$refresh_token,		
	);
	$data['_aop_signature']	=	Sign($data,$appSecret); 
	return json_decode(Curl($getTokenUrl,$data),true);
}


function Sign($vars, $appSecret){
	$str='';
	ksort($vars);
	foreach($vars as $k=>$v){
		$str.=$k.$v;
	}
	return strtoupper(bin2hex(hash_hmac('sha1',$str,$appSecret,true)));
}


function getCode($appKey,$appSecret, $callback_url){
	$getCodeUrl		=	"https://gw.api.alibaba.com/auth/authorize.htm?client_id=".$appKey ."&site=aliexpress&redirect_uri=".$callback_url."&_aop_signature=".Sign(array('client_id' => $appKey,'redirect_uri' =>$callback_url,'site' => 'aliexpress'),$appSecret);
		
	return "<a href='".$getCodeUrl."'>Login</a>";
}
?>