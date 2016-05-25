<?php
/**
 * 功能：范本view
 * @author zjr
 * v 1.0
 * 时间：2015/06/08
 *
 */
class TemplateView extends BaseView {
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
        $this->smarty->assign(A("Template")->templateList($companyId));
        $this->smarty->display('user/publish/template/list.html');
    }
    /**
     * 模板列表
     * @return [type] [description]
     */
    public function view_tpEdit() {
        $companyId  = get_usercompanyid();
        $platform   = $this->getParam('platform');
        $tpId       = $this->getParam('tpId');
        $tpInfo           = A("Template")->getTpInfoByIds(array($tpId));
        if(empty($tpInfo['tpInfo'])){
            $this->error_404Jump('未找到该页面','/template/list','3');
        }
        $tpInfo['tpInfo'] = $tpInfo['tpInfo'][$tpId];
        $this->smarty->assign($tpInfo);
        $this->smarty->assign("shops",M('Shops')->getUserPlatformShops($companyId,'4'));
        $location   = 'user/publish/template/';
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
     * 模板列表
     * @return [type] [description]
     */
    public function view_addWishTemplate() {
        //获取wish店铺
        $companyId = get_usercompanyid();
        $this->smarty->assign("shops",M('Shops')->getUserPlatformShops($companyId,'4'));
        $this->smarty->assign("company_id",$companyId);
        $this->smarty->display('user/publish/template/wish/wishEdit.html');
    }
    
    /**
     * wish范本
     */
    public function view_templateWishEdit(){
        //获取wish店铺
        $companyId = get_usercompanyid();
        $goodsBasicId = $this->getParam('goodsBasicId');
        $this->smarty->assign("shops",M('Shops')->getUserPlatformShops($companyId,'4'));
        $this->smarty->assign(A('GoodsBasic')->getGoodsBasicById($companyId,$goodsBasicId));
        $this->smarty->display('user/publish/template/wish/wishEdit.html');
    }
    
    /**
     * 验证范本是否存在
     * zjr
     */
    public function view_checkTemplateIsExist(){
        $tp_name = $this->getParam('tp_name');
        $res = A('Template')->checkTemplateIsExist($tp_name);
        die(empty($res) ? false : true);
    }
    
    /**
     * 制作范本
     * zjr
     */
    public function view_apiToTp(){
        $platform   = $this->getParam('platform');
        $flag       = $this->getParam('flag');
        $appkey     = $this->getParam('appkey');
        $startTime  = $this->getParam('startTime');
        $data = array(
            'platform'  => $platform,
            'flag'      => $flag,
            'appkey'    => $appkey,
            'startTime' => $startTime,
        );
        $res = A('Template')->apiToTp($data);
        die(empty($res) ? false : true);
    }
}
?>