<?php
/**
 * 功能：产品管理
 * @author wcx
 * v 1.0
 * 时间：2015/03/27
 *
 */
class UserProductsView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        C(include WEB_PATH.'conf/products_conf.php');
        C(include WEB_PATH.'conf/url_conf.php');
    }

    public function view_getProducts(){
        $res = A("UserProducts")->act_getProductsList(get_usercompanyid());
        $this->smarty->assign($res);
        $this->smarty->display("user/products/productList.html");
    }

    /**
     * 功能：获取
     */
    public function view_editGoods(){
        $goodsId = $_REQUEST['goodsId'];
        $goodsInfo = A("UserProducts")->act_getGoodsInfoById($goodsId);
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
        $this->smarty->assign($goodsInfo);
        $this->smarty->display("user/products/editGoodsInfo.html");
    }

    /**
     * 功能：获取
     */
    public function view_editGoodsPost(){
        $goodsData = $_POST;
        if(empty($goodsData)){
            redirect_to(WEB_URL."userProducts/editGoods/errCode/20014");
        }
        $res = A("UserProducts")->act_editGoods($goodsData);
        if($res){
            redirect_to(WEB_URL."userProducts/editGoods/goodsId/{$goodsData['id']}");
        }else{
            $msg = $this->collectMsg();
            redirect_to(WEB_URL."userProducts/editGoods/errCode/".$msg['errCode']);
        }
    }

    /**
     * 功能：获取类别
     */
    public function view_getCategoryByPid(){
        $pid = $_REQUEST['pid'];
        $category = A("UserProducts")->act_getCategoryByPid($pid);
        $this->ajaxReturn($category);
    }

    
}
?>