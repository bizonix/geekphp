<?php
/**
 * 类名：AdminSystemSetAct
 * 功能: 系统设置管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/03/01
 * errCode：
 */
class AdminSystemSetAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**************************************************
	 * 获取系统的运输方式
	 */
	public function act_getSystemTransport(){
		$carriers	= M("Carrier")->getAllData('*','1','carrier_abb',"order by id asc");
		if(empty($carriers)){
			self::$errMsg['10007']	= get_promptmsg('10007','系统运输方式');
			return false;
		}
		return $carriers;
	}
	/**
	 * 新增系统的运输方式
	 */
	public function act_addSystemTransport($insertData){
		if(empty($insertData)){
			self::$errMsg['10007']	= get_promptmsg('10007',"新增");
			return false;
		}
		$addData	= array(
			"type"				=> isset($insertData['type']) ? $insertData['type'] : '',
			"carrier_name_cn"	=> isset($insertData['carrier_name_cn']) ? $insertData['carrier_name_cn'] : '',
			"carrier_name_en"	=> isset($insertData['carrier_name_en']) ? $insertData['carrier_name_en'] : '',
			"is_track"			=> isset($insertData['is_track']) ? $insertData['is_track'] : 0,
			"carrier_ali"		=> isset($insertData['carrier_ali']) ? $insertData['carrier_ali'] : '',
			"carrier_abb"		=> isset($insertData['carrier_abb']) ? $insertData['carrier_abb'] : '',
			"carrier_index"		=> isset($insertData['carrier_index']) ? $insertData['carrier_index'] : '',
			"is_tracknum"		=> isset($insertData['is_tracknum']) ? $insertData['is_tracknum'] : 2,
			"is_shipfee"		=> isset($insertData['is_shipfee']) ? $insertData['is_shipfee'] : 0,
			"update_time"		=> time(),
			"add_time"			=> time(),
		);
		$addRes = M("Carrier")->insertData($addData);
		if($addRes){
			self::$errMsg['200']	= get_promptmsg("200","新增");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","新增");
			return false;
		}
	}

	/**
	 * 更新系统的运输方式
	 */
	public function act_updateSystemTransport($updateData){
		if(empty($updateData)){
			self::$errMsg['10007']	= get_promptmsg('10007',"新增");
			return false;
		}
		if(empty($updateData["sysCarrierId"])){
			self::$errMsg['10019']	= get_promptmsg('10019','系统运输方式ID');
			return false;
		}
		$updateEndData	= array(
			"type"				=> isset($updateData['type']) ? $updateData['type'] : '',
			"carrier_name_cn"	=> isset($updateData['carrier_name_cn']) ? $updateData['carrier_name_cn'] : '',
			"carrier_name_en"	=> isset($updateData['carrier_name_en']) ? $updateData['carrier_name_en'] : '',
			"is_track"			=> isset($updateData['is_track']) ? $updateData['is_track'] : 0,
			"carrier_ali"		=> isset($updateData['carrier_ali']) ? $updateData['carrier_ali'] : '',
			"carrier_abb"		=> isset($updateData['carrier_abb']) ? $updateData['carrier_abb'] : '',
			"carrier_index"		=> isset($updateData['carrier_index']) ? $updateData['carrier_index'] : '',
			"is_tracknum"		=> isset($updateData['is_tracknum']) ? $updateData['is_tracknum'] : 2,
			"is_shipfee"		=> isset($updateData['is_shipfee']) ? $updateData['is_shipfee'] : 0,
			"is_on"				=> isset($updateData['is_on']) ? $updateData['is_on'] : 1,
			"update_time"		=> time(),
		);
		$updateRes = M("Carrier")->updateData($updateData["sysCarrierId"],$updateEndData);
		if($updateRes){
			self::$errMsg['200']	= get_promptmsg("200","修改");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","修改");
			return false;
		}
	}

	/**
	 * 删除系统的运输方式
	 */
	public function act_deleteSystemTransport($carrierId){
		if(empty($carrierId)){
			self::$errMsg['10007']	= get_promptmsg('10007','平台运输方式ID');
			return false;
		}
		$deleteRes = M("Carrier")->deleteData($carrierId);
		if($deleteRes){
			self::$errMsg['200']	= get_promptmsg("200","删除");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","删除");
			return false;
		}
	}

	/***********************************************
	 * 获取平台的运输方式
	 */
	public function act_getPlatFormTransport($platformId=0,$serviceName='',$logisticsCompany=''){
		$where		= '1';
		if($platformId){
			$where .= " and platformId='{$platformId}'";
		}
		if($serviceName){
			$where .= " and serviceName='{$serviceName}'";
		}
		if($logisticsCompany){
			$where .= " and logisticsCompany='{$logisticsCompany}'";
		}
		$count		= M("PlatformCarrier")->getDataCount($where);
		$p 			= new Page ($count,10);
		$carriers	= MC("select pc.*,c.carrier_name_cn from ".C('DB_PREFIX')."platform_carrier pc left join ".C('DB_PREFIX')."carrier c on c.id=pc.carrierId where {$where} and pc.is_delete=0 order by id desc limit ".($this->page-1)*$this->perpage.",{$this->perpage}");
		$platforms	= M("Platform")->getAllData('*','1',"id","order by id asc");
		$page 		= $p->fpage();
		//系统的运输方式
		$sysCarriers = M("Carrier")->getAllData('*','1','carrier_abb',"order by id asc");
		//过滤出来物流服务，物流公司，映射运输方式
		$serviceName		= array();
		$logisticsCompany	= array();
		foreach($carriers as $k=>$v){
			if($v['serviceName'] && !in_array($v['serviceName'],$serviceName)){
				$serviceName[] = $v['serviceName'];
			}
			if($v['logisticsCompany'] && !in_array($v['logisticsCompany'],$logisticsCompany)){
				$logisticsCompany[] = $v['logisticsCompany'];
			}
		}
		
		$retList = array(
			"carriers"			=> $carriers,
			"count"				=> $count,
			"page"				=> $page,
			"platforms"			=> $platforms,
			"serviceName"		=> $serviceName,
			"logisticsCompany"	=> $logisticsCompany,
			"sysCarriers"		=> $sysCarriers,
		);
		return $retList;
	}

	/**
	 * 新增平台的运输方式
	 */
	public function act_addPlatformTransport($platformId,$serviceName,$displayName,$logisticsCompany,$carrierId,$recommendOrder,$recommendOrder,$trackingNoRegex,$minProcessDay,$maxProcessDay){
		$addData = array();
		if(empty($platformId)){
			self::$errMsg['10019']	= get_promptmsg('10019','平台运输方式ID');
			return false;
		}else{
			$addData["platformId"] = $platformId;
		}
		if(!empty($carrierId)){
			$addData["carrierId"] = $carrierId;
		}
		if(!empty($recommendOrder)){
			$addData["recommendOrder"] = $recommendOrder;
		}
		if(!empty($trackingNoRegex)){
			$addData["trackingNoRegex"] = $trackingNoRegex;
		}
		if(!empty($logisticsCompany)){
			$addData["logisticsCompany"] = $logisticsCompany;
		}
		if(!empty($minProcessDay)){
			$addData["minProcessDay"] = $minProcessDay;
		}
		if(!empty($maxProcessDay)){
			$addData["maxProcessDay"] = $maxProcessDay;
		}
		if(!empty($serviceName)){
			$addData["serviceName"] = $serviceName;
		}
		if(!empty($displayName)){
			$addData["displayName"] = $displayName;
		}
		if(empty($addData)){
			self::$errMsg['10007']	= get_promptmsg('10007',"新增");
			return false;
		}
		$addData["update_time"] = time();
		$addData["add_time"]	= time();

		$addRes = M("PlatformCarrier")->insertData($addData);
		if($addRes){
			self::$errMsg['200']	= get_promptmsg("200","新增");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","新增");
			return false;
		}
	}

	/**
	 * 更新平台的运输方式
	 */
	public function act_updatePlatformTransport($sysCarrierId,$platformId,$serviceName,$displayName,$logisticsCompany,$carrierId,$recommendOrder,$recommendOrder,$trackingNoRegex,$minProcessDay,$maxProcessDay){
		if(empty($sysCarrierId)){
			self::$errMsg['10007']	= get_promptmsg('10007','平台运输方式ID');
			return false;
		}
		$updateData = array();
		if(!empty($platformId)){
			$updateData["platformId"] = $platformId;
		}
		if(!empty($carrierId)){
			$updateData["carrierId"] = $carrierId;
		}
		if(!empty($recommendOrder)){
			$updateData["recommendOrder"] = $recommendOrder;
		}
		if(!empty($trackingNoRegex)){
			$updateData["trackingNoRegex"] = $trackingNoRegex;
		}
		if(!empty($logisticsCompany)){
			$updateData["logisticsCompany"] = $logisticsCompany;
		}
		if(!empty($minProcessDay)){
			$updateData["minProcessDay"] = $minProcessDay;
		}
		if(!empty($maxProcessDay)){
			$updateData["maxProcessDay"] = $maxProcessDay;
		}
		if(!empty($serviceName)){
			$updateData["serviceName"] = $serviceName;
		}
		if(!empty($displayName)){
			$updateData["displayName"] = $displayName;
		}
		if(empty($updateData)){
			self::$errMsg['20013']	= get_promptmsg('20013');
			return false;
		}
		$updateData["update_time"] = time();
		$upateRes = M("PlatformCarrier")->updateData($sysCarrierId,$updateData);
		if($upateRes){
			self::$errMsg['200']	= get_promptmsg("200","修改");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","修改");
			return false;
		}
	}

	/**
	 * 删除平台的运输方式
	 */
	public function act_deletePlatformTransport($sysCarrierId){
		if(empty($sysCarrierId)){
			self::$errMsg['10007']	= get_promptmsg('10007','平台运输方式ID');
			return false;
		}
		$deleteRes = M("PlatformCarrier")->deleteData($sysCarrierId);
		if($deleteRes){
			self::$errMsg['200']	= get_promptmsg("200","删除");
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg("10001","删除");
			return false;
		}
	}

}
