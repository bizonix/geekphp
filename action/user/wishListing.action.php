<?php
/**
 * 类名：WishListingAct
 * 功能：Listing管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/06/27
 * errCode：
 */ 
class WishListingAct extends CheckAct {
    private $lsId = 0;
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * 保存Listing
     * wcx
     */
    public function saveListingData($params,$companyId){
        //获取公司信息
	    if(empty($companyId)){
	        self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
	        return false;
	    }
	    if(isset($params['company_id']) && $params['company_id'] != $companyId){
	        self::$errMsg['10010'] = get_promptmsg(10010);
	        return false;
	    }
	    $platform = '4';
	    $params['company_id']  = $companyId;
	    $userId                = get_userid();
	    $userName              = get_username();
	    $mainParams = $wishDetailsParams = $params;
	    $mainParams['title']       = $mainParams['name'];
	    $mainParams['name']        = $mainParams['tp_name'];
	    $mainParams['account']     = $mainParams['account'];
	    $mainParams['spu']         = $mainParams['parent_sku'];
	    $mainParams['create_user'] = $userName;
	    $mainParams['user_id']     = $userId;
	    $mainParams['update_time'] = time();
	    $mainParams['platform']    = $platform;
	    $mainParams['table_suffix']= date('Y_m');
	    M('Listing')->begin();
	    $mainSaveData = M('Listing')->buildSaveData($mainParams);
	    $res1 = M('Listing')->saveTemplateData($mainSaveData);
	    if(empty($res1)){
	        M('Listing')->rollback();
	    }
	    $this->lsId = $res1;
	    $wishDetailsParams['ls_id'] = $res1;
	    $saveDetailDatas    = M('WishListingDetail')->buildSaveData($wishDetailsParams);
	    $res2 = M('WishListingDetail')->saveTemplateDetailData($saveDetailDatas,$mainParams['table_suffix']);
	    if($res2){
	        //记录sku
	        $skuData = array(
	            'company_id'   => $companyId,
	            'platform'     => $platform,
	            'tp_id'        => $res1,
                'ls_id'        => $this->lsId,
	            'spu'          => $mainParams['parent_sku'],
	            'skuArr'       => $mainParams['comVar']['sku'],
	        );
	        M('SkuListing')->insertMoreSkuListingDatas($skuData);
	        M('Listing')->commit();
	        return true;
	    }else{
	        M('Listing')->rollback();
	        self::$errMsg['10001'] = get_promptmsg(10001,"更新");
	        return false;
	    }
    }
    
    /**
     * wish刊登
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     */
    public function listingData($params,$companyId){
        //获取公司信息
        if(empty($companyId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
            return false;
        }
        //首先先保存范本信息
        $saveTpRes = $this->saveTemplateData($params,$companyId);
        if(empty($saveTpRes)){
            return false;
        }
        //开始刊登
        $listingRes = $this->listingDataToWish($this->tpId,$companyId);
        if(empty($listingRes)){
            return false;
        }
        //拆解刊登结果信息
        foreach ($listingRes as $account => $skuRes){
            foreach ($skuRes as $sku => $res){
                //如果刊登成功，就保存到listing表中，并且修改记录sku listing表
                
            }
        }
    }
    
    /**
     * wish刊登-核心刊登方法
     * wcx
     */
    public function listingDataToWish($lsId,$companyId,$flag = true){
        if(empty($lsId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"Listing ID");
            return false;
        }
        if(empty($companyId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
            return false;
        }
        //获取范本信息
        $lsMainData     = M('Listing')->getSingleData('*',"id = $lsId");
        if(empty($lsMainData)){
            self::$errMsg['10007'] = get_promptmsg(10007,"Listing");
            return false;
        }
        $wishShops = M('Shops')->getSingleData('*',"id = '{$lsMainData['account']}' and belong_company = {$companyId} and platform = '4'");
        if(empty($wishShops)){
            self::$errMsg['10007'] = get_promptmsg(10007,"wish店铺");
            return false;
        }
        
        M('WishListingDetail')->setTablePrefix('_'.$tpMainData['table_suffix']);
        $lsDetailData   = M('WishListingDetail')->getSingleData('*',array('ls_id' => $lsId,'company_id' => $tpMainData['company_id']));
        if(empty($lsDetailData)){
            self::$errMsg['10007'] = get_promptmsg(10007,"范本详情信息");
            return false;
        }
        
        //验证必填信息
        if(empty($lsDetailData['parent_sku'])){
            self::$errMsg['10007'] = get_promptmsg(10007,"主料号");
            return false;
        }
        
        $tagArr = json_decode($lsDetailData['tags'],true);
        if(empty($tagArr)){
            self::$errMsg['10007'] = get_promptmsg(10007,"标签");
            return false;
        }
        
        $variations = json_decode($lsDetailData['variations'],true);
        if(empty($variations)){
            self::$errMsg['10007'] = get_promptmsg(10007,"多属性");
            return false;
        }
        
        $discription = json_decode($lsDetailData['discription'],true);
        if(empty($discription)){
            self::$errMsg['10007'] = get_promptmsg(10007,"描述信息");
            return false;
        }
        
        $main_images = json_decode($lsDetailData['main_images'],true);
        if(empty($main_images)){
            self::$errMsg['10007'] = get_promptmsg(10007,"描述信息");
            return false;
        }
        
        $extra_images = json_decode($lsDetailData['extra_images'],true);

        $retArr = array();
        //如果flag==true说明需要修改产品的主信息
        if($flag){
            $mainProduct = array(
                'parent_sku'        => $lsDetailData['parent_sku'],
                'name'              => $lsDetailData['name'],
                'description'       => $lsDetailData['description'],
                'tags'              => implode(",", $tagArr),
                'brand'             => $lsDetailData['brand'],
                'landing_page_url'  => $lsDetailData['landing_page_url'],
                'upc'               => $lsDetailData['upc'],
                'main_image'        => $main_images[0],
                'extra_images'      => $extra_images,
            );
            A("WishButt")->setConfig($wishShops['shop_account'] , $wishShops['token']);
            try {
                $listMainRes = A("WishButt")->updateProduct($mainProduct);
                $listMainRes = json_decode($listMainRes,true);
            } catch (Exception $e) {
                $listMainRes = array();
            }
            if(!empty($listMainRes)){
                $retArr[$wishShops['id']]['main'] = array('code' => $listMainRes['code'],'message' => $listMainRes['message']);
            }else{
                $retArr[$wishShops['id']]['main'] = array('code' => '6001','message' => '网络失败');
            }
        }

        $existSku = array();   //获取存在的sku，用于下线不存在的sku
        //获取已经刊登的sku
        $hasListSku = M('SkuListing')->getAllData('*',array('ls_id' => $lsId,'company_id' => $companyId));

        foreach ($variations as $variant) {
            //必填项不存在则跳过
            if(empty($variant['sku']) || empty($variant['inventory']) || empty($variant['price']) || empty($variant['shipping'])){
                continue;
            }
            $existSku[] = $variant['sku'];

            $product_var = array(
                'sku'			=> $variant['sku'],
                'inventory'		=> $variant['inventory'],
                'enabled'       => True,
                'price'			=> $variant['price'],
                'shipping'		=> $variant['shipping'],
            );
            if(isset($variant['color'])) $product_var['color'] = $variant['color'];
            if(isset($variant['size'])) $product_var['size'] = $variant['size'];
            if(isset($variant['msrp'])) $product_var['msrp'] = $variant['msrp'];
            if(isset($variant['shipping_time'])) $product_var['shipping_time'] = $variant['shipping_time'];
            //刊登子产品
            $listResVar = array();
            A("WishButt")->setConfig($wishShops['shop_account'] , $wishShops['token']);
            try {
                $listResVar = A("WishButt")->updateVariant($product_var);
                $listResVar = json_decode($listResVar,true);
            } catch (Exception $e) {
                $listResVar = array();
            }
            if(!empty($listResVar)){
                $retArr[$wishShops['id']]['variations'][$variant['sku']] = array('code' => $listResVar['code'],'message' => $listResVar['message']);
            }else{
                $retArr[$wishShops['id']]['variations'][$variant['sku']] = array('code' => '6001','message' => '网络失败');
            }
        }

        //删除不存在的已经删除了的sku
        if(!empty($hasListSku)){
            foreach ($hasListSku as $skuArr) {
                if(!in_array($skuArr['sku'], $existSku)){
                    A("WishButt")->setConfig($wishShops['shop_account'] , $wishShops['token']);
                    try {
                        $disableRes = A('WishButt')->disableSku($skuArr['sku']);
                        $disableRes = json_decode($disableRes,true);
                    } catch (Exception $e) {
                        $disableRes = array();
                    }
                    if(!empty($listResVar)){
                        $retArr[$wishShops['id']]['disable'][$skuArr['sku']] = array('code' => $disableRes['code'],'message' => $disableRes['message']);
                    }else{
                        $retArr[$wishShops['id']]['disable'][$skuArr['sku']] = array('code' => '6001','message' => '网络失败');
                    }
                }
            }
        }

        return $retArr;
    }

    /**
     * 功能：删除不存在的sku
     */
    public function disableSomeListing($lsId,$companyId,$existSku){
        if(empty($lsId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"lsId");
            return false;
        }
        //获取已经刊登的sku
        $hasListSku = M('SkuListing')->getAllData('*',array('ls_id' => $lsId,'company_id' => $companyId));
        if(!empty($hasListSku)){
            $disableSkus = array();
            foreach ($disableSkus as $skuArr) {
                if(!in_array($skuArr['sku'], $existSku)){
                    $disableSkus[] = $skuArr['sku'];
                    A('WishButt')->disableSku($skuArr['sku']);
                }
            }
            if(!empty($disableSkus)){
            }
        }
    }
}
