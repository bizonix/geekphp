<?php
/**
 * ����ѷץ����-ץ����
 */
include_once WEB_PATH."lib/api/aliexpress/aliexpressSession.php";
class AliexpressGetOrders extends AliexpressSession{
    public function __construct(){
		parent::__construct();
	}
	
	/**********************查询订单详细信息**********************/
	public function findOrderListQuery($orderStatus='WAIT_SELLER_SEND_GOODS',$createDateStart = '', $createDateEnd = '',$page='1',$pageSize='50'){
		$apiName	= 'api.findOrderListQuery';
		$data	=	array(
			'access_token'	=>$this->access_token,
			'page'			=>1,
			'pageSize'		=>$pageSize,
		);
		if($orderStatus && $orderStatus != "ALL"){
			$data['orderStatus'] = $orderStatus;
		}
		if($createDateStart){
			$data['createDateStart'] = $createDateStart;
		}
		if($createDateEnd){
			$data['createDateEnd'] = $createDateEnd;
		}
		$url = $this->getUrl($apiName).$this->apiSign($apiName,$data);	
        $List		= json_decode($this->Curl($url,$data),true);
		$orderList	= array();
		if(!empty($List["orderList"])){
			foreach($List["orderList"] as $k=>$v){
				$orderId = strval($v["orderId"]);
				$orderList[$orderId]['detail'] = $this->findOrderById($orderId);
				$orderList[$orderId]['v']	   = $v;
			}			
			for($i=2;$i<=ceil($List["totalItem"]/$data['pageSize']);$i++){
				$data['page'] = $i;
				$List = json_decode($this->Curl($url,$data),true);
				foreach($List["orderList"] as $k=>$v){
					$orderId = strval($v["orderId"]);
					$orderList[$orderId]['detail'] = $this->findOrderById($orderId);
					$orderList[$orderId]['v']	   = $v;
				}
			}			
		}
		unset($List);
		return $orderList;		
	}
	
    /**
     * 查询订单详细信息，通过ID查询
     */
	public function findOrderById($orderId){
		$apiName	= 'api.findOrderById';
		$data = array(
			  'access_token' => $this->access_token,
			  'orderId'		 => $orderId
		);
		$url = $this->getUrl($apiName).$this->apiSign($apiName,$data);
		return json_decode($this->Curl($url,$data),true);
	}
}
?>
