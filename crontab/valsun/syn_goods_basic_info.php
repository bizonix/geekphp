<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$i = 1;
$companyId = 3;
do{
	$goods = M("Goods")->getData("spu","1","group by spu order by id desc",$i,500);
	if(!empty($goods)){
		$spuArr = array();
		foreach($goods as $k=>$v){
			$goodsTemplates = M("GoodsTemplates")->getSingleData("id",array("spu"=>$v["spu"],"companyId"=>$companyId));
			if(empty($goodsTemplates)){
				$res = A("ValsunButt")->getGoodsBasicInfo($v["spu"]);
				if(!empty($res[1][0])){
					$handleData = array(
						"spu"				=> $res[1][0]["spu"],
						"en_name"			=> $res[1][0]["en_name"],
						"cn_name"			=> $res[1][0]["cn_name"],
						"en_description"	=> $res[1][0]["en_description"],
						"cn_description"	=> $res[1][0]["cn_description"],
						"specifics"			=> json_encode($res[1][0]["specifics"]),
						"ebayNavigation"	=> $res[1][0]["ebayNavigation"],
						"ebayCategoryId"	=> $res[1][0]["ebayCategoryId"],
						"mainImages"		=> json_encode($res[1][0]["mainImages"]),
						"descriptionImages" => json_encode($res[1][0]["descriptionImages"]),
						"subitems"			=> json_encode($res[1][0]["subitems"]),
						"updateTime"		=> time(),
						"companyId"			=> $companyId
					);
					$ret = M("GoodsTemplates")->insertData($handleData);
					if($ret){
						echo "insert Success \r\n";
					}else{
						$err = M("GoodsTemplates")->getErrorMsg();
						echo json_encode($err);
						log::writeLog(json_encode($err),"valsun","synSpuBasicInfo","d");
						echo "insert Error \r\n";
					}
				}
			}
		}
	}
	$i++;
}while(!empty($goods));
