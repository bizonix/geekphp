<?php
/**
 * 功能：Wish Listing管理
 * @author wcx
 * v 1.0
 * 时间：2015/06/29
 *
 */
class WishListingView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 修改Listing信息
     * @return [type] [description]
     */
    public function view_updateListing() {
        $companyId = get_usercompanyid();
        $this->ajaxReturn(A('WishListing')->updateListingData($_REQUEST,$companyId));
    }
    
    
}
?>