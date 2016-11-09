<?php
/**
 * 类名：TrackAct
 * 功能：跟踪号
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/01/04
 */
class TrackAct extends CheckAct {
	
	public function __construct(){
		parent::__construct();
		C(include WEB_PATH.'conf/trans_conf.php');
	}
	//获取地址列表信息
	public function act_getTrackings($trackNumber) {

		if(empty($trackNumber)){
			self::$errMsg[10008] = get_promptmsg(10008,"跟踪号");
			return false;
		}

		session_start();

		if(time()-$_SESSION['time']<=1){
			self::$errMsg[10008] = get_promptmsg(10008,"调用频繁！");
			return false;
		}
		$_SESSION['time'] = time();
		$_SESSION[$trackNumber.'_weight'] = isset($_SESSION[$trackNumber.'_weight']) ? $_SESSION[$trackNumber.'_weight'] : sprintf("%.4f", 1/rand(2,9));

		$trackInfo = M("TrackAdmin")->getSingleData("*","track_number='{$trackNumber}'");
		if(empty($trackInfo)){
			return false;
		}
		$orderMain = M("Order")->getSingleData("simple_detail,create_time","id={$trackInfo['order_id']}");
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$orderMain['create_time']));
		$orderInfo = M("OrderDetails")->getSingleData("receiptAddress","id={$trackInfo['order_id']}");
		$simpleDetail	 = json_decode($orderMain["simple_detail"],true);
		$receiveInfo = json_decode($orderInfo['receiptAddress'],true);
		$countryInfo = M("Country")->getSingleData("countryNameEn","worldWideSn='{$receiveInfo["country"]}'");
		$receiveInfo["country"] = $countryInfo['countryNameEn'];
		$receiveInfo["deliverCountryTime"] = 0;
		$receiveInfo['desinationCountryTime'] = 0;
		//分配订单的重量
		$goodsWeight = 0;
		if(!empty($simpleDetail["orderFee"]["skuNumPrice"])){
			foreach($simpleDetail["orderFee"]["skuNumPrice"] as $k=>$v){
				$goodsWeight += $v["goodWeight"]+$v["packageWeight"];
			}
		}
		if(empty($goodsWeight)){
			$goodsWeight = $_SESSION[$trackNumber.'_weight'];
		}
		$receiveInfo["weight"]  = $goodsWeight;

		$trackDetailsList = array(array($trackInfo['table_suffix'],'SHENZHEN OF CHINA','Waiting for the distribution'));

		if((time()-$trackInfo['table_suffix']) > 3600*24*1){
			$trackDetailsList[] = array($trackInfo['table_suffix']+3600*2+6,'SHENZHEN OF CHINA','Awaiting packaging');
			$trackDetailsList[] = array($trackInfo['table_suffix']+3600*5-14,'SHENZHEN OF CHINA','Awaiting packaging');
			$trackDetailsList[] = array($trackInfo['table_suffix']+3600*8+35,'SHENZHEN OF CHINA','Departure Scan');
			$receiveInfo["deliverCountryTime"] = 3600*2+6+3600*5-14+3600*8+35;
			//5天后到达广州海关
			if(time() > ($trackInfo['table_suffix']+3600*24*5)){
				$trackDetailsList[] = array($trackInfo['table_suffix']+3600*24*5+24,'GAUNGZHOU OF CHINA','Arrived china custom');
				$receiveInfo["deliverCountryTime"] += 3600*24*5-240;
			}
			if(time() > ($trackInfo['table_suffix']+3600*24*15) && !empty($countryInfo['countryNameEn'])){
				$trackDetailsList[] = array($trackInfo['table_suffix']+3600*24*15+240,'GAUNGZHOU OF CHINA',"Sendding to {$countryInfo['countryNameEn']}");
				$receiveInfo["deliverCountryTime"] += 3600*24*10+20;
			}
			//25天后到达对方海关
			if(time() > ($trackInfo['table_suffix']+3600*24*25) && !empty($countryInfo['countryNameEn'])){
				$trackDetailsList[] = array($trackInfo['table_suffix']+3600*24*25-6,$countryInfo['countryNameEn'],"Arrived {$countryInfo['countryNameEn']} custom");
				$receiveInfo["desinationCountryTime"] += 3600*24*5-6;
			}
			//40天后到达城市
			if(time() > ($trackInfo['table_suffix']+3600*24*40) && !empty($countryInfo['countryNameEn'])){
				$trackDetailsList[] = array($trackInfo['table_suffix']+3600*24*40+9,"{$receiveInfo['province']} of {$receiveInfo['city']}","Sendding to {$receiveInfo['city']}");
				$receiveInfo["desinationCountryTime"] += 3600*24*15-90;
			}
			//50天后到达城市
			if(time() > ($trackInfo['table_suffix']+3600*24*50) && !empty($countryInfo['countryNameEn'])){
				$trackDetailsList[] = array($trackInfo['table_suffix']+3600*24*50+9,"{$receiveInfo['province']} of {$receiveInfo['city']}","Arrived {$receiveInfo['city']}");
				$receiveInfo["desinationCountryTime"] += 3600*24*10-90;
			}
		}else{
			$trackDetailsList[] = array($trackInfo['add_time'],'SHENZHEN OF CHINA','Order entry processing center');
			if(time() > ($trackInfo['add_time']+3600*5)){
				$trackDetailsList[] = array($trackInfo['add_time']+3600*5+6,'SHENZHEN OF CHINA','Awaiting packaging');
				$receiveInfo["deliverCountryTime"] = 3600*5+6;
			}
			if(time() > ($trackInfo['add_time']+3600*8)){
				$trackDetailsList[] = array($trackInfo['add_time']+3600*8-9,'SHENZHEN OF CHINA','Departure Scan');
				$receiveInfo["deliverCountryTime"] =  3600*8-9;
			}
		}

		return array("trackDetail" => $trackDetailsList, "trackInfo" => $receiveInfo);


	}

	/**
	 * 获取费用
	 * wcx
	 */
	public function act_getChangesFee($country,$shippingType,$channel=0){
		$pre = C('TRANS_DB_NAME').'.'.C('TRANS_TB_PRE');
		if($channel){
			$chann = " and chann.id = {$channel} ";
		}else{
			$chann = " and is_default = 1 ";
		}
		try{
			$chann = MC("select chann.carrierId,chann.channelAlias from {$pre}carrier carr left join {$pre}channels chann on carr.id = chann.carrierId where carr.carrierAbb = '{$shippingType}' and chann.is_delete=0 {$chann}");
			$ship = array();
			if(!empty($chann)){
				$ship = MC("select country.`countryNameEn`,ship.unitPrice,ship.handlefee from {$pre}countries_standard country left join {$pre}freight_{$chann[0]['channelAlias']} ship on ship.countries like CONCAT('%',country.`countryNameEn`,'%') where country.countrySn = '{$country}'");
			}
			return $ship[0];

		}catch(Exception $e){
			self::$errMsg[10020] = get_promptmsg(10020,json_encode($e->getMessage()));
			return false;
		}

	}

}