<?php
/**
 * 功能：Wish范本管理
 * @author zjr
 * v 1.0
 * 时间：2015/06/10
 *
 */
class WishTemplateView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 保持范本信息
     * @return [type] [description]
     */
    public function view_saveTemplateInfo() {
        $companyId = get_usercompanyid();
        $this->ajaxReturn(A('WishTemplate')->saveTemplateData($_REQUEST,$companyId));
    }
    
    /**
     * 保存并刊登范本
     * @return [type] [description]
     */
    public function view_listingData() {
        $companyId = get_usercompanyid();
        $this->ajaxReturn(A('WishTemplate')->listingData($_REQUEST,$companyId));
    }
    
}
?>