<?php
/**
 * 类名：TemplateModel
 * 功能：范本主表管理
 * 版本：V1.0
 * 作者：zjr
 * 时间：2016-06-20
 */
class TemplateModel extends CommonModel{

    public function __construct(){
        parent::__construct();
    }
    
    /**
     * 创建保存数据
     * by zjr
     */
    public function buildSaveData($params){
        $retData = array();
        if(!empty($params)){
            $platform = isset($params['platform']) && !empty($params['platform']) ? trim($params['platform']) : '';
            if(!empty($platform)){
                $retData['platform'] = $platform;
            }
            $company_id = isset($params['company_id']) && !empty($params['company_id']) ? trim($params['company_id']) : '';
            if(!empty($company_id)){
                $retData['company_id'] = $company_id;
            }
            $spu = isset($params['spu']) && !empty($params['spu']) ? trim($params['spu']) : '';
            if(!empty($spu)){
                $retData['spu'] = $spu;
            }
             
            $name = isset($params['name']) && !empty($params['name']) ? trim($params['name']) : '';
            if(!empty($name)){
                $retData['name'] = $name;
            }
            $title = isset($params['title']) && !empty($params['title']) ? trim($params['title']) : '';
            if(!empty($title)){
                $retData['title'] = $title;
            }
             
            $main_image = isset($params['main_images']) && !empty($params['main_images']) ? $params['main_images'] : '';
            if(!empty($main_image)){
                $retData['main_image'] = current($main_image);
            }
            $site_id = isset($params['site_id']) && !empty($params['site_id']) ? trim($params['site_id']) : '';
            if(!empty($site_id)){
                $retData['site_id'] = $site_id;
            }
            $account = isset($params['account']) && !empty($params['account']) ? $params['account'] : array();
            if(!empty($account)){
                $retData['account'] = json_encode($account);
            }
            $create_user = isset($params['create_user']) && !empty($params['create_user']) ? trim($params['create_user']) : '';
            if(!empty($create_user)){
                $retData['create_user'] = $create_user;
            }
            $create_time = isset($params['create_time']) && !empty($params['create_time']) ? trim($params['create_time']) : '';
            if(!empty($create_time)){
                $retData['create_time'] = $create_time;
            }
            $update_time = isset($params['update_time']) && !empty($params['update_time']) ? trim($params['update_time']) : '';
            if(!empty($update_time)){
                $retData['update_time'] = $update_time;
            }
            $user_id = isset($params['user_id']) && !empty($params['user_id']) ? trim($params['user_id']) : '';
            if(!empty($user_id)){
                $retData['user_id'] = $user_id;
            }
            $table_suffix = isset($params['table_suffix']) && !empty($params['table_suffix']) ? trim($params['table_suffix']) : '';
            if(!empty($table_suffix)){
                $retData['table_suffix'] = $table_suffix;
            }
        }
        return $retData;
    }
    
    /**
	 * 创建查询条件
	 * @param unknown $where
	 * @return string
	 * by zjr
	 */
	public function buildWhereData($params){
	    $retData = array();
	    if(!empty($params)){
	        if(isset($params['id'])) $retData['id'] = $params['id'];
	        if(isset($params['platform'])) $retData['platform'] = $params['platform'];
	        if(isset($params['name'])) $retData['name'] = $params['name'];
	        if(isset($params['spu'])) $retData['spu'] = $params['spu'];
	        if(isset($params['company_id'])) $retData['company_id'] = $params['company_id'];
	        if(isset($params['site_id'])) $retData['site_id'] = $params['site_id'];
	        if(isset($params['account'])) $retData['account'] = $params['account'];
	        if(isset($params['create_user'])) $retData['create_user'] = $params['create_user'];
	        if(isset($params['update_time'])) $retData['update_time'] = $params['update_time'];
	        if(isset($params['user_id'])) $retData['user_id'] = $params['user_id'];
	    }
	    return $retData;
	}
	
	/**
	 * 插入数据
	 * zjr
	 */
	public function saveTemplateData($data,$detailData){
	    $ret = 0;
	    if(empty($data['company_id']) || empty($data['platform']) || empty($data['name']) || empty($data['spu'])){
	        return false;
	    }
	    //判断数据是否存在
	    $where = array(
	        'company_id'   => $data['company_id'], 
	        'platform'     => $data['platform'], 
	        'name'         => $data['name'], 
	        'spu'          => $data['spu'], 
	    );
	    $exist = $this->getSingleData('*',$where);
        $this->begin();
	    if(empty($exist)){
	        $data['create_time'] = time();
	        $res1 = $this->insertData($data);
	        if(!empty($res1)){
	            $tpId = $this->getLastInsertId(); 
	        }else{
                $this->rollback();
                return false;
            }
	    }else{
            $tpId                   = $exist['id'];
            $data['table_suffix']   = $exist['table_suffix'];
	        $res1 = $this->updateData($tpId,$data);
	        if(empty($res1)){
                $this->rollback();
                return false;
            }
	    }
        $detailData['tp_id'] = $tpId;
        $saveDetailDatas    = M('WishTemplateDetail')->buildSaveData($detailData);
        $res2 = M('WishTemplateDetail')->saveTemplateDetailData($saveDetailDatas,$data['table_suffix']);
        if(empty($res2)){
            $this->rollback();
            return false;
        }
        $this->commit();
	    return $tpId;
	}
    
}
?>