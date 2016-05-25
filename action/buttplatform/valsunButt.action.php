<?php
/*
* 华城平台对接接口
* add by: zoujunrong @date 20150210
*/
class ValsunButtAct extends CheckAct
{

    private $appKey; //key
    private $appSecret; //账号
    private $valsunObj; //赛维api对象

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($appKey='',$appSecret='')
    {
        $this->valsunObj   = F("valsun.Valsun");
    	$this->appKey      = $appKey;
    	$this->appSecret   = $appSecret;
		$this->valsunObj->setConfig($appKey,$appSecret);
    }
	public function getAppkey(){
		return $this->appKey;
	}

    /**
     * 功能：同步订单状态订单号信息
     * $orderIds   例"33321|45466"
     */
    public function synOrdersStatusAndTrackNumber($orderIds)
    {
        $orderIds = implode("|", $orderIds);
        return $this->valsunObj->getOrdersQueryStatus($orderIds);
    }

    /**
     *  推送订单
     *	@param $order 数组	订单信息
     */
    public function pushOrders($order)
    {
        return $this->valsunObj->pushOrders($order);
    }

    /**
     *  获取国家简码信息
     *  @param $order 数组    获取国家简码
     */
    public function getCountryInfo()
    {
        return $this->valsunObj->getCountryInfo($type="all");
    }

    /**
     *  获取产品料号信息
     *  @param $order 数组    获取更新料号状态记跟新料号信息
     */
    public function getGoodsStatus($startTime,$endTime,$isNew)
    {
        return $this->valsunObj->getGoodsStatus($startTime,$endTime,$isNew);
    }

    /**
     *  获取产品料号的基础信息
     *  @param $order 数组    获取更新料号状态记跟新料号信息
     */
    public function getGoodsBasicInfo($spu)
    {
        $res = $this->valsunObj->getGoodsBasicInfo($spu);
        $res = json_decode($res,true);
        return $res['data'];
    }
    
    /**
     *  获取产品料号的基础信息
     *  @param $order 数组    获取更新料号状态记跟新料号信息
     */
    public function getOrderDetails($orders)
    {
        $res = $this->valsunObj->getOrderDetails($orders);
        $res = json_decode($res,true);
        return $res['data'];
    }

    /**
     *  获取产品价格信息
     *  @param $order 数组    获取更新料号状态记跟新料号信息
     */
    public function getProductsFee($spuArr)
    {
        $res = $this->valsunObj->getProductsFee($spuArr);
        $res = json_decode($res,true);
        return $res['data'];
    }
}
?>