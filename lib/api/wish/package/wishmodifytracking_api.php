<?php

include_once WEB_PATH."lib/api/wish/wishSession.php";
class WishModifyTracking extends WishSession{
    public function __construct(){
		parent::__construct();
	}
    
    //修改跟踪号信息
	public function modifytracking($id,$tracking_provider,$tracking_number,$ship_note=""){
	    $url = sprintf("https://merchant.wish.com/api/v1/order/modify-tracking?key=%s&tracking_provider=%s&tracking_number=%s&id=%s", urlencode($this->key), urlencode($tracking_provider), urlencode($tracking_number), urlencode($id));
		return $this->wish_file_get_contents($url);//post 链接超时
	}
	

}
?>
