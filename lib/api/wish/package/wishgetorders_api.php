<?php

include_once WEB_PATH."lib/api/wish/wishSession.php";
class WishGetOrders extends WishSession{
    public function __construct(){
		parent::__construct();
	}
    
    //����ʱ�����Ϣ��ȡ����������Ϣ
	public function multiGetOrders($since, $limit=500, $start=''){
	    $url = sprintf("https://merchant.wish.com/api/v1/order/multi-get?key=%s&start=%s&limit=%s&since=%s",urlencode($this->key), urlencode($start), urlencode($limit), urlencode($since));
		return $this->wish_file_get_contents($url);
	}
    
    //����ʱ�����Ϣ��ȡ����������Ϣ
	public function retrieveUnfulfilledOrders($start='', $limit=500){
	    $url = sprintf("https://merchant.wish.com/api/v1/order/get-fulfill?key=%s&start=%s&limit=%s",urlencode($this->key), urlencode($start), urlencode($limit));
		return $this->wish_file_get_contents($url);
	}
	

}
?>
