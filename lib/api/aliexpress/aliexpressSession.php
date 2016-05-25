<?php
class AliexpressSession
{
	protected $server		  =	'https://gw.api.alibaba.com';
	protected $rootpath		  =	'openapi';					//openapi,fileapi
	protected $protocol		  =	'param2';					//param2,json2,jsonp2,xml2,http,param,json,jsonp,xml
	protected $ns			  =	'aliexpress.open';
	protected $version		  =	1;
    //这里先写死，后面动态根据账号赋值
          
	protected $appKey		  =	'4221667';					//填自己的
	protected $appSecret	  =	'uDz8xleLwYsj';				//填自己的
	protected $refresh_token  =	"24e2771e-d4e2-4670-8f3b-e00ddeef513b";//填自己的S
	//protected $callback_url   =	C('SMT_GET_REFRESH_TOKEN_URL')."account/4221667";
	protected $callback_url   =	'http://www.weclu.com/public/getSmtRefrashToken/data';
	protected $access_token ;
	protected $tokenjson = '';

	protected $redirectUrl='http://www.weclu.com/public/getSmtRefrashToken/data';
    protected $logname;
    protected $account;
    
    function __construct() {
	   $this->logname = date("Y-m-d_H-i-s").rand(1, 9).'.log';
	}

	public function setConfig($account, $appKey, $appSecret, $refresh_token){
	    $this->account		 = $account;
		$this->appKey		 = $appKey;
		$this->appSecret	 = $appSecret;
		if($this->refresh_token != $refresh_token){
			$this->tokenjson = '';
		}
		$this->refresh_token = $refresh_token;
	}	
	public function setCompanyId($company){
		$this->callback_url	 .= '/'.$company.'_'.$this->appKey.'_'.$this->appSecret.'_'.$this->account;
		$this->redirectUrl	 .= '/'.$company.'_'.$this->appKey.'_'.$this->appSecret.'_'.$this->account;
	}
	public function doInit(){
		$token = $this->getToken();   
		$this->access_token	= $token['access_token']; 
	}
    
    public function Curl($url,$vars=''){
		$ch =curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($vars));
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
		$content = curl_exec($ch);
		curl_close($ch);
        //写传入和返回结果日志
        //$this->backupRequestAndResponseXml($url.'/'.http_build_query($vars), $content);
		return $content;
	}
	
	//获取数据兼容file_get_contents与curl
    public function vita_get_url_content($url) {
	    $ch = curl_init();
	    $timeout = 30;
	    curl_setopt ($ch, CURLOPT_URL, $url);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $file_contents = curl_exec($ch);
	    curl_close($ch);
	    return $file_contents;
	}
	
	//生成签名
	public function Sign($vars){
		$str = '';
		ksort($vars);
		foreach($vars as $k=>$v){
			$str .= $k.$v;
		}
		return strtoupper(bin2hex(hash_hmac('sha1',$str,$this->appSecret,true)));
	}
	
	public function getCode(){
		$getCodeUrl = 'http://gw.api.alibaba.com/auth/authorize.htm?client_id='.$this->appKey .'&site=aliexpress&redirect_uri='.$this->callback_url.'&_aop_signature='.$this->Sign(array('client_id' => $this->appKey,'redirect_uri' =>$this->callback_url,'site' => 'aliexpress'));
		//echo $getCodeUrl;exit;
		header("Location:{$getCodeUrl}");
	}
	public function setProtocol($protocol){
		$this->protocol = $protocol;
	}
	//获取授权
	public function getToken($code=''){
		if (!empty($this->tokenjson)){
			return $this->tokenjson;
		}
		if(!empty($this->refresh_token)){
			$getTokenUrl = "{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/refreshToken/{$this->appKey}";
			$data = array(
				'grant_type'	=> 'refresh_token',		//授权类型
				'client_id'		=> $this->appKey,				//app唯一标示
				'client_secret'	=> $this->appSecret,			//app密钥
				'refresh_token'	=> $this->refresh_token,		//app入口地址
			);
			$data['_aop_signature'] = $this->Sign($data); 
		}else{
			$getTokenUrl="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/getToken/{$this->appKey}";
			$data =array(
				'grant_type'		=> 'authorization_code',	//授权类型
				'need_refresh_token'=> 'true',				//是否需要返回长效token
				'client_id'			=> $this->appKey,				//app唯一标示
				'client_secret'		=> $this->appSecret,			//app密钥
				'redirect_uri'		=> $this->redirectUrl,			//app入口地址
				'code'				=> $code,	//bug
			);
		}
		$this->tokenjson = json_decode($this->Curl($getTokenUrl,$data),true);
		return $this->tokenjson;
	}
	/**
	 * [apiSign 分销商接口签名]
	 * @param  [type] $apiName [接口名]
	 * @param  [type] $data    [数据]
	 * @return [type]          [签名串]
	 */
	public function apiSign($apiName,$data){
		$url = "{$this->protocol}/{$this->version}/{$this->ns}/{$apiName}/{$this->appKey}";
		$str = '';
		ksort($data);
		foreach($data as $k=>$v){
			$str .= $k.$v;
		}
		return strtoupper(bin2hex(hash_hmac('sha1',$url.$str,$this->appSecret,true)));
	}
	//获取调用的地址
	public function getUrl($apiName){
		return "{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/{$apiName}/{$this->appKey}?_aop_signature=";
	}
}
?>