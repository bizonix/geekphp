<?php
/*
 * aliexpress平台对接接口
 * add by: linzhengxiang @date 20140618
 */
class AliexpressButtAct extends CheckAct{  
    
    private $account;
	private $appKey;
    private $appSecret;
    private $refresh_token;
    private $AliexpressObj;

	public function __construct(){
		parent::__construct();
	}

	public function setToken($account,$token){
        $token                = json_decode($token,true);
        $this->account        = $account;
        $this->appKey         = empty($token['appKey']) ? '' : $token['appKey'];
        $this->appSecret      = empty($token['appSecret']) ? '' : $token['appSecret'];
        $this->refresh_token  = empty($token['refreshToken']) ? '' : $token['refreshToken'];
	}

	/**
	 * 抓取处于 'orderStatus'  => 'WAIT_SELLER_SEND_GOODS' 的订单
	 * @return array 订单数组
	 * @author wcx
	 */
	public function findOrderListQuery($orderStatus='WAIT_SELLER_SEND_GOODS',$createDateStart = '', $createDateEnd = '',$page,$pageSize){
		$OrderObject = F('aliexpress.package.AliexpressGetOrders');
		$OrderObject->setConfig($this->account, $this->appKey, $this->appSecret, $this->refresh_token);
        $OrderObject->doInit();
        $orderList = $OrderObject->findOrderListQuery($orderStatus,$createDateStart, $createDateEnd,$page,$pageSize);
		return $orderList;
	}

	public function findOrderById($aliexpres_order){
		$OrderObject = F('aliexpress.package.AliexpressGetOrders');
		$OrderObject->setConfig($this->account, $this->appKey, $this->appSecret, $this->refresh_token);
        $OrderObject->doInit();
        $orderList = $OrderObject->findOrderById($aliexpres_order);
		return $orderList;
	}

    public function listLogisticsService(){
        $OrderObject = F('aliexpress.package.Aliexpress');
        $OrderObject->setConfig($this->account, $this->appKey, $this->appSecret, $this->refresh_token);
        $OrderObject->doInit();
        $list = $OrderObject->listLogisticsService();
        return $list;
    }

    /**
	 * 标记发货 对对应订单标记发放， 支持全部发货， 部分发货
	 *  var serviceName	物流服务简称
	 *  var logisticsNo	物流追踪号
	 *	var	sendType	发送方式（all,part）
	 *	var	outRef		对应的订单号
	 */
	public function sellerShipment($serviceName, $logisticsNo, $sendType, $outRef, $description='',$Website=""){
		$OrderObject = F('aliexpress.package.aliexpressSellerShipment');
		$OrderObject->setConfig($this->account, $this->appKey, $this->appSecret, $this->refresh_token);
        $OrderObject->doInit();
        $ret = $OrderObject->sellerShipment($serviceName, $logisticsNo, $sendType, $outRef, $description,$Website);
        log::writeLog('sellerShipment ret = '.json_encode($ret),'wcx_test/tmp','ret','d');
        if(empty($ret['success'])){
            if($ret['error_code'] == "15-2001"){
                self::$errMsg['30001'] = get_promptmsg("30001");
                return false;
            }elseif($ret['error_code'] == "15-1002"){
                self::$errMsg['30002'] = get_promptmsg("30002");
                return false;
            }elseif($ret['error_code'] == "15-200"){
                self::$errMsg['30003'] = get_promptmsg("30003");
                return false;
            }else{
                self::$errMsg['30000'] = get_promptmsg("30000");
                return false;
            }
        }
		return true;
	}
    
	/**
	 * 根据运输方式获取物流服务简称
	 * @param string ebay_carrier
	 * @return string serivceName
	 */
    public function get_carrier_name($ebay_carrier){
		/* if(in_array($ebay_carrier, array('Hongkong Post Air Mail', 'HK Post Air Mail', 'HKPAM', 'Hongkong Post Airmail', 'HK Post Airmail','HongKong Post Air Mail'))){
			$ebay_carrier		= '香港小包挂号';
		}
		if(in_array($ebay_carrier, array('UPSS', 'UPS Express Saver'))){
			$ebay_carrier		= 'UPS';
		}
		
		if($ebay_carrier   == 'DHL'){
			$ebay_carrier		= 'DHL';
		}
		
		if($ebay_carrier   == 'EMS'){
			$ebay_carrier		= 'EMS';
		}
		
		if(in_array($ebay_carrier, array('ChinaPost Post Air Mail', 'China Post Air Mail', 'CPAM', 'China Post Airmail'))){
			$ebay_carrier		= '中国邮政挂号';
		}
		
		if($ebay_carrier=='ePacket'){
			$ebay_carrier = 'EUB';
		}

		if($ebay_carrier == "Fedex IE"){
			$ebay_carrier = 'FedEx';
		}
		return $ebay_carrier; */
    	switch (strtoupper($ebay_carrier)){
    		case "香港小包挂号":
    			$serviceName		= 'HKPAM';	//Hongkong Post Air Mail
    			break;
    		case "UPS":
    		case "UPS美国专线":
    			$serviceName		= 'UPS';
    			break;
    		case "DHL":
    			$serviceName		= 'DHL';
    			break;
    		case "FEDEX":
    			$serviceName		= 'FEDEX_IE';
    			break;
    		case "TNT":
    			$serviceName		= 'TNT';
    			break;
    		case "EMS":
    			$serviceName		= 'EMS';
    			break;
    		case "中国邮政挂号":
    			$serviceName		= 'CPAM';	//China Post Air Mail
    			break;
    		case "瑞士小包挂号":
    			$serviceName		= 'CHP';	//
    			break;
    		case "俄速通挂号":
    		case "俄速通大包":
    			$serviceName		= 'CPAM_HRB';	//俄速通专线
    			break;
    		case "EUB":
    			$serviceName		= 'EMS_ZX_ZX_US';	//EUB
    			break;
    		case "新加坡小包挂号":
    			$serviceName		= 'SGP';
    			break;
    		case "WEDO":
            case "中国邮政平邮":
    		case "俄速通平邮":
    		case "瑞士小包平邮":
    		case "新加坡DHL GM平邮":
    		case "香港小包平邮":
    			$serviceName		= 'Other';
    			break;
    		default:
    			$serviceName	   = false;
    			break;
    	}
    	return $serviceName;
    }


    public function time_shift($origin_num) { //转换成时间戳
    	$time_offset	=	0;
    	$i	=	0;
    	$i	=	strpos($origin_num,"-");
    	
    	if($i > 0){
    		$temp	=	explode("-", $origin_num);
    		$utc	=	intval(preg_replace("/0/","",$temp[1]));
    		$time_offset	=	time() - 3600*(8+ $utc);	
    	}
    	$i	=	0;
    	$i	=	strpos($origin_num,"+");
    	if($i > 0){
    		$temp	=	explode("+", $origin_num);
    		$utc	=	intval(preg_replace("/0/","",$temp[1]));
    		if($utc > 8){
    			$time_offset	=	time() + 3600*($utc - 8);	
    		}else{
    			$time_offset	=	time() - 3600*(8 - $utc);	
    		}
    	}
    	$time	=	strtotime(substr($origin_num,0,14));
    	return array($time, $time_offset);
    }
    
    //根据平台返回的国家简码返回对应的国家全称，需完成
    public function get_country_name($code){
        return $code;
    }
    public function getAppCode($account,$appKey,$appSecret,$companyId){
        $api = F('aliexpress.package.AliexpressSessions');
        $api->setConfig($account,$appKey,$appSecret,'');
        $api->setCompanyId($companyId);//设置公司id
        $api->getCode();
    }
    public function savToken($company,$account,$code,$appKey,$appSecret){
        $api = F('aliexpress.package.AliexpressSessions');

        $api->setConfig($account,$appKey,$appSecret,'');
        $api->setProtocol('http');
        $token = $api->getToken($code);
        Log::pLog("token",var_export($token,true));
        $token['appKey'] = $appKey;
        $token['appSecret'] = $appSecret;
        $token['refreshToken'] = $token['refresh_token'];
        unset($token['refresh_token']);
        //$ret = M("Shops")->getData("id","shop_account='$account' and belong_company='$company'");
        $ret = A("Shops")->act_getShopInfo($account,2);
        if(empty($ret)){
            // $data = array(
            //     "shop_account"  => $account,
            //     "platform"      => '2',
            //     "belong_company"=> $company,
            //     "token"         => json_encode($token),
            //     "creater"       => "huanhuan",
            //     "update_time"   => time(),
            //     "add_time"      => time(),
            // );
            return A("Shops")->act_addShop($account,2,json_encode($token));
            //return M("Shops")->insertData($data);
        }else{
            //log::write("shop_account='$account' and belong_company='$company' $account=".json_encode($token));
            return M("Shops")->updateData($ret[0]['id'],array("token"=>json_encode($token)));
        }
    }
}
?>