<?php
/**
 * 类名：ListingAct
 * 功能：Listing管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/6/27
 * errCode：
 */ 
class ListingAct extends CheckAct {
    private $params;
    public function __construct(){
        parent::__construct();
        $this->params = array(
            'company_id'    => isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : 0,
            'platform'      => isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 0,
            'account'       => isset($_REQUEST['account']) ? $_REQUEST['account'] : '',
            'spu'           => isset($_REQUEST['spu']) ? $_REQUEST['spu'] : '',
            'status'        => isset($_REQUEST['status']) ? $_REQUEST['status'] : 0,
            'create_user'   => isset($_REQUEST['create_user']) ? $_REQUEST['create_user'] : '',
        );
    }
    
    /**
     * Listing列表
     */
    public function listingList($companyId){
        $sort     = "order by id desc";
        $this->params['company_id'] = $companyId;
        $where    = $this->_buildWhere($this->params);
        $count	  = M($this->act_getModel())->getDataCount($where);
        $p 		  = new Page ($count,10);
        $lsData   = M($this->act_getModel())->getData("*",$where,$sort,$this->page, $this->perpage);
        $page 		= $p->fpage();
        $platforms  = M("Platform")->getAllData("id,platform_en_name,platform_cn_name","type IN (1,2,3)","id");
        $shops      = M('Shops')->getAllData('id,shop_account,platform',array('belong_company' => $companyId),'id');
        //解析店铺账号
        $lsStatus = C('LISTING_STATUS');
        foreach ($lsData as &$ls){
            $ls['statusStr'] = $lsStatus[$ls['status']];
        }
        return array('lsData'=>$lsData,'platforms'=>$platforms,'lsStatus' => $lsStatus,'shops' => $shops,'page'=>$page,'count'=>$count);
    }   
    
    /**
     * 获取Listing的信息 通过ListingID
     * param $idArr = array('1231','32132');
     * return array(
     *      '2' => array(
     *          "lsMain"    => array(....),
     *          "lsDetail"  => array(....),
     *      )
     * );
     * by zjr
     * 2015-06-27
     */
    public function getLsInfoByIds($idArr){
        if(empty($idArr)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"范本ID");
            return false;
        }
        $retLsArr   = array();
        $lsInfos    = M('Listing')->getAllData("*","id IN ('".implode("','",$idArr)."')");
        if(empty($lsInfos)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"Listing");
            return false;
        }
        //获取所有平台
        $platforms  = M("Platform")->getAllData("id,platform_en_name,platform_cn_name","type IN (1,2,3)","id");
        foreach($lsInfos as &$lsmain){
            $tableName = $platforms[$lsmain['platform']]['platform_en_name'].'ListingDetail';
            M($tableName)->setTablePrefix('_'.$lsmain['table_suffix']);
            $lsDetail = M($tableName)->getSingleData("*",array("ls_id" => $lsmain['id']));
            if(!empty($lsDetail)){
                $lsDetail['tags']           = json_decode($lsDetail['tags'],true);
                if(!empty($lsDetail['tags'])){
                    $lsDetail['tags'] = implode(',',$lsDetail['tags']);
                }
                if(!empty($lsDetail['variations'])){
                    $lsDetail['variations'] = json_decode($lsDetail['variations'],true);
                }
                if(!empty($lsDetail['main_images'])){
                    $lsDetail['main_images']      = json_decode($lsDetail['main_images'],true);
                }
                if(!empty($lsDetail['extra_images'])){
                    $lsDetail['extra_images']      = json_decode($lsDetail['extra_images'],true);
                }
                $retLsArr[$lsmain['id']]    = array('main' => $lsmain,'detail' => $lsDetail);
            }
        }
        return array("lsInfo" => $retLsArr);
    }
    
    /**
     * 上线listing  spu
     * zjr
     */
    public function onlineListing($lsIds,$companyId){
        if(empty($companyId)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"companyId");
            return false;
        }
        $lsIds = explode(',', trim($lsIds));
        $lsIds = array_unique($lsIds);
        if(empty($lsIds)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"lsId");
            return false;
        }
        $lsInfos = M('Listing')->getAllData('id,account,platform,spu',"id IN ('".implode("','",$lsIds)."') and company_id = {$companyId}");
        if(empty($lsInfos)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"listing");
            return false;
        }
        $ret = array();
        foreach ($lsInfos as $lsInfo){
            //获取店铺信息
            $shopInfo = M('Shops')->getSingleData('*',array('id' => $lsInfo['account']));
            if(empty($shopInfo)){
                $ret[$lsInfo['id']] = array('10007',get_promptmsg(10007,"店铺账号"));
                continue;
            }
            //wish平台
            if($lsInfo['platform'] == '4'){
                A("WishButt")->setConfig($shopInfo['shop_account'] , $shopInfo['token']);
                $res = A("WishButt")->enableParentSku($lsInfo['spu']);
                $res = json_decode($res,true);
                if(!empty($res)){
                    if($res['code'] == 0){
                        //修改本地的listing信息
                        $updateListingModRes = M('Listing')->updateData($lsInfo['id'],array('status' => 1));
                        if($updateListingModRes){
                            $ret[$lsInfo['id']] = array('200','操作成功！');
                        }else{
                            $ret[$lsInfo['id']] = array('10001','成功上架线上Listing，修改本地listing失败！');
                        }
                    }else{
                        $ret[$lsInfo['id']] = array($res['code'],$res['message']);
                    }
                }else{
                    $ret[$lsInfo['id']] = array('10001',get_promptmsg(10001,'上架listing'));
                }
            }
        }
        return $ret;
    }
    
    /**
     * 下线listing  spu
     * zjr
     */
    public function offlineListing($lsIds,$companyId){
        if(empty($companyId)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"companyId");
            return false;
        }
        $lsIds = explode(',', trim($lsIds));
        $lsIds = array_unique($lsIds);
        if(empty($lsIds)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"lsId");
            return false;
        }
        $lsInfos = M('Listing')->getAllData('id,account,platform,spu',"id IN ('".implode("','",$lsIds)."') and company_id = {$companyId}");
        if(empty($lsInfos)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"listing");
            return false;
        }
        $ret = array();
        foreach ($lsInfos as $lsInfo){
            //获取店铺信息
            $shopInfo = M('Shops')->getSingleData('*',array('id' => $lsInfo['account']));
            if(empty($shopInfo)){
                $ret[$lsInfo['id']] = array('10007',get_promptmsg(10007,"店铺账号"));
                continue;
            }
            //wish平台
            if($lsInfo['platform'] == '4'){
                A("WishButt")->setConfig($shopInfo['shop_account'] , $shopInfo['token']);
                $res = A("WishButt")->disableParentSku($lsInfo['spu']);
                $res = json_decode($res,true);
                if(!empty($res)){
                    if($res['code'] == 0){
                        //修改本地的listing信息
                        $updateListingModRes = M('Listing')->updateData($lsInfo['id'],array('status' => 2));
                        if($updateListingModRes){
                            $ret[$lsInfo['id']] = array('200','操作成功！');
                        }else{
                            $ret[$lsInfo['id']] = array('10001','成功下架线上Listing，修改本地listing失败！');
                        }
                    }else{
                        $ret[$lsInfo['id']] = array($res['code'],$res['message']);
                    }
                }else{
                    $ret[$lsInfo['id']] = array('10001',get_promptmsg(10001,'下架listing'));
                }
            }
        }
        return $ret;
    }
    
    /**
     * 同步线上listing
     * zjr
     */
    public function synShopListing($platform,$account,$startTime,$companyId){
        if(empty($platform)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"platform");
            return false;
        }
        if(empty($account)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"account");
            return false;
        }
        if(empty($companyId)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"companyId");
            return false;
        }
        if(empty($startTime)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"startTime");
            return false;
        }
        
        //获取店铺信息
        $shopInfo = M('Shops')->getSingleData('*',array('id' => $account , 'platform' => $platform , 'belong_company' => $companyId));
        if(empty($shopInfo)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"店铺");
            return false;
        }
        $limit = 500;
        $since = date('Y-m-d',strtotime($startTime));
        $start = 0;
        $ret   = array();
        //获取结果
        do{
            A("WishButt")->setConfig($shopInfo['shop_account'] , $shopInfo['token']);
            $res = array();
            $res = A("WishButt")->listAllProducts($start,$limit,$since);
            $res = json_decode($res,true);
            //处理结果
            if(!empty($res["data"])){
                foreach ($res["data"] as $product) {
                    $params = array(
                        'company_id'        => $companyId,
                        'parent_sku'        => $product['Product']['parent_sku'],
                        'name'              => $product['Product']['name'],
                        'account'           => $account,
                        'tags'              => implode(',',$this->buildWishTags($product['Product']['tags'])),
                        'comVar'            => $this->buildWishComVarTags($product['Product']['variants']),
                        'main_images'       => array($product['Product']['main_image']),
                        'extra_images'      => explode('|', $product['Product']['extra_images']),
                        'description'       => $product['Product']['description'],
                    );
                    $saveRes = A('WishListing')->saveListingData($params,$companyId);
                    if($saveRes){
                        $ret[$product['Product']['parent_sku']] = array('200','同步成功！');
                    }else{
                        $ret[$product['Product']['parent_sku']] = array('2001','同步失败！');
                    }
                }
            }
            //切换到下一页
            $start += $limit;
        }while(!empty($res["paging"]) && !empty($res["paging"]['next']));
        
        return $ret;
        
    }
    
    protected function buildWishTags($tagsArr){
        $ret = array();
        if(!empty($tagsArr)){
            foreach ($tagsArr as $tag){
                $ret[] = $tag['Tag']['name'];
            }
        }
        return $ret;
    }
    
    protected function buildWishComVarTags($variants){
        $comVar = array();
        if(!empty($variants)){
            foreach ($variants as $k=>$variant){
                foreach ($variant['Variant'] as $kk=>$var){
                    if(in_array($kk, array('sku','inventory','price','shipping','color','size','msrp','shipping_time'))){
                        $comVar[$kk][] = $var;
                    }
                }
            }
        }
        return $comVar;
    }
    
}
