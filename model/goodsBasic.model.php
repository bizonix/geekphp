<?php
/**
 * 类名：GoodsBasicModel
 * 功能：产品基础信息管理
 * 版本：V1.0
 * 作者：邹军荣
 * 时间：2015-05-28
 */
class GoodsBasicModel extends CommonModel{

	public function __construct(){
		parent::__construct();
	}

	/**
	 * variants format
	 * add by zjr
	 */
	public function buildVariants($params){
		$variants = array();
		if(!empty($params)){
			foreach ($params as $key => $value) {
				$variant = array(
					'sku' 		=> isset($params['sku']) && !empty($params['sku']) ? $params['sku'] : '',
					'itemPrice' => isset($params['itemPrice']) && !empty($params['itemPrice']) ? $params['itemPrice'] : '0.00',
					'inventory' => isset($params['inventory']) && !empty($params['inventory']) ? $params['inventory'] : '0',
					'color' 	=> isset($params['color']) && !empty($params['color']) ? $params['color'] : '',
					'size' 		=> isset($params['size']) && !empty($params['size']) ? $params['size'] : '',
					//在此定义新增属性
				);
				$variants[] = $variant;
			}
		}
		return $variants;
	}

	/**
	 * main_image format
	 * $params = array('SV002123_B.jpg','23334_M.jpg');
	 * add by zjr
	 */
	public function buildMainImages($params){
	}

	/**
	 * extra_images format
	 * $params = array('SV002123_B-1.jpg','SV002123_B-2.jpg','23334_M-1.jpg');
	 * add by zjr
	 */
	public function buildExtraImages($params){
	}


	public function getAliexProduct($url){
		$html = $this->curl($url);
		/**商品ID**/
		preg_match('|window\.runParams\.productId="(.*?)";|', $html, $matches);
		$productId = $matches[1];
		/**描述**/
		$getDesDomain = 'http://it.aliexpress.com/getSubsiteDescModuleAjax.htm?productId='.$productId.'&t=';
		$desHtml = $this->curl($getDesDomain);
		preg_match('|window\.productDescription=\'(.*)\';|', $desHtml, $matches);
		$description = $matches[1];
		/**标题**/
		preg_match('|<h1\s*class="product-name"\s*itemprop="name">(.*?)<\/h1>|', $html, $matches);
		$title = $matches[1];
		/**店铺**/
		preg_match('|<a\s*class="store-lnk".*?>(.*?)<\/a>|', $html, $matches);
		$source_shop = $matches[1];
		if(empty($source_shop)){ //从店铺点进去获取
			preg_match('|<a\s*class="shop-name".*?>([\s\S]*?)</a>|', $html, $matches);
			$source_shop = $matches[1];
		}
		/**类目**/
		preg_match('|window\.runParams\.categoryId="(.*?)"|',$html, $matches);
		$categoryId = $matches[1];
		/**图片**/
		preg_match('|window\.runParams\.imageBigViewURL=(\[[\s\S]*?\]);|', $html, $matches);
		$images = $matches[1];
		/**sku属性**/
		preg_match('|var\s*skuProducts=(\[.*?\]);|', $html, $matches);
		$variants = $matches[1];

		$data = array(
			'source_platform' => 2,
			'source_shop' => $source_shop,
			'from_url' => $url,
			'product_id' => $productId,
			'import_type'	=> 1,
			'company_id'	=> 1,
			'title' => $title,
			'description' => $description,
			'category' => $categoryId,
			'variants' => $variants,
			'main_images' => $images,
			'add_time'	=> time()
		);
		if($rs = $this->getSingleData('id',array('product_id' => $productId,'source_platform' => 2))){
			$this->updateData($rs['id'],$data);
		}else{
			$this->insertData($data);
		}
	}

	public function getEbayProduct($url){
		$html = $this->curl($url);
		/**标题**/
		preg_match('|<h1 class="it-ttl" itemprop="name" id="itemTitle"><span class="g-hdn">.*?</span>(.*?)<\/h1>|', $html, $matches);
		$title = $matches[1];

	}

	public function getAmazonProduct($url){
		$html = $this->curl($url);
		/**标题**/
		preg_match('|<h1 class="it-ttl" itemprop="name" id="itemTitle"><span class="g-hdn">.*?</span>(.*?)<\/h1>|', $html, $matches);
		$title = $matches[1];

	}


	
	/**
	 * 创建需要保存的数据
	 * @param unknown $params
	 * @return string
	 */
	public function buildSaveData($params){
	    $retData = array();
	    if(!empty($params)){
	        if(isset($params['spu'])) $retData['spu'] = trim($params['spu']); 
	        if(isset($params['title'])) $retData['title'] = trim($params['title']); 
	        if(isset($params['description'])) $retData['description'] = trim($params['description']); 
	        if(isset($params['category'])) $retData['category'] = trim($params['category']); 
	        if(isset($params['spu'])) $retData['spu'] = trim($params['spu']);
            //组织通用属性
	        if(isset($params['comVar'])){
	            $newComVar = array();
	            foreach ($params['comVar'] as $var => $varVal){
	                if(!empty($var) && !empty($params['comVar']['sku'])){
	                    //对应单个的sku
	                    foreach($params['comVar']['sku'] as $k=>$v){
	                        if(empty($params['comVar']['sku'][$k])) continue;
	                        $newComVar[$params['comVar']['sku'][$k]][$var] = $varVal[$k];
	                    }
	                }
	            }
	            $retData['common_variants'] = json_encode($newComVar);
	        }
// 	        print_r($params['main_images']);
// 	        print_r($params['extra_images']);
	        //组织主图
	        if(isset($params['main_images'])){
	            $mainImages = $params['main_images'];
	            $mainImages = array_unique($mainImages);
	            $mainImages = array_values($mainImages);
	            if(count($mainImages) > 12){
	                $mainImages =  array_slice($mainImages,0,12);
	            }
	            $retData['main_images'] = json_encode($mainImages);
	        }
	        //组织主图
	        if(isset($params['extra_images'])){
	            $extraImages = $params['extra_images'];
	            $extraImages = array_unique($extraImages);
	            $extraImages = array_values($extraImages);
	            if(count($extraImages) > 12){
	                $extraImages =  array_slice($extraImages,0,12);
	            }
	            $retData['extra_images'] = json_encode($extraImages);
	        }
	        $retData['update_time'] = time();
// 	        print_r($retData['main_images']);
// 	        print_r($retData['extra_images']);
	    }
	    return $retData;
	}
	
	/**
	 * 创建查询条件
	 * @param unknown $where
	 * @return string
	 */
	public function buildWhereData($params){
	    $retData = array();
	    if(!empty($params)){
	        if(isset($params['id'])) $retData['id'] = $params['id'];
	        if(isset($params['company_id'])) $retData['company_id'] = $params['company_id'];
	    }
	    return $retData;
	}

	public function getAliexProductForShop($url){
		$links = array();
		$html = $this->getPageLinks($url,$links);
		preg_match('|data-url-rule="(.*?)"\s*data-total="(\d+)"|', $html, $matches);
		if($matches[1]&&$matches[2]){ //有分页
			$pageUrl = $matches[1];
			$pages = ceil($matches[2]/36);
			for($i=2;$i<=$pages;$i++){
				$pUrl = str_replace('*page*', $i, $pageUrl);
				echo $pUrl."列表开始采集\n";
				$this->getPageLinks($pUrl,$links);
			}
		}
		foreach ($links as $link) {
			echo $link."详情页开始采集\n";
			$this->getAliexProduct($link);
		}
	}
	
	private function getPageLinks($url,&$links){
		$html = $this->curl($url);
		preg_match_all('|<a\s*class="pic-rind"\s*href="(.*?)"|', $html, $matches, PREG_PATTERN_ORDER);
		foreach ($matches[1] as $link) { //单页链接
			$links[] = $link;
		}
		return $html;
	}


	public function curl($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$html = curl_exec($ch);
		if (curl_errno($ch))
			$html = "";
		curl_close($ch);
		return $html;
	}
}
?>