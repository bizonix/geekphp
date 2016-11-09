<?php
/**
 * 类名：GroupAct
 * 功能: 团体管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/02/16
 * errCode：
 */
class GroupAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 獲取公司信息 根据公司ID
	 */
	public function act_getGroupInfoById($groupId){
		$GroupInfo = M("Company")->getData("*",array("id" => $groupId));
		if(empty($GroupInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体');
			return false;
		}
		return $GroupInfo[0];
	}

	/**
	 * 獲取公司信息 根据公司中文名称
	 */
	public function act_getGroupInfoByCnName($groupCnName){
		$GroupInfo = M("Company")->getData("*",array("cn_name" => $groupCnName));
		if(empty($GroupInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体');
			return false;
		}
		return $GroupInfo[0];
	}

	/**
	 * 獲取公司信息 根据公司中文名称
	 */
	public function act_addGroup($data){
		$insertData = array();
		if(!empty($data['cn_name'])){
			$insertData['cn_name']	= $data['cn_name'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体中文名');
			return false;
		}
		if(!empty($data['en_name'])){
			$insertData['en_name']	= $data['en_name'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体英文名');
			return false;
		}
		if(!empty($data['short_name'])){
			$insertData['short_name']	= $data['short_name'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体简称');
			return false;
		}
		if(!empty($data['legal_person'])){
			$insertData['legal_person']	= $data['legal_person'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体法人');
			return false;
		}
		if(!empty($data['type'])){
			$insertData['type']	= $data['type'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体类型');
			return false;
		}
		if(!empty($data['address'])){
			$insertData['address']	= $data['address'];
		}else{
			self::$errMsg['10008']	= get_promptmsg('10008','团体地址');
			return false;
		}
		$userName = get_username();
		if($userName){
			$insertData['add_person']	= $userName;
		}else{
			self::$errMsg['10007']	= get_promptmsg('10007','用户');
			return false;
		}
		$companys = M("Company")->getData("*","cn_name='{$insertData['cn_name']}' or en_name='{$insertData['en_name']}'");
		
		if($companys[0]['add_person'] == $userName){
			$updateRet = M("Company")->updateData($companys[0]['id'],$insertData);
			if(empty($updateRet)){
				self::$errMsg['10001']	= get_promptmsg('10001','创建团体');
				return false;
			}
			return true;
		}elseif(count($companys)>0){
			self::$errMsg['10003'] = get_promptmsg(10003,"该公司");
			return false;
		}
		$insertData['add_time']	= $insertData['register_time'] = time();
		M("Company")->begin();
		$insertRet = M("Company")->insertData($insertData);
		if(empty($insertRet)){
			self::$errMsg['10001']	= get_promptmsg('10001','创建团体');
			return false;
		}
		$insertId  = M("Company")->getLastInsertId();
		$res = M("User")->updateData(get_userid(),array("company_id" => $insertId));
		if($res){
			M("Company")->commit();
		}else{
			M("Company")->rollback();
		}
		//添加公司信息至cookie中
		$userInfo = M('User')->getSingleData("*",array("id"=>get_userid()));
		A("Login")->act_registerLoginInfo($userInfo);
		return true;
	}

	/**
	 * 獲取公司信息 根据公司英文名称
	 */
	public function act_getGroupInfoByEnName($groupCnName){
		$GroupInfo = M("Company")->getData("*",array("en_name" => $groupEnName));
		if(empty($GroupInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体');
			return false;
		}
		return $GroupInfo[0];
	}

	/**
	 * 獲取公司信息 根据公司英文名称
	 */
	public function act_getGroupMembers($groupId){
		$where 	  = array("company_id" => $groupId);
		$count	  = M("CompanyMember")->getDataCount($where);
		$p 		  = new Page ($count,10);
		$members = M("CompanyMember")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
		$page 		= $p->fpage();
		if(empty($members)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体成员');
			return false;
		}
		$list = array("members"=>$members,"count"=>$count,"page"=>$page);
		return $list;
	}

	/**
	 * 修改成员状态
	 */
	public function act_updateMembersStatus($memberId,$status){
		if($status > 1){
			$memberInfo = M("User")->getSingleData("company_id",array("id"=>$memberId));
		}else{
			self::$errMsg['10019']	= get_promptmsg(10019);
			return false;
		}
		$adminCompanyId = get_usercompanyid();
		if(empty($adminCompanyId)){
			self::$errMsg['10007']	= get_promptmsg(10007,"公司");
			return false;
		}
		if($status == 2 && empty($memberInfo['company_id'])){
			M("User")->begin();
			$updateFlag = M("User")->updateData($memberId,array("company_id"=>$adminCompanyId));
		}elseif($status == 3 && $adminCompanyId == $memberInfo['company_id']){
			M("User")->begin();
			$updateFlag = M("User")->updateData($memberId,array("company_id"=>0));
		}
		if($updateFlag){
			$memberUpdateFlag = M("CompanyMember")->updateDataWhere(array("member_status"=>$status),array("company_id"=>$adminCompanyId,"member_id"=>$memberId));
			if($memberUpdateFlag){
				M("User")->commit();
				self::$errMsg['200'] = get_promptmsg(200);
				return true;
			}else{
				M("User")->rollback();
				self::$errMsg['10001'] = get_promptmsg(10001,"审核");
				return false;
			}
		}else{
			M("User")->rollback();
			self::$errMsg['10001'] = get_promptmsg(10001,"审核");
			return false;
		}
	}

	/**
	 * 获取公司所有信息
	 */
	public function act_getGroupList(){
		$count	  = M("Company")->getDataCount("1");
		$p 		  = new Page ($count,10);
		$groups   = M("Company")->getData("*","1","order by id desc",$this->page,$this->perpage);
		//获取已申请
		$hasApply = M("CompanyMember")->getAllData("*",array("member_id"=>get_userid()),"company_id");
		$page 	  = $p->fpage();
		if(empty($groups)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体');
			return false;
		}
		$list = array("groups"=>$groups,"count"=>$count,"page"=>$page,"hasApply"=>$hasApply);
		return $list;
	}

	/**
	 * 申请加入团队
	 */
	public function act_applyAddGroup($companyId){
		$memberId = get_userid();
		$memberName = get_username();
		if(empty($memberId) || empty($companyId)){
			self::$errMsg['10008'] = get_promptmsg();
			return false;
		}
		$insertData = array(
			"company_id" => $companyId,
			"member_id"	 => $memberId,
			"member_name"=> $memberName,
			"power"		 => 'all',
			"add_time"	 => time(),
		);
		$insertFlag = M("CompanyMember")->insertData($insertData);
		if($insertFlag){
			self::$errMsg[200] = get_promptmsg(200);
			return true;
		}else{
			self::$errMsg[10007] = get_promptmsg(10007,"申请");
			return false;
		}
		
	}

}
