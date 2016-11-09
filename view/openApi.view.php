<?php
/**
 * 功能：控制登录方面的一系列动作
 * @author wcx
 * v 1.0
 * 时间：2014/06/27
 *
 */
class OpenApiView extends BaseView {

    public function __construct(){
        parent::__construct();
    }
    
    /*
     * 速卖通的回调地址
     * wcx
     */
    public function view_aliApiCallback() {
        log::write("\n apiREQUEST = ".json_encode($_REQUEST));
    }

    /**
     *  接收供应商推送的产品信息
     */
    public function view_getProductsInfo(){
        echo A("ApiIntegration")->act_getProductsTemp();
        exit;
    }
}