<?php
/**
 * AdminSystemSetView
 * 功能：用于系统设置
 * @author 邹军荣
 * v 1.0
 * 2015/03/01  
 */
class AdminSystemSetView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}

	/*****************************************
	 * 系统运输方式
	 */
	public function view_systemTransport() {
		$carriers = A("AdminSystemSet")->act_getSystemTransport();
		$this->smarty->assign("carriers",$carriers);
		$this->smarty->display("admin/system/transport/systemTransport.html");
	}

	/*
	 * 新增平台运输方式
	 */
	public function view_addSystemTransport() {
		unset($_REQUEST["mod"]);
		unset($_REQUEST["act"]);
		$insertData			= $_REQUEST;
		$res = A("AdminSystemSet")->act_addSystemTransport($insertData);
		$this->ajaxReturn($res);
	}

	/**
	 * 更新系统运输方式
	 */
	public function view_updateSystemTransport() {
		unset($_REQUEST["mod"]);
		unset($_REQUEST["act"]);
		$updateData			= $_REQUEST;
		$res = A("AdminSystemSet")->act_updateSystemTransport($updateData);
		$this->ajaxReturn($res);
	}

	/**
	 * 删除系统运输方式
	 */
	public function view_deleteSystemTransport() {
		$carrierId		= $_REQUEST['carrierId'];
		$res = A("AdminSystemSet")->act_deleteSystemTransport($carrierId);
		$this->ajaxReturn($res);
	}

	/*****************************************
	 * 平台运输方式
	 */
	public function view_platformTransport() {
		$platformId			= $_REQUEST['platformId'];
		$serviceName		= $_REQUEST['serviceName'];
		$logisticsCompany	= $_REQUEST['logisticsCompany'];
		$list = A("AdminSystemSet")->act_getPlatFormTransport($platformId,$serviceName,$logisticsCompany);
		$this->smarty->assign($list);
		$this->smarty->display("admin/system/transport/platformTransport.html");
	}

	/*
	 * 新增平台运输方式
	 */
	public function view_addPlatformTransport() {
		$platformId			= $_REQUEST['platformId'];
		$serviceName		= $_REQUEST['serviceName'];
		$displayName		= $_REQUEST['displayName'];
		$logisticsCompany	= $_REQUEST['logisticsCompany'];
		$carrierId			= $_REQUEST['carrierId'];
		$recommendOrder		= $_REQUEST['recommendOrder'];
		$trackingNoRegex	= $_REQUEST['trackingNoRegex'];
		$minProcessDay		= $_REQUEST['minProcessDay'];
		$maxProcessDay		= $_REQUEST['maxProcessDay'];
		$res = A("AdminSystemSet")->act_addPlatformTransport($platformId,$serviceName,$displayName,$logisticsCompany,$carrierId,$recommendOrder,$recommendOrder,$trackingNoRegex,$minProcessDay,$maxProcessDay);
		$this->ajaxReturn($res);
	}

	/**
	 * 更新平台运输方式
	 */
	public function view_updatePlatformTransport() {
		$sysCarrierId		= $_REQUEST['sysCarrierId'];
		$platformId			= $_REQUEST['platformId'];
		$serviceName		= $_REQUEST['serviceName'];
		$displayName		= $_REQUEST['displayName'];
		$logisticsCompany	= $_REQUEST['logisticsCompany'];
		$carrierId			= $_REQUEST['carrierId'];
		$recommendOrder		= $_REQUEST['recommendOrder'];
		$trackingNoRegex	= $_REQUEST['trackingNoRegex'];
		$minProcessDay		= $_REQUEST['minProcessDay'];
		$maxProcessDay		= $_REQUEST['maxProcessDay'];
		$res = A("AdminSystemSet")->act_updatePlatformTransport($sysCarrierId,$platformId,$serviceName,$displayName,$logisticsCompany,$carrierId,$recommendOrder,$recommendOrder,$trackingNoRegex,$minProcessDay,$maxProcessDay);
		$this->ajaxReturn($res);
	}

	/**
	 * 删除平台运输方式
	 */
	public function view_deletePlatformTransport() {
		$sysCarrierId		= $_REQUEST['sysCarrierId'];
		$res = A("AdminSystemSet")->act_deletePlatformTransport($sysCarrierId);
		$this->ajaxReturn($res);
	}

}