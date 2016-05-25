<?php
/**
 * ImagesView
 * 功能：用于处理图片控制
 * @author 邹军荣
 * v 1.0
 * 2015/04/19  
 */
class ImagesView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 删除临时图片
	 */
	public function view_delTmpPic(){
	    F('opensys');
	    $companyId 	= $_REQUEST['companyId'];
	    $imgName 	= $_REQUEST['imgName'];
	    $ret = vita_get_url_content2(C('IMAGE_HANDLE_SOURCE_URL')."delTmpImg/{$companyId}/{$imgName}");
	    $this->ajaxReturn($ret);
	}

}
?>