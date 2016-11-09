
<?php
/**
 * TrackView
 * 功能：用于物流跟踪号的处理控制
 * @author wcx
 * v 1.0
 * 2014/06/26  
 */
class TrackView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}

	/**
	 * 
	 */
	public function view_index() {
		$this->smarty->display('user/trans/getTrackings.html');
	}

	public function view_getTrackings() {
		$trackNumber  = $_REQUEST["trackNumber"];
		$list = A("Track")->act_getTrackings($trackNumber);
		$this->smarty->assign($list);
		$this->smarty->display('user/trans/getTrackings.html');
	}

}
?>