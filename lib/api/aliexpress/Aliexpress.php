<?php
/**
*类名：Aliexpress
*功能：速卖通API
*作者：冯赛明
*时间：
*版本：V1
*修改人：冯赛明
*修改时间：2013-7-31
*/
include_once('AliKeys.php');
class Aliexpress{
	var $server			=	'https://gw.api.alibaba.com';
	var $rootpath		=	'openapi';					//openapi,fileapi
	var $protocol		=	'param2';					
	var $ns				=	'aliexpress.open';//namespace
	var $version		=	1;   //版本
	var $appKey			=	'';					//
	var $appSecret		=	'';				//
	var $refresh_token	=	"";//
	var $callback_url	=	"http://202.103.191.209:88/aliexpress/callback.php";

	var $access_token ;

	function __construct() 
	{
	}
	
	/*
	*功能：通过账号获取appkey、token、refresh_token
	*说明：
	*参数1： $account   string  速卖通账户
	*/
	public function setConfig($account)
	{
		global $aliKeys;
		if(!isset($aliKeys[$account]['appKey'])) {
			return false;
		}
		$this->appKey		=	$aliKeys[$account]['appKey'];
		$this->appSecret	=	$aliKeys[$account]['appSecret'];
		$this->refresh_token=	$aliKeys[$account]['refresh_token'];
		return true;
	}


	function doInit(){
		$token	=	$this->getToken();
		$this->access_token	=	$token->access_token;
	}

	function curl($url,$vars, $type=1){//echo http_build_query($vars); 
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
		//curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($vars));
		if($type == 1){
			curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($vars));
		}else{
			curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		}
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
		$content=curl_exec($ch);
		$xx	=	curl_error($ch);
		//var_dump($xx);
		curl_close($ch);
		return $content;
	}
	//生成签名
	public function sign($vars){
		$str='';
		ksort($vars);
		foreach($vars as $k=>$v){
			$str.=$k.$v;
		}
		return strtoupper(bin2hex(hash_hmac('sha1',$str,$this->appSecret,true)));
	}
	
    //
	function getCode(){
		$getCodeUrl = $this->server .'/auth/authorize.htm?client_id='.$this->appKey .'&site=aliexpress&redirect_uri='.$this->callback_url.'&_aop_signature='.$this->sign(array('client_id' => $this->appKey,'redirect_uri' =>$this->callback_url,'site' => 'aliexpress'));
		return '<a href="javascript:void(0)" onclick="window.open(\''.$getCodeUrl.'\',\'child\',\'width=500,height=380\');">请先登陆并授权</a>';
	}
	
	//获取授权
	function getToken(){
		if(!empty($this->refresh_token)){
			$getTokenUrl="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/refreshToken/{$this->appKey}";
			$data =array(
				'grant_type'		=>'refresh_token',		//授权类型
				'client_id'			=>$this->appKey,				//app唯一标示
				'client_secret'		=>$this->appSecret,			//app密钥
				'refresh_token'		=>$this->refresh_token,		//app入口地址
			);
			$data['_aop_signature']=$this->sign($data); 
			return json_decode($this->curl($getTokenUrl,$data));
			
		}else{
			$getTokenUrl="{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/system.oauth2/getToken/{$this->appKey}";
			$data =array(
				'grant_type'		=>'authorization_code',	//授权类型
				'need_refresh_token'=>'true',				//是否需要返回长效token
				'client_id'			=>$this->appKey,				//app唯一标示
				'client_secret'		=>$this->appSecret,			//app密钥
				'redirect_uri'		=>$this->redirectUrl,			//app入口地址
				'code'				=>$_SESSION['code'],	//bug
			);
			return json_decode($this->curl($getTokenUrl,$data));
		}
	}
	
	/*
	*功能：运费计算
	*说明：$length; 	//int 	是 	长 		
		 $width; 	//int 	是 	宽 		
		 $height; 	//int 	是 	高 		
		 $weight; 	//double 	是 	毛重 		
		 $count; 	//int 	是 	数量 		
		 $country; 	//String 	是 	country 		
		 $isCustomPackWeight; 	//boolean 	否 	是否为自定义打包计重,Y/N 	Y 	
		 $packBaseUnit; 	//int 	否 	打包计重几件以内按单个产品计重,当isCustomPackWeight=Y时必选 		
		 $packAddUnit; 	//int 	否 	打包计重超过部分每增加件数,当isCustomPackWeight=Y时必选 		
		 $packAddWeight; 	//double 	否 	打包计重超过部分续重,当isCustomPackWeight=Y时必选 		
		 $freightTemplateId; 	//int 	是 	运费模板ID 必选	
	*/
	public function calculateFreight($length,$width,$height,$weight,$count,$country,$isCustomPackWeight,$packBaseUnit,$packAddUnit,$packAddWeight,$freightTemplateId)
	{
		$apiName='api.calculateFreight';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token'	     => $this->access_token,
			'length'             => $length,
			'width'              => $width,
			'height'             => $height,
			'weight'             => $weight,
			'count'              => $count,
			'country'            => $country,
			'isCustomPackWeight' => $isCustomPackWeight,
			'packBaseUnit'       => $packBaseUnit,
			'packAddUnit'        => $packAddUnit,
			'packAddWeight'      => $packAddWeight,
			'freightTemplateId'  => $freightTemplateId,				
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：列出用户的运费模板
	*/
	public function listFreightTemplate()
	{	
		$apiName='api.listFreightTemplate';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token'	     => $this->access_token,						
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：通过模板ID获取运费模板的详细信息
	*说明：名称 	        类型 	  是否必须 	描述 	示例值    	默认值
         templateId 	Integer 	是 	   模板id 	100170741
	*/
	public function getFreightSettingByTemplateQuery($templateId)
	{	
		$apiName='api.getFreightSettingByTemplateQuery';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'templateId'   => $templateId, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：服务模板查询API 
	*说明：名称 	         类型 	是否必须 	  描述 	           示例值 	默认值
          templateId 	 Long 	 是 	     输入服务模板编号。     -1
	                                     注：输入为-1时，
									     获取所有服务模板列表。
	*/
	public function queryPromiseTemplateById($templateId = -1)
	{	
		$apiName='api.queryPromiseTemplateById';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'templateId'   => $templateId, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}	
	
	/*
	*功能：获取类目属性接口  
	*说明：名称    	类型 	是否必须 	描述 	示例值 	默认值
         cateId 	int 	  是 	
	*/
	public function getAttributesResultByCateId($cateId = 0)
	{	
		$apiName='api.queryPromiseTemplateById';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'cateId'       => $cateId, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：获取单个类目信息  
	*说明：名称    	类型 	是否必须 	描述 	示例值 	默认值
         cateId 	int 	  是 	
	*/
	public function getPostCategoryById($cateId = 0)
	{	
		$apiName='api.getPostCategoryById';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'cateId'       => $cateId, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：获取下级类目信息,同获取单个类目信息内容相同（cateId=0获得一级类目列表）
	*说明：名称    	类型 	是否必须 	描述 	示例值 	默认值
         cateId 	int 	  是 	                类目ID
	*/
	public function getChildrenPostCategoryById($cateId = 0)
	{	
		$apiName='api.getChildrenPostCategoryById';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'cateId'       => $cateId, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}

	/*
	*功能：获取产品分组 
	*说明：名称 	类型 	是否必须 	描述 	示例值 	默认值
          page 	int 	是 	    页码 	  1      1
	*/
	public function getWsProductGroup($page = 1)
	{	
		$apiName='api.getWsProductGroup';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token' => $this->access_token,
			'page'       => $page, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	
	/*
	*功能：商品列表查询接口（主帐号可以查询自己公司下商品，子帐号只查到自己商品。注意：当订单状态在等待发货（ WAIT_SELLER_SEND_GOODS ）时，可能包括两种情况：一是买家已付款，并且资金已到帐；二是买家已付款，但资金未到帐。这两种订单目前混在一起为等待卖家发货（ WAIT_SELLER_SEND_GOODS ），请在资金到帐后（一般为买家付款后24小时内）再进行实际发货，否则您的货物可能会出现损失（与目前后台逻辑一致）。判断逻辑为：买家付款时间+24小时内的订单，均有可能资金未到帐；24小时以上的订单可放心发货。速卖通平台已经着手区分这两种状态，区分完成后，将增加一种状态来区分资金未到帐和等待发货，预计4月底完成，届时将同步更新文档，请大家注意。）
	*说明：应用级输入参数
名称 	           类型 	  是否必须 	描述 	示例值 	默认值
productStatusType  String 	是 	商品业务状态，目前提供4种，输入参数分别是：上架:onSelling ；下架:offline ；审核中:auditing ；审核不通过:editingRequired。 	onSelling 	
subject 	       String 	否 	商品标题模糊搜索字段。只支持半角英数字，长度不超过128。 		
groupId 	       Integer 	否 	商品分组搜索字段。输入商品分组id(groupId). 		
wsDisplay 	       String 	否 	商品下架原因搜索字段。expire_offline：过期下架；user_offline：用户下架；violate_offline：违规下架。 		
offLineTime 	   Integer 	否 	到期时间搜索字段。商品到期时间，输入值小于等于30，单位天。相当查询与现在时+offLineTime天数之内的商品。 	7 	
productId 	       Integer 	否 	商品id搜索字段。输入所需查询的商品id。 		
pageSize 	       Integer 	否 	每页查询商品数量。输入值小于100，默认20。 		
currentPage 	   Integer 	否 	需要商品的当前页数。默认第一页。 		
ownerMemberId 	   String 	否 	商品所有人搜索字段。必须是输入所需查询的商品所有人loginId，切当前用户是所有人或所有人上级用户。
	*/
	public function findProductInfoListQuery($productStatusType)
	{	
		$apiName='api.findProductInfoListQuery';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token'      => $this->access_token,
			'productStatusType' => $productStatusType, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}
	/*
	*功能：上架产品
	*参数：productIds 商品ID
	*/
	public function onlineAeProduct($productIds)
	{
		$apiName='api.onlineAeProduct';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token'      => $this->access_token,
			'productIds' => $productIds, 					
		);		
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;		
		return $this->curl($apiInfo,$code_arr);
	}

	/*
	*功能：下架产品
	*参数：productIds 商品ID
	*/

	public function offlineAeProduct($productIds)
	{
		$apiName='api.offlineAeProduct';//api名称
		$this->doInit();
		$code_arr=array(
		    'access_token'      => $this->access_token,
			'productIds' => $productIds,
		);
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;
		return $this->curl($apiInfo,$code_arr);
	}

	/**
	 * 拉取产品类别接口
	 */
	public function getCategoryList($cateId="0")
	{
		set_time_limit(0);
		$apiName = 'api.getChildrenPostCategoryById';
		$this->doInit();
		$code_arr=array(
		    'access_token'		=> $this->access_token,
			'cateId'			=> $cateId,
		);
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;
		return $this->curl($apiInfo,$code_arr);
	}

	/**
	 * 刊登产品接口
	 */
	public function postAeProduct($data) {
		$apiName = 'api.postAeProduct';
		$this->doInit();
		$data['access_token']	= $this->access_token;
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;
		return $this->curl($apiInfo,$data);
	}

	/**
	 * 根据productId获取单个产品的信息
	 * @para productId as int
	 * return json
	 */
	public function findAeProductById($productId) {
		$apiName = 'api.findAeProductById';
		$this->doInit();
		$code_arr=array(
		    'access_token'		=> $this->access_token,
			'productId'			=> $productId,
		);
		$apiInfo = $this->server.'/'.$this->rootpath.'/'.$this->protocol.'/'.$this->version.'/'.$this->ns.'/'.$apiName.'/'.$this->appKey;
		return $this->curl($apiInfo,$code_arr);
	}
}

?>