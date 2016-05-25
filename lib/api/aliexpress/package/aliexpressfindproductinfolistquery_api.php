<?php
/**
 * 亚马逊抓单类-获取商品信息
 */
include_once WEB_PATH."lib/api/aliexpress/aliexpressSession.php";
class AliexpressFindProductInfoListQuery extends AliexpressSession{
    /**********************获取商品信息**********************/
    public function __construct(){
		parent::__construct();
	}
    
	public function findProductInfoListQuery(){
		$data = array(
        			'access_token'	=>$this->access_token,
        			'page'			=>'1',
        			'pageSize'		=>'100',
        			'productStatusType'	=>'onSelling',
        		);
		$url = "{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findProductInfoListQuery/{$this->appKey}";
		$List = json_decode($this->Curl($url,$data));
		$ProductList = '';
		if(!empty($List->aeopAEProductDisplayDTOList)){
			foreach($List->aeopAEProductDisplayDTOList as $k=>$v){
				$ProductList[] = $this->findAeProductById($v->productId);
			}			
			for($i=2;$i<=$List->totalPage;$i++){
				$data['page'] = $i;
				$List = json_decode($this->Curl($url,$data));
				foreach($List->aeopAEProductDisplayDTOList as $k=>$v){
					$ProductList[] = $this->findAeProductById($v->productId);
				}
			}			
		}
		return $ProductList;
	}
        
    public function findAeProductById($productId){
		$data=array(
			'access_token' => $this->access_token,
			'productId'	   => $productId,
		);
		$url = "{$this->server}/{$this->rootpath}/{$this->protocol}/{$this->version}/{$this->ns}/api.findAeProductById/{$this->appKey}";
		return json_decode($this->Curl($url,$data));
	}        
}
?>
