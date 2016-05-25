<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
$since = '';//time()-3600*24*30;
$limit = 100;
$start = 0;
$res = array();
$whereArr = array("platform" => 4);
if(!empty($argv[1])){
	$whereArr['belong_company'] = $argv[1];
}
$shops = M("Shops")->getAllData("*",$whereArr);
foreach ($shops as $key => $value) {
	A("WishButt")->setConfig($value['shop_account'] , $value['token']);
	//获取结果
	do{
		$res = array();
		$res = A("WishButt")->listAllProducts($start,$limit,$since);
		$res = json_decode($res,true);
		//处理结果
		if(!empty($res["data"])){
			foreach ($res["data"] as $product) {
				$data = array(
					'from_shop' 	=> $value['shop_account'],
					'description' 	=> $product['Product']['description'],
					'name' 			=> $product['Product']['name'],
					'number_saves' 	=> $product['Product']['number_saves'],
					'number_sold' 	=> $product['Product']['number_sold'],
					'parent_sku' 	=> $product['Product']['parent_sku'],
					'review_status' => $product['Product']['review_status'],
					'tags' 			=> json_encode($product['Product']['tags']),
					'variants' 		=> json_encode($product['Product']['variants']),
					'company_id' 	=> $value['belong_company'],	
				);
				$whereData = array(
					'company_id'	=> $value['belong_company'],
					'parent_sku'	=> $product['Product']['parent_sku'],
					'from_shop'		=> $value['shop_account'],
				);
				$exist = M('StaticsWishProducts')->getSingleData('id,parent_sku',$whereData);
				if(empty($exist)){
					$handleRes = M('StaticsWishProducts')->insertData($data);
				}else{
					$handleRes = M('StaticsWishProducts')->updateData($exist['id'],$data);
				}

			}
		}

		//切换到下一页
		$start += $limit;
	}while(!empty($res["paging"]) && empty($res["paging"]['next']));

}
