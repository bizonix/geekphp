<?php
/*
 *从开放系统获取信息类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePicsModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 转发图片至图片系统分销商文件夹下
	 * @param json imgInfor
	 * @return int
	 * @author zjr
	 */
	public function saveDistributorWaterMark($imgInfor){
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['imgInfor'] = $imgInfor;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
}
?>