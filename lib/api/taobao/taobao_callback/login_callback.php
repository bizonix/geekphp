<?php
//获取登录授权地址
//https://oauth.taobao.com/authorize?client_id=21460636&redirect_uri=http://202.103.191.209:88/json.php&response_type=code

$code			=	$_REQUEST['code'];						//通过访问https://oauth.taobao.com/authorize获取code
$grant_type		=	'authorization_code';
$redirect_uri	=	'http://202.103.191.209:88/json.php';	//此处回调url要和后台设置的回调url相同
$client_id		=	'21460636';								//自己的APPKEY
$client_secret	=	'df0cb97ac64f603c799082dde8966c6b';		//自己的appsecret
 
//请求参数
$postfields= array('grant_type'     => $grant_type,
                     'client_id'     => $client_id,
                     'client_secret' => $client_secret,
                     'code'          => $code,
                     'redirect_uri'  => $redirect_uri
);
 
$url = 'https://oauth.taobao.com/token';
 
$token = json_decode(curl($url,$postfields));
$access_token = $token->access_token;
 
//打印token
echo "token返回结果：</br>";
echo json_encode($token);
 
//POST请求函数
function curl($url, $postFields = null)
{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (is_array($postFields) && 0 < count($postFields))
		{
			$postBodyString = "";
			foreach ($postFields as $k => $v)
			{
				$postBodyString .= "$k=" . urlencode($v) . "&"; 
			}
			unset($k, $v);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  
 			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
		}
		$reponse = curl_exec($ch);
		if (curl_errno($ch)){
			throw new Exception(curl_error($ch),0);
		}
		else{
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode){
				throw new Exception($reponse,$httpStatusCode);
			}
		}
		curl_close($ch);
		return $reponse;
}
 
?>
