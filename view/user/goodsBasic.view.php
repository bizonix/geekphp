<?php
/**
 * 功能：基础信息管理
 * @author wcx
 * v 1.0
 * 时间：2015/05/31
 *
 */
class GoodsBasicView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * 产品列表
     * @return [type] [description]
     */
    public function view_goodsBasic() {
        $companyId = get_usercompanyid();
        $companyId = 1;
        $this->smarty->assign(A('GoodsBasic')->getGoodsBasicList($companyId,$_REQUEST));
        $this->smarty->display('user/publish/basic/goodsBasicList.html');
    }
    /**
     * 产品编辑
     * @return [type] [description]
     */
    public function view_goodsBasicEdit() {
        $goodsBasicId   = $_REQUEST['goodsBasicId'];
        $companyId      = get_usercompanyid();
        $companyId      = 1;
        $this->smarty->assign(A('GoodsBasic')->getGoodsBasicById($companyId,$goodsBasicId));
        $this->smarty->display('user/publish/basic/goodsBasicEdit.html');
    }
    
    public function view_fetchImages(){
        $url = $_REQUEST['url'];
        
    }
    
    public function view_saveBasicInfo(){
        $this->ajaxReturn(A("GoodsBasic")->saveBasicInfo($_REQUEST));
    }
}
?>