<?php
/**
 * 功能：账号站点映射管理
 * @author wcx
 * v 1.0
 * 时间：2015/06/10
 *
 */
class AccountSiteRelationView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 列表
     * @return [type] [description]
     */
    public function view_list() {
        $this->smarty->assign(array(
            'siteInfo' => array_merge(array(""=>"请选择"),C("SITES")),
            //'platform' => A("Platform")->act_getList(),
            'shopInfo' => A("Shops")->act_getAllShopsBycompanyId($this->_companyid,$this->_param("platform",'1')),
            'dataList' => $this->_getList(),
            'isLimited' => array(""=>"请选择","1"=>"正常",'2'=>'受限'),
        ));
        $this->smarty->display('user/publish/accountSite/list.html');
    }
    public function _getList(){
        $where = $this->_getCondition(array(array("platform","1","`s`.platform"),"site_id","shop_id","status"));
        return A($this->getAction())->act_getList($where,$this->_companyid);
    }
}