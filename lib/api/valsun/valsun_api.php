<?php
class Valsun{

	var $method		=	"";
	var $timestamp	=	"";
	var $format		=	"json";		//返回的数据格式
	var $app_key	=	"";			//商家编码
	var $app_secret	=	"";			//申请的secret key，请不要公开！
	var $version	=	"1.0";		//接口版本
	var $server		=	"http://idc.gw.open.valsun.cn/router/rest?";		//入口URL

	public function __construct() {
	}


	public function setConfig($app_key, $app_secret){
		$this->app_key		=	$app_key;      //分销商需要传入的app_key （必填）
		$this->app_secret	=	$app_secret;   //分销商token (必填)
	}

	/***********************************************
	 *	curl 请求
	 *	@param $url		string	请求的url地址
	 *	@param $vars	array	需要post的数据(key=>val)
	 */
	public function Curl($url, $vars=''){
		$ch	=	curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($vars));
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
		$content	=	curl_exec($ch);
		curl_close($ch);
		return $content;
	}


	/***********************************************
	 *	生成签名
	 *	@param	$paramArr	array
	 *	@return string
	 */
	public function createSign($paramArr) {
	    $str	=	"";
	    ksort($paramArr);
	    foreach ($paramArr as $key => $val) {
	       if ($key !='' && $val !='') {
	           $str	.=	$key.$val;
	       }
	    }
	    $sign	=	strtoupper(md5($str.$this->app_secret));
	    return	$sign;
	}

	/**
	 * 功能： 推送订单接口 老接口
	 * @param string $order [订单数据]
	 * @return  json [推送状态]
	 */
	public function pushOrders($order){
		$strParam	=	"";
		$url		=	$this->server;
		$order_json = json_encode($order);
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.orders.recevice',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'orderArr' => $order_json
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		log::writeLog("url = ".$url,"api/third/valsun","push_order.log","d");
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 查询订单状态接口
	 * @param string $recordnumber [订单号]
	 * @return  json [推送状态]
	 */
	public function getOrdersQueryStatus($recordnumber){
	    $strParam	=	"";
	    $url		=	$this->server;
	    $paramArr= array(
	        /* API系统级输入参数 Start */
	        'method'	=> 'open.orders.query.status', //API名称
	        'format'	=> 'json',  //返回格式
	        'v'			=> '1.0',   //API版本号
	        'app_key'	=> $this->app_key,
	        'protocol'	=> 'param2',
	        'timestamp'	=> date('Y-m-d H:i:s'),
	        /* API系统级参数 End */
	    
	        /* API应用级输入参数 Start*/
	    // 			'category'  => 'all'
	        'recordnumber' => $recordnumber
	    );
	    
	    $sign		=	$this->createSign($paramArr);
	    $strParam	.=	'&sign='.$sign;
	    $url		=	$url.$strParam;
	    $ret		=	$this->Curl($url,$paramArr);
	    return $ret;
	}
	
	/**
	 * 功能： 获取产品主料号信息
	 * @param  [string] $spu [主料号]
	 * @return [json]   [json数组]
	 */
	public function getSpuInfo($spu){
	    $strParam	=	"";
	    $url		=	$this->server;
	    $paramArr= array(
	        /* API系统级输入参数 Start */
	        'method'	=> 'open.base.get.spu.info',  //API名称
	        'format'	=> 'json',  //返回格式
	        'v'			=> '1.0',   //API版本号
	        'app_key'	=> $this->app_key,
	        'protocol'	=> 'param2',
	        'timestamp'	=> date('Y-m-d H:i:s'),
	        /* API系统级参数 End */
	          
	        /* API应用级输入参数 Start*/
	        // 			'category'  => 'all'
	        'spu' => $spu
	    );	     
	    $sign		=	$this->createSign($paramArr);
	    $strParam	.=	'&sign='.$sign;
	    $url		=	$url.$strParam;
	    $ret		=	$this->Curl($url,$paramArr);
	    return $ret;
	}



	//+++++++++++++++++++++++新流程接口++++++++++++++++++++++++++++++


	/**
	 * 功能： 推送订单接口 最新接口
	 * @param string $order [订单数据]
	 * @return  json [推送状态]
	 */
	public function pushOrdersNew($order){
		$strParam	=	"";
		$url		=	$this->server;
		$order_json = json_encode($order);
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.pushOutDistributorOrder',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'orderArr' => $order_json
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 查询订单接口 最新接口
	 * @param array $orderIds [订单号]
	 * @return  json [订单信息]
	 */
	public function getOrdersNew($orderIds){
		$strParam	=	"";
		$url		=	$this->server;
		$orderIds	= json_encode($orderIds);
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.getDistributorOrdersInfo',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'orders' => $orderIds
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 获取国家简码信息
	 * @param string type [类型]
	 * @return  json [订单信息]
	 */
	public function getCountryInfo($type){
		$strParam	=	"";
		$url		=	$this->server;
		$orderIds	= json_encode($orderIds);
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.getCountryCode',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'type' => $type
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 更新产品的状态
	 * @param string type [类型]
	 * @return  json [订单信息]
	 */
	public function getGoodsStatus($startTime,$endTime,$isNew=null){
		$strParam	=	"";
		$url		=	$this->server;
		$orderIds	= json_encode($orderIds);
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.returnProducts',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'startTime' => $startTime,
		    'endTime'   => $endTime,
		);
		if($isNew !== null){
			$paramArr['isNew'] = $isNew;
		}

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 更新产品的基础信息
	 * @param string type [类型]
	 * @return  json [订单信息]
	 */
	public function getGoodsBasicInfo($spu=''){
		$strParam	=	"";
		$url		=	$this->server;
		if(is_array($spu)){
			$spu = implode(",", $spu);
		}
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.base.get.spu.info',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'spu' 		=> $spu,
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 查询订单的详细
	 * @param string type [类型]
	 * @return  json [订单信息]
	 */
	public function getOrderDetails($orders=''){
		$strParam	=	"";
		$url		=	$this->server;
		if(is_array($orders)){
			$orders = json_encode($orders);
		}
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.getDistributorOrdersInfo',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'orders' 		=> $orders,
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}

	/**
	 * 功能： 获取产品的费用信息
	 * @param string type [类型]
	 * @return  json [订单信息]
	 */
	public function getProductsFee($spuArr){
		$strParam	=	"";
		$url		=	$this->server;
		if(is_array($spuArr)){
			$spuArr = json_encode($spuArr);
		}
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'open.hc.returnSpusPrice',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'app_key'	=> $this->app_key,
			'protocol'	=> 'param2',
			'timestamp'	=> date('Y-m-d H:i:s'),
			/* API系统级参数 End */

			/* API应用级输入参数 Start*/
		    'spuArr' 	=> $spuArr,
		);

		$sign		=	$this->createSign($paramArr);
		$strParam	.=	'&sign='.$sign;
		$url		=	$url.$strParam;
		$ret		=	$this->Curl($url,$paramArr);
		return $ret;
	}


}

?>
