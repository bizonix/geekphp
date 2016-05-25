<?php
class CategoryProductView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function view_index() {
		$categorys	= A("CategoryProduct")->act_getCategory();
		$this->smarty->assign("categorys",$categorys);
        $this->smarty->display('product/category/categoryProduct.html');
	}
}
?>