<?php 
$res = getShippingTypeApi();
print_r($res);exit;

//获取运输方式
function getShippingTypeApi(){
	$paramArr= array(
			'method'	=> 'trans.carrier.abb.get',
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'username'  => 'datacenter',
	);
	return _apiGet($paramArr);
}
function _apiGet($paramArr){
	$url		= 'http://idc.gw.open.valsun.cn/router/rest?';
	//生成签名
	$sign    	= createSign($paramArr);
	//组织参数
	$strParam 	= createStrParam($paramArr);
	$strParam 	.= 'sign='.$sign;
	//构造Url
	$urls 		= $url.$strParam;
	$cnt		= 0;
	while($cnt < 3 && ($result=@vita_get_url_content($urls))===FALSE) $cnt++;
	$data 		= json_decode($result,true);
	return $data['data'];
}
//签名函数
function createSign ($paramArr,$token = '') {
	$token  = '5f5c4f8c005f09c567769e918fa5d2e3'; //用户purchase token
	$sign = $token;
	ksort($paramArr);
	foreach ($paramArr as $key => $val) {
		if ($key !='' && $val !='') {
			$sign .= $key.$val;
		}
	}
	$sign = strtoupper(md5($sign.$token));
	return $sign;
}
//组参函数
function createStrParam ($paramArr) {
	$strParam = '';
	foreach ($paramArr as $key => $val) {
		if ($key != '' && $val !='') {
			$strParam .= $key.'='.urlencode($val).'&';
		}
	}
	return $strParam;
}
//获取数据兼容file_get_contents与curl
function vita_get_url_content($url) {
	$ch = curl_init();
	$timeout = 30;
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$file_contents = curl_exec($ch);
	curl_close($ch);
	return $file_contents;
}

?>