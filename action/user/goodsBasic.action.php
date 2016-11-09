<?php
/**
 * 类名：GoodsBasicListAct
 * 功能: 产品基础信息管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/06/01
 * errCode：
 */
class GoodsBasicAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取产品基础信息 根据公司ID
	 */
	public function getGoodsBasicList($companyId,$params=array()){
	    if(empty($companyId)){
	        self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
	        return false;
	    }
		$where 	  = array("company_id" => $companyId);
		$filed    = array('source_platform','source_shop','import_type','company_id','spu','title','main_images','add_time','creater');
		if(isset($params['source_platform']) && strlen($params['source_platform']) > 0)  $where['source_platform'] = $params['source_platform'];
		if(isset($params['source_shop']) && strlen($params['source_shop']) > 0)  $where['source_shop'] = $params['source_shop'];
		if(isset($params['import_type']) && strlen($params['import_type']) > 0)  $where['import_type'] = $params['import_type'];
		if(isset($params['creater']) && !empty($params['creater']))  $where['creater'] = $params['creater'];
		if(isset($params['spu']) && !empty($params['spu']))  $where['spu'] = $params['spu'];
		//获取所有平台
		$platform = M('Platform')->getAllData('*','type IN (1,2,3)','id');
		$importType = C('IMPORT_TYPE');
		$sourceShop = array();
		$count	  = M("GoodsBasic")->getDataCount($where);
		$p 		  = new Page ($count,10);
		$goodsBasics = M("GoodsBasic")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
		if(!empty($goodsBasics)){
		    foreach ($goodsBasics as &$val){
		        $val['main_images'] = !empty($val['main_images']) ? json_decode($val['main_images'],true) : array();
		        $val['extra_images'] = !empty($val['extra_images']) ? json_decode($val['extra_images'],true) : array();
		        $val['import_type_str'] = $importType[$val['import_type']];
		        $val['platform_str'] = $platform[$val['source_platform']]['platform_cn_name'];
		        if(!in_array($val['source_shop'],$sourceShop)){
		            $sourceShop[] = $val['source_shop'];
		        }
		    }
		}
		$page 		= $p->fpage();
		return array("goodsBasics" => $goodsBasics,"page"=>$page,"count"=>$count,'platform' => $platform,'importType' => $importType,'sourceShop' => $sourceShop);
	}
	
	/**
	 * 获取产品基础信息 根据公司ID
	 */
	public function getGoodsBasicById($companyId,$goodsBasicId){
	    if(empty($companyId)){
	        self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
	        return false;
	    }
	    if(empty($goodsBasicId)){
	        self::$errMsg['10007'] = get_promptmsg(10007,"产品信息");
	        return false;
	    }
	    $basicInfo = array();
	    $basicInfo = M("GoodsBasic")->getSingleData('*',"id = {$goodsBasicId}");
	    $category = json_decode($basicInfo['category'],true);
	    if(!empty($basicInfo)){
	        $basicInfo['main_images']      = !empty($basicInfo['main_images']) ? json_decode($basicInfo['main_images'],true) : array();
	        $basicInfo['extra_images']     = !empty($basicInfo['extra_images']) ? json_decode($basicInfo['extra_images'],true) : array();
	        $basicInfo['variants']         = !empty($basicInfo['variants']) ? json_decode($basicInfo['variants'],true) : array();
	        $basicInfo['common_variants']  = !empty($basicInfo['common_variants']) ? json_decode($basicInfo['common_variants'],true) : array();
	        $basicInfo['category']         = !empty($basicInfo['category']) ? implode(' > ',json_decode($basicInfo['category'],true)) : '';
	    }
	    return array("goodsBasics" => $basicInfo);
	    
	}
	
	/**
	 * 根据地址抓取图片
	 */
	public function fetchImagesByUrl(){
	    
	}
	
	/**
	 * 保存基础信息
	 * @param unknown $params
	 * @return boolean|unknown
	 * by wcx
	 */
	public function saveBasicInfo($params){
	    //获取公司信息
	    $companyId = 1;
	    if(empty($companyId)){
	        self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
	        return false;
	    }
	    if(isset($params['company_id']) && $params['company_id'] != $companyId){
	        self::$errMsg['10010'] = get_promptmsg(10010);
	        return false;
	    }
	    $saveDatas     = M('GoodsBasic')->buildSaveData($params);
	    $whereDatas    = M('GoodsBasic')->buildWhereData($params);
	    $saveRes = M('GoodsBasic')->updateDataWhere($saveDatas,$whereDatas);
	    if($saveRes){
	        return true;
	    }else{
	        self::$errMsg['10001'] = get_promptmsg(10001,"更新");
	        return false;
	    }
	}

	

}
