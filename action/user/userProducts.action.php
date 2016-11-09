<?php
/**
 * 类名：ProductsAct
 * 功能: 产品管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/02/16
 * errCode：
 */
class UserProductsAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 獲取公司信息 根据公司ID
	 */
	public function act_getProductsList($companyId){
		$where 	  = array("companyId" => $companyId);
		if(isset($_REQUEST['materialStatus']) && !empty($_REQUEST['materialStatus'])){
			$where['status']	= $_REQUEST['materialStatus'];
		}
		if(isset($_REQUEST['materialType']) && !empty($_REQUEST['materialType']) && !empty($_REQUEST['materialCode'])){
			$where[$_REQUEST['materialType']]	= $_REQUEST['materialCode'];
		}
		$count	  = M("Goods")->getDataCount($where);
		$p 		  = new Page ($count,10);
		$goodsLists = M("Goods")->getData("*",$where,"order by isNew desc,id desc",$this->page,$this->perpage);
		$cateGoryArr = M("GoodsCategory")->getAllData("id,name","1","id");
		if(!empty($goodsLists)){
			$categorys = array();
			foreach ($goodsLists as $k=>$v) {
				//获取分类
				$goodsCategory = explode("-", $v["goodsCategory"]);
				foreach ($goodsCategory as $key => $value) {
					$goodsCategory[$key] = $cateGoryArr[$value]['name'];
				}
				$goodsLists[$k]['goodsCategory'] = implode("->", $goodsCategory);
				$spu = changeValsunSpu($v['spu']);
				//获取图片
				$firstPre	= substr($spu,0,2);
				$secondPre  = substr($spu,2,1);
				$goodsLists[$k]['imgDir'] = C("IMAGE_SYS_ADDR")."/images/{$companyId}/{$firstPre}/{$secondPre}/{$spu}-G.jpg";
 			}

		}

		if(empty($goodsLists)){
			self::$errMsg['10007']	= get_promptmsg('10007','产品');
			return false;
		}
		$page 		= $p->fpage();
		return array("goodLists" => $goodsLists,"page"=>$page,"count"=>$count);
	}

	public function act_getGoodsInfoById($pid){
		$companyId = get_usercompanyid();
		if(empty($pid)){
			return array("goodsInfo"=>array("companyId" => $companyId));
		}
		$companyId = get_usercompanyid();
		$goodsInfo = M("Goods")->getSingleData("*","id={$pid} and companyId={$companyId}");
		$cateGoryArr = M("GoodsCategory")->getAllData("id,name,pid","1","id");
		if(!empty($goodsInfo)){
			$categorys = array();
			//获取分类
			$goodsCategory = explode("-", $goodsInfo["goodsCategory"]);
			foreach ($goodsCategory as $key => $value) {
				$goodsCategory[$key] = $cateGoryArr[$value]['name'];
			}
			$goodsInfo['goodsCategory'] = implode("->", $goodsCategory);

		}
		//获取图片
		$images = getImages($goodsInfo['spu'],$companyId);
		$goodsInfo['images'] =  json_decode($images,true);
		return array("goodsInfo"=>$goodsInfo,'goodsCategory'=>$goodsCategory,'cateGoryArr'=>$cateGoryArr);
	}

	/*
	 * 获取 category
	 */
	public function act_getCategoryByPid($pid){
		if(empty($pid)){
			self::$errMsg[10007]   =   get_promptmsg(10007,"父类别");
			return false;
		}
		$cateGoryArr = M("GoodsCategory")->getAllData("id,name,pid","pid = {$pid}","id");
		return $cateGoryArr;
	}

	/*
	 * 编辑店铺
	 */
	public function act_editGoods($goodsData){
		if(empty($goodsData)){
			self::$errMsg[10007]   =   get_promptmsg(10007,"产品");
			return false;
		}
		$companyId = get_usercompanyid();
		if($companyId != $goodsData['companyId']){
			self::$errMsg[10010]   =   get_promptmsg(10010);
			return false;
		}
		if(!empty($goodsData['category'])){
			foreach ($goodsData['category'] as $value) {
				if(!empty($value) && $value != '#'){
					$goodsData['goodsCategory'][] = $value;
				}
			}
			$goodsData['goodsCategory'] = implode("-", $goodsData['goodsCategory']);
		}
		if(!empty($goodsData['mainImgName']) || !empty($goodsData['propImgName'])){
			$imgArr = array_merge($goodsData['mainImgName'],$goodsData['propImgName']);
			$imgStr = json_encode($imgArr);
		}
		// echo "<pre>";
		unset($goodsData['id']);
		unset($goodsData['mainImgName']);
		unset($goodsData['propImgName']);
		unset($goodsData['saveBtn']);
		unset($goodsData['category']);

		$goodsData['goodsUpdateTime'] = time();
		$hasGoods = M("Goods")->getSingleData("*",array('sku'=>$goodsData['sku'],'companyId'=>$goodsData['companyId']));
		if(!empty($hasGoods)){
			$updateRes = M("Goods")->updateData($hasGoods['id'],$goodsData);
			if(!$updateRes){
				self::$errMsg[10001]   =   get_promptmsg(10001,"更新产品");
				return false;
			}
		}else{
			$goodsData['goodsCreatedTime'] = time();
			$insertRes = M("Goods")->insertData($goodsData);
			if(!$insertRes){
				self::$errMsg[10001]   =   get_promptmsg(10001,"新增产品");
				return false;
			}
		}
		//提交图片
		if(!empty($imgStr)){
			F("opensys");
			$sumitImgRes = vita_get_url_content2(C('IMAGE_HANDLE_SOURCE_URL')."submitTmpImg/{$companyId}/{$imgStr}");
		}
		return true;
	}
	

}
