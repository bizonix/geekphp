<?php
/**
 * 功能：店铺管理
 * @author zjr
 * v 1.0
 * 时间：2014/12/16
 *
 */
class ShopsView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        C(include WEB_PATH.'conf/order_conf.php');
    }

    /**
     * 添加店铺
     */
    public function view_addShopView(){
        $shopId    =  $this->getParam('shopId');
    	$platform  = empty($this->getParam('platform')) ? '2' : $this->getParam('platform');
        $groupInfo = A("Group")->act_getGroupInfoById(get_usercompanyid());
    	$this->smarty->assign("platform",$platform);
    	$this->smarty->assign("groupInfo",$groupInfo);
        if($shopId){
            $this->smarty->assign(A("Shops")->act_getShopInfoById($shopId));
        }
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
    	$this->smarty->display('user/shops/addShop.html');
    }

    /**
     * 店铺列表
     */
    public function view_shopListView(){
    	$list = A("Shops")->act_getCompanyShops(get_usercompanyid());
    	$this->smarty->assign($list);
        if(isset($_REQUEST['check'])){
            $this->showOperateRes();
        }
    	$this->smarty->display('user/shops/shopList.html');
    }

    /**
     * 店铺详情
     */
    public function view_shopDetailView(){
        $shopId    =  $this->getParam('shopId');
        $platform  = empty($this->getParam('platform')) ? '2' : $this->getParam('platform');
        $groupInfo = A("Group")->act_getGroupInfoById(get_usercompanyid());
        $this->smarty->assign("groupInfo",$groupInfo);
        $this->smarty->assign(A("Shops")->act_getShopInfoById($shopId));
        $this->smarty->display('user/shops/shopDetail.html');
    }

    /**
     * 保存店铺
     */
    public function view_addShop(){
    	$shopAccount 	= $this->getParam("shopAccount");
    	$platform	 	= $this->getParam("platform");
        $token          = empty($this->getParam("token")) ? array() : $this->getParam("token");
    	if(empty($shopAccount) || empty($platform) || empty($token) || empty(get_usercompanyid())){
    		redirect_to(WEB_URL."shops/addShopView/platform/{$platform}/errCode/20014");
    	}
        foreach ($token as $key => $value) {
            if(empty(trim($value))){
                unset($token[$key]);
            }else{
                $token[$key]    = trim($value);
            }
        }
        switch($platform){
            case '1' :
                 $tokenConf = json_encode($token);
                break;
            case '2' :
                $res = A("AliexpressButt")->getAppCode($shopAccount,$token['appKey'],$token['appSecret']);
                exit;
                break;
            case '4' :
                $tokenConf = $token['appKey'];
                break;
        }
        $res = A("Shops")->act_addShop($shopAccount,$platform,$tokenConf);
        if($res){
            redirect_to(WEB_URL."shops/addShopView/platform/{$platform}/errCode/200");
        }else{
            redirect_to(WEB_URL."shops/addShopView/platform/{$platform}");
        }
    }

    /**
     * 为店铺授权
     */
    public function view_updateRefrashToken(){
        $shopId = $_REQUEST['shopId'];
        A("Shops")->act_updateRefrashToken($shopId);
        exit;
    }

	/**
     * 获取账号信息通过账号和平台
     */
    public function view_getShopInfo(){
    	$shopAccount = $this->must($this->getParam('shopAccount'),"");
    	$platform	 = $this->must($this->getParam('platform'));
    	echo $this->ajaxReturn(A("Shops")->act_getShopInfo($shopAccount,$platform));
    }

    /**
     * 删除店铺
     */
    public function view_deleteShop(){
        $shopId = $this->must($this->getParam('shopId'),"");
        $deleteFlag = A("Shops")->act_deleteShop($shopId);
        redirect_to(WEB_URL."shops/shopListView/check/true");
    }

    /**
     * 判断账号是否已经存在
     */
    public function view_checkShopIsExist(){
    	$shopAccount = $this->must($this->getParam('shopAccount'),"");
    	$platform	 = $this->must($this->getParam('platform'));
    	$shopInfo = A("Shops")->act_getShopInfo($shopAccount,$platform);
    	if(empty($shopInfo)){
    		$retData = false;
    	}else{
    		$retData = true;
    	}
    	echo $this->ajaxReturn($retData);
    }
}
?>