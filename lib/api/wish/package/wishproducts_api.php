<?php

include_once WEB_PATH."lib/api/wish/wishSession.php";
class WishProducts extends WishSession{
    public function __construct(){
		parent::__construct();
	}
    
    //获取所有产品信息
	public function listAllProducts($start,$limit,$since){
	    $url = sprintf("https://merchant.wish.com/api/v1/product/multi-get?key=%s&start=%s&limit=%s&since=%s",urlencode($this->key), urlencode($start), urlencode($limit), urlencode($since));
		return $this->wish_file_get_contents($url);
	}

	//创建主产品信息
	public function createProduct($params){
	    $params['key'] = $this->key;
		$url = sprintf("https://merchant.wish.com/api/v1/product/add");
		F('opensys');
		return vita_get_url_content2($url,$params);
	}
	//创建子产品信息
	public function createProductVariation($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/variant/add");
	    F('opensys');
		return vita_get_url_content2($url,$params);
	}
	//修改主产品
	public function updateProduct($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/product/update");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	//修改子产品属性
	public function updateVariant($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/variant/update");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	
	//上架spu系列产品
	public function enableParentSku($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/product/enable");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	//下架spu系列产品
	public function disableParentSku($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/product/disable");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	//上架sku产品
	public function enableSku($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/variant/enable");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	//下架sku产品
	public function disableSku($params){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/variant/disable");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
	//修改产品sku的库存
	public function updateInventory(){
	    $params['key'] = $this->key;
	    $url = sprintf("https://merchant.wish.com/api/v1/variant/update-inventory");
	    F('opensys');
	    return vita_get_url_content2($url,$params);
	}
    
}
?>
