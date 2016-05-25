<?php
class Aliexpress{

	var $server			=	'https://gw.api.alibaba.com';
	var $rootpath		=	'openapi';					//openapi,fileapi
	var $protocol		=	'param2';					//param2,json2,jsonp2,xml2,http,param,json,jsonp,xml
	var $ns				=	'aliexpress.open';
	var $version		=	1;
	var $appKey			=	'895611';					//Ìî×Ô¼ºµÄ
	var $appSecret		=	'EcwaA6#3H:p';				//Ìî×Ô¼ºµÄ
	var $refresh_token	=	"96f3a689-a9a8-4858-bd37-7d53d673c39b";//Ìî×Ô¼ºµÄ
	var $callback_url	=	"http://202.103.191.209:88/aliexpress/callback.php";

	//var $access_token = 'd43bc953-7b74-4bc4-9ec6-7176bf5288a5';
	var $access_token ;

	public function __construct() {
	}

	public function setConfig($appKey,$appSecret,$refresh_token){
		$this->appKey		=	$appKey;
		$this->appSecret	=	$appSecret;
		$this->refresh_token=	$refresh_token;
	}	

	public function doInit(){
		$token	=	$this->getToken();     
		$this->access_token	=	$token->access_token;       
	}

	public function Curl($url,$vars=''){
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
	
	//Éú³ÉÇ©Ãû
	public function Sign($vars){
		$str='';
		ksort($vars);
		foreach($vars as $k=>$v){
			$str.=$k.$v;
		}
		return strtoupper(bin2hex(hash_hmac('sha1',$str,$this->appSecret,true)));
	}
	
    //Éú³ÉÇ©Ãû
	public function getCode(){
		$getCodeUrl = $this->server .'/auth/authorize.htm?client_id='.$this->appKey .'&site=aliexpress&redirect_uri='.$this->callback_url.'&_aop_signature='.$this->Sign(array('client_id' => $this->appKey,'redirect_uri' =>$this->callback_url,'site' => 'aliexpress'));
		return '<a href="javascript:void(0)" onclick="window.open(\''.$getCodeUrl.'\',\'child\',\'width=500,height=380\');">ÇëÏÈµÇÂ½²¢ÊÚÈ¨</a>';
	}
	
	//»ñÈ¡ÊÚÈ¨
	public function getToken(){
		if(!empty($this->refresh_token)){
			$getTokenUrl="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/refreshToken/{$this->appKey}";
			$data =array(
				'grant_type'		=>'refresh_token',		//ÊÚÈ¨ÀàÐÍ
				'client_id'			=>$this->appKey,				//appÎ¨Ò»±êÊ¾
				'client_secret'		=>$this->appSecret,			//appÃÜÔ¿
				'refresh_token'		=>$this->refresh_token,		//appÈë¿ÚµØÖ·
			);
			$data['_aop_signature']=$this->Sign($data); 
			return json_decode($this->Curl($getTokenUrl,$data));
			
		}else{
			$getTokenUrl="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/getToken/{$this->appKey}";
			$data =array(
				'grant_type'		=>'authorization_code',	//ÊÚÈ¨ÀàÐÍ
				'need_refresh_token'=>'true',				//ÊÇ·ñÐèÒª·µ»Ø³¤Ð§token
				'client_id'			=>$this->appKey,				//appÎ¨Ò»±êÊ¾
				'client_secret'		=>$this->appSecret,			//appÃÜÔ¿
				'redirect_uri'		=>$this->redirectUrl,			//appÈë¿ÚµØÖ·
				'code'				=>$_SESSION['code'],	//bug
			);
			return json_decode($this->Curl($getTokenUrl,$data));
		}
	}
	
	/**********************»ñÈ¡¶©µ¥ÐÅÏ¢**********************/
	public function findOrderListQuery(){
		$data	=	array(
			'access_token'	=>$this->access_token,
			'page'			=>'1',
			'pageSize'		=>'50',
			//'createDateStart'	=>	'08/29/2013',
			//'createDateEnd'	=>	'09/26/2013',
			'orderStatus'	=>'WAIT_SELLER_SEND_GOODS'	//等待卖家发货
			//'orderStatus'	=>'WAIT_SELLER_SEND_GOODS'	//等待卖家发货
			//'orderStatus'	=>'SELLER_SEND_PART_GOODS' //卖家部分发货
			//'orderStatus'	=>'SELLER_SEND_GOODS' //卖家已发货
			//'orderStatus'	=>'BUYER_ACCEPT_GOODS' //买家已确认收货
			//'orderStatus'	=>'NO_LOGISTICS'     //没有物流流转信息
		);
		$url		=	"{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findOrderListQuery/{$this->appKey}";
	/*
        echo $url;
        $List    	=	$this->Curl($url,$data);
        var_dump($List);
        exit;
        $List		=	json_decode($List,true);
    */
    	$List		=	json_decode($this->Curl($url,$data),true);
        
		$orderList	=	array();
		if(!empty($List["orderList"])){
			foreach($List["orderList"] as $k=>$v){
				$orderId	=	$v["orderId"];
				//if($orderId != "15009394248946") continue;
				$orderList[$orderId]['detail']	=	$this->findOrderById($orderId);
				$orderList[$orderId]['v']		=	$v;
			}
			
			for($i=2;$i<=ceil($List["totalItem"]/$data['pageSize']);$i++){
				$data['page']=$i;
				$List=json_decode($this->Curl($url,$data),true);
				foreach($List["orderList"] as $k=>$v){
					$orderId	=	$v["orderId"];
					//if($orderId != "15009394248946") continue;
					$orderList[$orderId]['detail']	=	$this->findOrderById($orderId);
					$orderList[$orderId]['v']		=	$v;
				}
			}
			
		}
		unset($List);
		return $orderList;
		
	}
	
	public function findOrderById($orderId){
		$data=array(
			'access_token'	=>$this->access_token,
			'orderId'			=>$orderId,
		);
		$url="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findOrderById/{$this->appKey}";
		return json_decode($this->Curl($url,$data),true);
	}
	/**********************»ñÈ¡ÉÌÆ·ÐÅÏ¢**********************/
	public function findProductInfoListQuery(){
		$data=array(
			'access_token'	=>$this->access_token,
			'page'			=>'1',
			'pageSize'		=>'100',
			'productStatusType'	=>'onSelling',
		);
		$url="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findProductInfoListQuery/{$this->appKey}";
		$List=json_decode($this->Curl($url,$data));
		$ProductList='';
		if(!empty($List->aeopAEProductDisplayDTOList)){
			foreach($List->aeopAEProductDisplayDTOList as $k=>$v){
				$ProductList[]=$this->findAeProductById($v->productId);
			}
			
			for($i=2;$i<=$List->totalPage;$i++){
				$data['page']=$i;
				$List=json_decode($this->Curl($url,$data));
				foreach($List->aeopAEProductDisplayDTOList as $k=>$v){
					$ProductList[]=$this->findAeProductById($v->productId);
				}
			}
			
		}
		return $ProductList;
	}
	
	
	public function findAeProductById($productId){
		$data=array(
			'access_token'	=>$this->access_token,
			'productId'		=>$productId,
		);
		$url="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findAeProductById/{$this->appKey}";
		return json_decode($this->Curl($url,$data));
	}

	
	
	/********************************************************
	 *	¶Ô¶ÔÓ¦¶©µ¥±ê¼Ç·¢·Å£¬ Ö§³ÖÈ«²¿·¢»õ£¬ ²¿·Ö·¢»õ
	 *	var	serviceName	ÎïÁ÷·þÎñ¼ò³Æ
	 *	var	logisticsNo	ÎïÁ÷×·×ÙºÅ
	 *	var	sendType	·¢ËÍ·½Ê½£¨all,part£©
	 *	var	outRef		¶ÔÓ¦µÄ¶©µ¥ºÅ
	 */
	public function sellerShipment($serviceName, $logisticsNo, $sendType, $outRef, $description=""){
		$data	=	array(
			'access_token'	=>	$this->access_token,
			'serviceName'	=>	$serviceName,
			'logisticsNo'	=>	$logisticsNo,
			'sendType'		=>	$sendType,
			'outRef'		=>	$outRef
		);
		
		if(!empty($description)){
			$data['description']	=	$description;
		}
		$url	=	"{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.sellerShipment/{$this->appKey}";
		return json_decode($this->Curl($url,$data),true);	

	}


	/***********************************************************
	 *	»ñÈ¡aliexpressÖ§³ÖµÄÎïÁ÷ÐÅÏ¢
	 *
	 */
	 public function listLogisticsService(){
		$data	=	array(
			'access_token'	=>$this->access_token
		); 
		$url	=	"{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.listLogisticsService/{$this->appKey}";
		return json_decode($this->Curl($url,$data),true);	
	 }

}
?>
