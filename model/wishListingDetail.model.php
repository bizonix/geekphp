<?php
/**
 * 类名：WishListingDetailModel
 * 功能：wish Listing详情表管理
 * 版本：V1.0
 * 作者：wcx
 * 时间：2015-06-27
 */
class WishListingDetailModel extends CommonModel{

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 创建需要保存的数据
	 * @param unknown $params
	 * @return string
	 */
	public function buildSaveData($params){
	    $retData = array();
	    if(!empty($params)){
	        $ls_id = isset($params['ls_id']) && !empty($params['ls_id']) ? trim($params['ls_id']) : '';
	        if(!empty($ls_id)){
	            $retData['ls_id'] = $ls_id;
	        }
	        $company_id = isset($params['company_id']) && !empty($params['company_id']) ? trim($params['company_id']) : '';
	        if(!empty($company_id)){
	            $retData['company_id'] = $company_id;
	        }
	        $parent_sku = isset($params['parent_sku']) && !empty($params['parent_sku']) ? trim($params['parent_sku']) : '';
	        if(!empty($parent_sku)){
	            $retData['parent_sku'] = $parent_sku;
	        }
	        
	        $name = isset($params['name']) && !empty($params['name']) ? trim($params['name']) : '';
	        if(!empty($name)){
	            $retData['name'] = $name;
	        }
            	        
	        $description = isset($params['description']) && !empty($params['description']) ? trim($params['description']) : '';
	        if(!empty($description)){
	            $retData['description'] = $description;
	        }
	        
	        //对tags进行处理
	        if(!empty($params['tags'])){
	            $tags = trim($params['tags']);
	            $tags = explode(',',$tags);
	            $tags = array_unique($tags);
	            $tags = array_values($tags);
	            $retData['tags'] = json_encode($tags);
	        }
	        
	        $brand = isset($params['brand']) && !empty($params['brand']) ? trim($params['brand']) : '';
	        if(!empty($brand)){
	            $retData['brand'] = $brand;
	        }
	        
	        $upc = isset($params['upc']) && !empty($params['upc']) ? trim($params['upc']) : '';
	        if(!empty($upc)){
	            $upc = strlen($upc) > 12 ? substr($upc,0,12) : $upc;
	            $retData['upc'] = $upc; 
	        }
	        
	        $landing_page_url = isset($params['landing_page_url']) && !empty($params['landing_page_url']) ? trim($params['landing_page_url']) : '';
	        if(!empty($landing_page_url)){
	            $retData['landing_page_url'] = $landing_page_url;
	        }
	        
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
	            $retData['variations'] = json_encode($newComVar);
	        }
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
	        if(isset($params['ls_id'])) $retData['ls_id'] = $params['ls_id'];
	        if(isset($params['company_id'])) $retData['company_id'] = $params['company_id'];
	        if(isset($params['parent_sku'])) $retData['parent_sku'] = $params['parent_sku'];
	    }
	    return $retData;
	}
	
	/**
	 * 插入数据
	 * wcx
	 */
	public function saveListingDetailData($data,$subfix){
	    $ret = 0;
	    //判断数据是否存在
	    $where = array(
	        'ls_id'        => $data['ls_id'],
	    );
	    $this->setTablePrefix('_'.$subfix);
	    $exist = $this->getSingleData('*',$where);
	    if(empty($exist)){
	        $res = $this->insertData($data);
	        if(!empty($res)){
	            $ret = $this->getLastInsertId();
	        }
	    }else{
	        $res = $this->updateDataWhere($data,$where);
	        if(!empty($res)){
	            $ret = $exist['id'];
	        }
	    }
	    return $ret;
	}
	
}
?>