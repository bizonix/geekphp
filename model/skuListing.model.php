<?php
/**
 * 类名：SkuListingModel
 * 功能：sku刊登和范本统计
 * 版本：V1.0
 * 作者：wcx
 * 时间：2015-05-05
 */
class SkuListingModel extends CommonModel{
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * 组装数据
	 * wcx
	 */
	public function buildDatas($params){
	    $publicData    = array();
	    $multyplyData  = array();
	    if(!empty($params)){
	        if(isset($params['company_id'])) $publicData['company_id'] = $params['company_id'];
	        if(isset($params['platform'])) $publicData['platform'] = $params['platform'];
	        if(isset($params['tp_id'])) $publicData['tp_id'] = $params['tp_id'];
	        if(isset($params['listing_id'])) $publicData['listing_id'] = $params['listing_id'];
	        if(isset($params['spu'])) $publicData['spu'] = $params['spu'];
	        if(isset($params['sku'])) $publicData['sku'] = $params['sku'];
	    }
	}
	

	//解析订单数据
	public function insertSkuListingDatas($data){
		if(empty($data['company_id'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数company_id");
			return false;
		}
		if(empty($data['platform'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数platform");
			return false;
		}
		if(empty($data['spu'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数spu");
			return false;
		}
		if(empty($data['sku'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数sku");
			return false;
		}
		if(empty($data['ls_id'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数tp_id");
			return false;
		}
        $insertData = array(
            'company_id'    => $data['company_id'],
            'platform'      => $data['platform'],
            'spu'           => $data['spu'],
            'sku'           => $data['sku'],
            'ls_id'         => $data['ls_id'],
        );
        $whereData  = $insertData;
        if(isset($data['tp_id'])) $insertData['tp_id'] = $data['tp_id'];
        
		//判断是否存在
		$exist = $this->getSingleData('*',$whereData);
		if(empty($exist)){
			$res = $this->insertData($insertData);
		}else{
			$res = $this->updateData($exist['id'],$insertData);
		}

		return $res;
	}

	/*
	 * 插入多sku
	 * params  $skuDatas = array(
	 *     'company_id'   => 1,
           'platform'     => 1,
           'tp_id'        => 12,
           'spu'          => 'SV00123',
           'skuArr'       => array('SV00123_XL_1','SV00123_XL_2','SV00123_XL_2'),
	 * );
	 * 
	 * return  array(
	 *     'SV00123_XL_1' = array('200','操作成功！'),
	 *     'SV00123_XL_2' = array('200','操作成功！'),
	 *     'SV00123_XL_3' = array('200','操作成功！'),
	 * )
	 * wcx
	 */
	public function insertMoreSkuListingDatas($skuDatas){
	    if(!isset($skuDatas['skuArr']) || empty($skuDatas['skuArr'])){
	        self::$errMsg[10007]   =   get_promptmsg(10007,"参数skuArr");
	        return false;
	    }
	    $skuArr  = $skuDatas['skuArr'];
	    unset($skuDatas['skuArr']);
	    $retArr  = array();
	    $tmpData = array();
	    foreach ($skuArr as $sku){
	        if(!empty($sku)){
	            $tmpData = $skuDatas;
	            $tmpData['sku'] = $sku;
	            $res = $this->insertSkuListingDatas($tmpData);
	            if($res){
	                $retArr[$sku] = array('200','操作成功！');
	            }else{
	                $retArr[$sku] = array('2001','插入失败！');
	            }
	        }
	    }
	    return $retArr;
	}
    
    
}
?>