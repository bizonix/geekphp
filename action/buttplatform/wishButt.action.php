<?php
/*
* wish平台对接接口
* add by: zjr @date 20150226
*/
class WishButtAct extends CheckAct
{

    private $key; //key
    private $account; //账号

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($account='',$key='')
    {
    	/*$keyname = WEB_PATH.'/conf/scripts/keys/wish/wish_key_config.php';
    	if (file_exists($keyname)) {
    		include_once $keyname;
            //$wishKeyConfig 为 key配置数组
            if(empty($wishKeyConfig[$account])){
                exit ("未找到 $account 对应 的key");
            }
    	} else {
    		exit ("未找到key对应文件!");
    	}*/
        if(empty($account) && empty($key)){
            self::$errMsg[10008] = get_promptmsg('10008','店铺账号');
            return false;
        }
        if(empty($key)){
            $shopInfo = M("Shops")->getData("*",array("platform" => 4,"shop_account" => $account));
            if(empty($shopInfo)){
                self::$errMsg[10008] = get_promptmsg('10008','店铺');
                return false;
            }
            $key = $shopInfo[0]['token'];
        }
        // $account = '2498312215@qq.com';
        // $key = 'JHBia2RmMiQxMDAkalhGdWJXMXRyZlguWDB2cDNYc3ZKUSQyYkdXTy5GLzVnNXVpSThNWlc0SnVtOGd0UWs=';
    	$this->account = empty($account) ? 'unkown' : $account;
    	$this->key     = $key;//$wishKeyConfig[$account];
        ######################以后扩展到接口获取  end  ######################
    }

    /**
     * 根据时间段获取时间段内状态改变的订单
     */
    public function multiGetOrders($since, $limit=500, $start='')
    {
        $wishObj = F('wish.package.WishGetOrders');
        $wishObj->setConfig($this->account, $this->key);
        return $wishObj->multiGetOrders($since, $limit, $start='');
    }

    /**
     * 获取所有未发货的订单
     */
    public function retrieveUnfulfilledOrders($start='', $limit=500)
    {
        $wishObj = F('wish.package.WishGetOrders');
        $wishObj->setConfig($this->account, $this->key);
        return $wishObj->retrieveUnfulfilledOrders($start, $limit);
    }
    
    /**
     * 标记发货
     */
    public function fulFillOrders($id, $tracking_provider, $tracking_number)
    {
        $wishObj = F('wish.package.WishFullFillOrder');
        $wishObj->setConfig($this->account, $this->key);
        $ret     = $wishObj->fulFillOrders($id, $tracking_provider, $tracking_number);
        $ret     = json_decode($ret,true);
        if($ret['code'] == 1002){
            self::$errMsg["30001"] = get_promptmsg("30001");
            return false;
        }elseif($ret['code'] == 0){
            self::$errMsg["200"] = get_promptmsg("200","标记发货");
            return true;
        }else{
            self::$errMsg["30000"] = get_promptmsg("30000");
            return false;
        }
        return true;
    }
    public function modifyTracking($id,$tracking_provider,$tracking_number,$ship_note=""){
        $wishObj     = F('wish.package.WishModifyTracking');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->modifytracking($id,$tracking_provider,$tracking_number,$ship_note);
    }

    /*
     * 获取所有产品
     * zjr
     */
    public function listAllProducts($start,$limit,$since=''){
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->listAllProducts($start,$limit,$since);
    }

    /*
     * 添加主产品信息
     * zjr
     */
    public function createProduct($params){
        $needParams = array('name','description','tags','sku','inventory','price','shipping','main_image');
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->createProduct($params);
    }

    /*
     * 添加子产品信息
     * zjr
     */
    public function createProductVariation($params){
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->createProductVariation($params);
    }
    /*
     * 修改主产品
     * zjr
     */
    public function updateProduct($params){
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        if(!isset($params['parent_sku']) || empty($params['parent_sku'])){
            self::$errMsg['10007'] = get_promptmsg(10007,"parent_sku");
            return false;
        }
        $updateData = array('parent_sku' => $params['parent_sku']);
        if(isset($params['name'])) $updateData['name'] = $params['name'];
        if(isset($params['description'])) $updateData['description'] = $params['description'];
        if(isset($params['tags'])) $updateData['tags'] = $params['tags'];
        if(isset($params['brand'])) $updateData['brand'] = $params['brand'];
        if(isset($params['landing_page_url'])) $updateData['landing_page_url'] = $params['landing_page_url'];
        if(isset($params['upc'])) $updateData['upc'] = $params['upc'];
        if(isset($params['main_image'])) $updateData['main_image'] = $params['main_image'];
        if(isset($params['extra_images'])) $updateData['extra_images'] = $params['extra_images'];
        return $wishObj->updateProduct($updateData);
    }
    /*
     * 修改子产品属性
     * zjr
     */
    public function updateVariant($params){
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        if(!isset($params['sku']) || empty($params['sku'])){
            self::$errMsg['10007'] = get_promptmsg(10007,"sku");
            return false;
        }
        $updateData = array('sku' => $params['sku']);
        if(isset($params['inventory'])) $updateData['inventory'] = $params['inventory'];
        if(isset($params['price'])) $updateData['price'] = $params['price'];
        if(isset($params['shipping'])) $updateData['shipping'] = $params['shipping'];
        if(isset($params['enabled'])) $updateData['enabled'] = $params['enabled'];
        if(isset($params['size'])) $updateData['size'] = $params['size'];
        if(isset($params['color'])) $updateData['color'] = $params['color'];
        if(isset($params['msrp'])) $updateData['msrp'] = $params['msrp'];
        if(isset($params['shipping_time'])) $updateData['shipping_time'] = $params['shipping_time'];
        if(isset($params['main_image'])) $updateData['main_image'] = $params['main_image'];
        return $wishObj->updateVariant($updateData);
    }
    /*
     * 上架spu系列产品
     * zjr
     */
    public function enableParentSku($spu){
        $params     = array('parent_sku' => $spu);
        $wishObj    = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->enableParentSku($params);
    }
    /*
     * 下架spu系列产品
     * zjr
     */
    public function disableParentSku($spu){
        $params     = array('parent_sku' => $spu);
        $wishObj     = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->disableParentSku($params);
    }
    /*
     * 上架sku产品
     * zjr
     */
    public function enableSku($sku){
        $params     = array('sku' => $sku);
        $wishObj    = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->enableSku($params);
    }
    /*
     * 下架sku产品
     * zjr
     */
    public function disableSku($sku){
        $params     = array('sku' => $sku);
        $wishObj    = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->disableSku($params);
    }
    /*
     * 修改产品sku的库存
     * zjr
     */
    public function updateInventory($sku,$inventory){
        $params     = array('sku' => $sku,'inventory' => $inventory);
        $wishObj    = F('wish.package.WishProducts');
        $wishObj->setConfig($this->account,$this->key);
        return $wishObj->disableSku($params);
    }

}
?>