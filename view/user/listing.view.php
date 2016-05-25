<?php
/**
 * 功能：listingview
 * @author zjr
 * v 1.0
 * 时间：2015/06/28
 *
 */
class ListingView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 模板列表
     * @return [type] [description]
     */
    public function view_list() {
        $companyId = get_usercompanyid();
        $this->smarty->assign(A("Listing")->listingList($companyId));
        $this->smarty->display('user/publish/listing/list.html');
    }
    /**
     * 模板列表
     * @return [type] [description]
     */
    public function view_lsEdit() {
        $companyId  = get_usercompanyid();
        $platform   = $this->getParam('platform');
        $lsId       = $this->getParam('lsId');
        $lsInfo           = A("Listing")->getLsInfoByIds(array($lsId));
        if(empty($lsInfo['lsInfo'])){
            $this->error_404Jump('未找到该页面','/listing/list','3');
        }
        $lsInfo['lsInfo'] = $lsInfo['lsInfo'][$lsId];
        $this->smarty->assign($lsInfo);
        $this->smarty->assign("shops",M('Shops')->getUserPlatformShops($companyId,'4'));
        $location   = 'user/publish/listing/';
        switch ($platform){
            case '1':
                $location .= 'ebay/edit.html';
                break;
            case '2':
                $location .= '';
                break;
            case '3':
                $location .= '';
                break;
            case '4':
                $location .= 'wish/update.html';
        }
        $this->smarty->display($location);
    }
    
    /**
     * 上架listing
     * zjr
     */
    public function view_onlineListing(){
        $companyId  = get_usercompanyid();
        $lsIds       = $this->getParam('lsIds');
        $res = A('Listing')->onlineListing($lsIds,$companyId);
        $this->ajaxReturn($res);
    }
    
    /**
     * 下架listing
     * zjr
     */
    public function view_offlineListing(){
        $companyId  = get_usercompanyid();
        $lsIds       = $this->getParam('lsIds');
        $res = A('Listing')->offlineListing($lsIds,$companyId);
        $this->ajaxReturn($res);
    }
    
    /**
     *  同步店铺listing
     *  zjr
     */
    public function view_synShopListing(){
        $companyId  = get_usercompanyid();
        $platform   = $this->getParam('platform');
        $account    = $this->getParam('account');
        $startTime  = $this->getParam('startTime');
        $res        = A('Listing')->synShopListing($platform,$account,$startTime,$companyId);
        $this->ajaxReturn($res);
    }
}
?>