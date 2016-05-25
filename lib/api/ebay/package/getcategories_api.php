<?php
/*
 * 抓取订单的某个属性
 * @add lzx, date 20140612
 */
include_once WEB_PATH."lib/api/ebay/eBaySession.php";
class GetCategories extends eBaySession{
	
	protected $verb = 'GetCategories';
	
	public function __construct(){
		parent::__construct();
	}
	public function GetCategories($data){

		$this->setSiteId($data['siteId']);
		// if(isset($data['pid'])){
		// 	$this->setCategoryParent($data['pid']);
		// }
		$this->setDetailLevel('ReturnAll');
		
		return $this->sendHttpRequest();
	}
}
?>