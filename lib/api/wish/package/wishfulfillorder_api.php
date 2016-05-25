<?php

include_once WEB_PATH."lib/api/wish/wishSession.php";
class WishFullFillOrder extends WishSession{
    public function __construct(){
		parent::__construct();
	}
    
    //上传跟踪号
	public function fulFillOrders($id, $tracking_provider, $tracking_number){
	    $url = sprintf("https://merchant.wish.com/api/v1/order/fulfill-one?key=%s&tracking_provider=%s&tracking_number=%s&id=%s", urlencode($this->key), urlencode($tracking_provider), urlencode($tracking_number), urlencode($id));
		return $this->wish_file_get_contents($url, 'POST');//必须为post
	}
	

}
?>
