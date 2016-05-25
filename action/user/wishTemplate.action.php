<?php
/**
 * 类名：WishTemplateAct
 * 功能：范本管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/06/10
 * errCode：
 */ 
class WishTemplateAct extends CheckAct {
    private $tpId = 0;
    public function __construct(){
        parent::__construct();
    }
    /**
     * 保存模板
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     */
    public function saveTemplateData($params,$companyId){
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
	    $mainSaveData = M('Template')->buildSaveData($mainParams);
	    $res = M('Template')->saveTemplateData($mainSaveData,$wishDetailsParams);
	    if(empty($res)){
            self::$errMsg['10001'] = get_promptmsg(10001,"更新");
            return false;
	    }
	    return $res;
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
        $tpId = $this->saveTemplateData($params,$companyId);
        if(empty($tpId)){
            return false;
        }
        //开始刊登
        $listingRes = $this->listingDataToWish($tpId,$companyId);
        if(empty($listingRes)){
            return false;
        } 
        //拆解刊登结果信息
        foreach ($listingRes as $account => $skuRes){
            foreach ($skuRes as $sku => $res){
                //如果刊登成功，就保存到listing表中，并且修改记录sku listing表
                if($res['code'] != "0" && $res['code'] != "1000"){
                    //失败的sku从中剔除
                    $clears = array();
                    foreach($params['comVar'] as $k => $v){
                        foreach ($v as $kk=> $vv){
                            if($k == 'sku' && $vv == $sku){
                                unset($params['comVar'][$k][$kk]);
                                $clears[] = $kk;
                            }elseif($k != 'sku' && in_array($kk,$clears)){
                                unset($params['comVar'][$k][$kk]);
                            }
                        }
                        $params['comVar'][$k] = array_values($params['comVar'][$k]);
                    }
                }
            }
            //保存wish listing
            $params['account'] = $account;
            $params['tp_id']   = $tpId;
            $saveListingRes = A('WishListing')->saveListingData($params,$companyId);
        }
        return $saveListingRes;
    }
    
    /**
     * wish刊登-核心刊登方法
     * zjr
     */
    public function listingDataToWish($tpId,$companyId){
        if(empty($tpId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"范本ID");
            return false;
        }
        if(empty($companyId)){
            self::$errMsg['10007'] = get_promptmsg(10007,"公司ID");
            return false;
        }
        //获取范本信息
        $tpMainData     = M('Template')->getSingleData('*',array("id" => $tpId));
        if(empty($tpMainData)){
            self::$errMsg['10007'] = get_promptmsg(10007,"范本信息");
            return false;
        }
        //判断是否选择了wish账号
        $account = json_decode($tpMainData['account'],true);
        if(empty($account)){
            self::$errMsg['10007'] = get_promptmsg(10007,"wish账号");
            return false;
        }
        $wishShops = M('Shops')->getAllData('*',"belong_company = {$companyId} and platform = '4' and id IN ('".implode("','",$account)."')",'id');
        if(empty($wishShops)){
            self::$errMsg['10007'] = get_promptmsg(10007,"wish店铺");
            return false;
        }
        M('WishTemplateDetail')->setTablePrefix('_'.$tpMainData['table_suffix']);
        $tpDetailData   = M('WishTemplateDetail')->getSingleData('*',array('tp_id' => $tpId,'company_id' => $tpMainData['company_id']));
        if(empty($tpDetailData)){
            self::$errMsg['10007'] = get_promptmsg(10007,"范本详情信息");
            return false;
        }
        
        //验证必填信息
        if(empty($tpDetailData['parent_sku'])){
            self::$errMsg['10007'] = get_promptmsg(10007,"主料号");
            return false;
        }
        
        $tagArr = json_decode($tpDetailData['tags'],true);
        if(empty($tagArr)){
            self::$errMsg['10007'] = get_promptmsg(10007,"标签");
            return false;
        }
        
        $variations = json_decode($tpDetailData['variations'],true);
        if(empty($variations)){
            self::$errMsg['10007'] = get_promptmsg(10007,"多属性");
            return false;
        }
        
        if(empty($tpDetailData['description'])){
            self::$errMsg['10007'] = get_promptmsg(10007,"描述信息");
            return false;
        }
        
        $main_images = json_decode($tpDetailData['main_images'],true);
        if(empty($main_images)){
            self::$errMsg['10007'] = get_promptmsg(10007,"描述信息");
            return false;
        }
        
        $extra_images = json_decode($tpDetailData['extra_images'],true);
        $var_listing = array();
        $loop = 0;
        foreach ($variations as $variant) {
            //必填项不存在则跳过
            if(empty($variant['sku']) || empty($variant['inventory']) || empty($variant['price']) || empty($variant['shipping'])){
                continue;
            }
            $product = $product_var = array();
            if($loop == 0){
                $product = array(
                    'name'				=> $tpDetailData['name'],
                    'main_image'		=> $main_images[$loop],
                    'sku'				=> $variant['sku'],
                    'shipping'			=> $variant['shipping'],
                    'tags'				=> implode(",", $tagArr),
                    'description'		=> $tpDetailData['description'],
                    'inventory'			=> $variant['inventory'],
                    'parent_sku'		=> $tpDetailData['parent_sku'],
                    'price'		        => $variant['price'],
                );
                if(isset($variant['color'])) $product['color'] = $variant['color'];
                if(isset($variant['size'])) $product['size'] = $variant['size'];
                if(isset($variant['msrp'])) $product['msrp'] = $variant['msrp'];
                if(isset($variant['shipping_time'])) $product['shipping_time'] = $variant['shipping_time'];
                if(isset($tpDetailData['brand'])) $product['brand'] = $tpDetailData['brand'];
                if(isset($variant['landing_page_url'])) $product['landing_page_url'] = '';
                if(isset($tpDetailData['upc'])) $tpDetailData['upc'] = $tpDetailData['upc'];
                if(!empty($extra_images)) $product['extra_images'] = implode("|",$extra_images);
                $var_listing[] = $product;
                //刊登主产品
                foreach ($wishShops as $accId=>$shop){
                    $listResMain = array();
                    A("WishButt")->setConfig($shop['shop_account'] , $shop['token']);
                    try {
                        $listResMain = A("WishButt")->createProduct($product);
                        $listResMain = json_decode($listResMain,true);
                    } catch (Exception $e) {
                        $listResMain = array();
                    }
                    if(!empty($listResMain)){
                        $retArr[$accId][$variant['sku']] = array('code' => $listResMain['code'],'message' => $listResMain['message']);
                    }else{
                        $retArr[$accId][$variant['sku']] = array('code' => '6001','message' => '网络失败');
                    }
                }
            }else{
                $product_var = array(
                    'parent_sku'	=> $tpDetailData['parent_sku'],
                    'sku'			=> $variant['sku'],
                    'inventory'		=> $variant['inventory'],
                    'price'			=> $variant['price'],
                    'shipping'		=> $variant['shipping'],
                );
                if(isset($variant['color'])) $product_var['color'] = $variant['color'];
                if(isset($variant['size'])) $product_var['size'] = $variant['size'];
                if(isset($variant['msrp'])) $product_var['msrp'] = $variant['msrp'];
                if(isset($variant['shipping_time'])) $product_var['shipping_time'] = $variant['shipping_time'];
                $var_listing[] = $product_var;
                //刊登子产品
                foreach ($wishShops as $accId=>$shop){
                    $listResVar = array();
                    A("WishButt")->setConfig($shop['shop_account'] , $shop['token']);
                    try {
                        $listResVar = A("WishButt")->createProductVariation($product_var);
                        $listResVar = json_decode($listResVar,true);
                    } catch (Exception $e) {
                        $listResVar = array();
                    }
                    if(!empty($listResVar)){
                        $retArr[$accId][$variant['sku']] = array('code' => $listResVar['code'],'message' => $listResVar['message']);
                    }else{
                        $retArr[$accId][$variant['sku']] = array('code' => '6001','message' => '网络失败');
                    }
                }
            }
            $loop++;
        }
        return $retArr;
    }
    
    /**
     * 获取tpId
     * zjr
     */
    public function getTpId(){
        return $this->tpId;
    }
}
