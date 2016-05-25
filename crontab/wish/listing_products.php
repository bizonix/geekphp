<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
$spuArr = array('SV008101','3291','SV000998');
$prepage = '100';
$retArr = array();
$where = array(
	'review_status' => 'approved',
	'listing_nums'	=> '0',
	'company_id'	=> '21',
);

$desc = array(1,7,'1050|1','9');
$asc = array(6,7);
$shop = array(
        'shop_account' => 'flushDress',
        'token' => 'JHBia2RmMiQxMDAkb1ZSS0NXRU00ZndmNDN3dkpRVEEuQSRZcmVtaEFNV0ZBQWlVS0ZVME1DTUdEVWlsOHM='
    );
$page = '9';
/*
$where['from_shop'] = 'yao_wish_test01';
$shop = array(
        'shop_account' => 'goodsDesign',
        'token' => 'JHBia2RmMiQxMDAkMGJwM0xnWEF1SmNTZ2xCcXpmay9CdyRxemJIT0tsQTN6YWlJTGZTRmJpbFlqVEtFUkU='
    );
$page = '7';

array("8","11-12");
$shop = array(
        'shop_account' => 'China Goods(wish)',
        'token' => 'JHBia2RmMiQxMDAka0ZMcUhTTWtoRERHLkY5THFkVjZ6dyRXczE5Ty5VUkdYRlE4MC94SkJCaDFTZk5sWVk='
    );

$page = '2';
*/
$products = M('StaticsWishProducts')->getData('*',$where,'order by number_sold desc',$page,$prepage);
foreach ($products as $productArr) {
    //判断spu是否可分销
    $rightSpu = M('Goods')->getSingleData('spu',"spu='{$productArr['parent_sku']}'");
    if(empty($rightSpu)){
        continue;
    }
	$tags = json_decode($productArr['tags'],true);
	$tagArr = array();
	foreach ($tags as $key => $tag) {
		$tagArr[] = $tag['Tag']['name'];
	}
	if(empty($tagArr)){
		continue;
	}
	
	$variants = json_decode($productArr['variants'],true);
	foreach ($variants as $key=>$variant) {
	    $product = $product_var = array();
		if($key == 0){
			$product = array(
				'name'				=> $productArr['name'],
				'main_image'		=> $productArr['main_image'],
				'sku'				=> $variant['Variant']['sku'],
				'shipping'			=> $variant['Variant']['shipping'],
				'tags'				=> implode(",", $tagArr),
				'description'		=> $productArr['description'],
				'inventory'			=> '1000',
				'parent_sku'		=> $productArr['parent_sku'],
			);
			if(isset($variant['Variant']['price'])) $product['price'] = $variant['Variant']['price']+1;
			if(isset($variant['Variant']['color'])) $product['color'] = $variant['Variant']['color'];
			if(isset($variant['Variant']['size'])) $product['size'] = $variant['Variant']['size'];
			if(isset($variant['Variant']['msrp'])) $product['msrp'] = $variant['Variant']['msrp']+5;
			if(isset($variant['Variant']['shipping_time'])) $product['shipping_time'] = $variant['Variant']['shipping_time'];
			if(isset($productArr['brand'])) $product['brand'] = $productArr['brand'];
			if(isset($variant['Variant']['landing_page_url'])) $product['landing_page_url'] = '';
			if(isset($productArr['upc'])) $productArr['upc'] = $productArr['upc'];
			if(isset($productArr['extra_images'])) $product['extra_images'] = $productArr['extra_images'];
			//刊登主产品
			A("WishButt")->setConfig($shop['shop_account'] , $shop['token']);
			$listResMain = A("WishButt")->createProduct($product);
			$listResMain = json_decode($listResMain,true);
			if(!empty($listResMain)){
			    $retArr[$productArr['parent_sku']][$variant['Variant']['sku']] = array('code' => $listResMain['code'],'message' => $listResMain['message']);
			}else{
			    $retArr[$productArr['parent_sku']][$variant['Variant']['sku']] = array('code' => '6001','message' => '网络失败');
			}
		}else{
			$product_var = array(
				'parent_sku'	=> $productArr['parent_sku'],
				'sku'			=> $variant['Variant']['sku'],
				'inventory'		=> '1000',
				'price'			=> $variant['Variant']['price']+1,
				'shipping'		=> $variant['Variant']['shipping'],
			);
			if(isset($variant['Variant']['color'])) $product_var['color'] = $variant['Variant']['color'];
			if(isset($variant['Variant']['size'])) $product_var['size'] = $variant['Variant']['size'];
			if(isset($variant['Variant']['msrp'])) $product_var['msrp'] = $variant['Variant']['msrp']+5;
			if(isset($variant['Variant']['shipping_time'])) $product_var['shipping_time'] = $variant['Variant']['shipping_time'];
			//刊登子产品
			A("WishButt")->setConfig($shop['shop_account'] , $shop['token']);
			$listResVar = A("WishButt")->createProductVariation($product_var);
			$listResVar = json_decode($listResVar,true);
			if(!empty($listResVar)){
			    $retArr[$productArr['parent_sku']][$variant['Variant']['sku']] = array('code' => $listResVar['code'],'message' => $listResVar['message']);
			}else{
			    $retArr[$productArr['parent_sku']][$variant['Variant']['sku']] = array('code' => '6001','message' => '网络失败');
			}
		}
		
	}

}
print_r($retArr);

