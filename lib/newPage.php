<?php
/**
 * 新分页类
 * @author jbf
 */

class NewPage {

	// 分页栏每页显示的页数
	public $rollPage = 5;
	// 页数跳转时要带的参数
	public $parameter  ;
	// 分页URL地址
	public $url     =   '';
	// 默认列表每页显示行数
	public $listRows = 20;
	// 起始行数
	public $firstRow    ;
	// 分页总页面数
	protected $totalPages  ;
	// 总行数
	protected $totalRows  ;
	// 当前页数
	protected $nowPage    ;
	// 分页的栏的总页数
	protected $coolPages   ;
	// 分页显示定制
	protected $config  =    array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>'<li class="orderBills_page_jl">%totalRow% %header% %nowPage%/%totalPage% 页</li> %upPage% <!-- %first%  %prePage% -->  %linkPage% <!-- %nextPage% -->  %downPage% <!-- %end% -->');
	// 默认分页变量名
	protected $varPage;
	protected $topTheme = '%totalRow% %header%';

	/**
	 * @access public
	 * @param array $totalRows  总的记录数
	 * @param array $listRows  每页显示记录数
	 * @param array $parameter  分页跳转的参数
	 */
	public function __construct($totalRows,$listRows='',$parameter='',$url='') {
		$this->totalRows    =   $totalRows;
		$this->parameter    =   $parameter;
		$this->varPage      =   'page' ;
		if(!empty($listRows)) {
			$this->listRows =   intval($listRows);
		}
		$this->totalPages   =   ceil($this->totalRows/$this->listRows);     //总页数
		$this->coolPages    =   ceil($this->totalPages/$this->rollPage);
		$this->nowPage      =   !empty($_GET[$this->varPage])?intval($_GET[$this->varPage]):1;
		if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
			$this->nowPage  =   $this->totalPages;
		}
		$this->firstRow     =   $this->listRows*($this->nowPage-1);
	}
	
	public function setVarPage($varPage) {
		$this -> varPage = $varPage;
	}

	public function setConfig($name,$value) {
		if(isset($this->config[$name])) {
			$this->config[$name]    =   $value;
		}
	}
	
	/**
	 * 构建网址
	 * @return string
	 * @author jbf
	 */
	protected function buildURL() {
		$url = '/index.php?';
		if (!empty($this -> parameter) && is_array($this -> parameter)) {
			foreach ($this -> parameter AS $key => $value) {
				if ($value !== '' && $value !== null && $key != 'submit' && $key != 'page') $url .= $key . '='.urlencode($value) . '&amp;';
			}
		}
		
		return $url .= $this -> varPage . '=__PAGE__';
	}

	/**
	 * 分页显示输出
	 * @access public
	 * @author jbf
	 */
	public function show() {
		if(0 == $this->totalRows) return '';
		$p              =   $this->varPage;
		$nowCoolPage    =   ceil($this->nowPage/$this->rollPage);
		
		$url = self::buildURL();
		
		//上下翻页字符串
		$upRow          =   $this->nowPage-1;
		$downRow        =   $this->nowPage+1;
		if ($upRow>0){
			$upPage     =   "<li class=\"orderBills_page_on orderBills_page_color\"><a href='".str_replace('__PAGE__',$upRow,$url)."'>".$this->config['prev']."</a></li>";
		}else{
			$upPage     =   '';
		}

		if ($downRow <= $this->totalPages){
			$downPage   =   "<li class=\"orderBills_page_down orderBills_page_color\"><a href='".str_replace('__PAGE__',$downRow,$url)."'>".$this->config['next']."</a></li>";
		}else{
			$downPage   =   '';
		}
		// << < > >>
		if($nowCoolPage == 1){
			$theFirst   =   '';
			$prePage    =   '';
		}else{
			$preRow     =   $this->nowPage-$this->rollPage;
			$prePage    =   "<li class=\"orderBills_page_dian orderBills_page_color\"><a href='".str_replace('__PAGE__',$preRow,$url)."' >上".$this->rollPage."页</a></li>";
			$theFirst   =   "<li class=\"orderBills_page_down orderBills_page_color\"><a href='".str_replace('__PAGE__',1,$url)."' >".$this->config['first']."</a></li>";
		}
		if($nowCoolPage == $this->coolPages){
			$nextPage   =   '';
			$theEnd     =   '';
		}else{
			$nextRow    =   $this->nowPage+$this->rollPage;
			$theEndRow  =   $this->totalPages;
			$nextPage   =   "<li class=\"orderBills_page_dian orderBills_page_color\"><a href='".str_replace('__PAGE__',$nextRow,$url)."' >下".$this->rollPage."页</a></li>";
			$theEnd     =   "<li class=\"orderBills_page_down orderBills_page_color\"><a href='".str_replace('__PAGE__',$theEndRow,$url)."' >".$this->config['last']."</a></li>";
		}
		// 1 2 3 4 5
		$linkPage = "";
		for($i=1;$i<=$this->rollPage;$i++){
			$page       =   ($nowCoolPage-1)*$this->rollPage+$i;
			if($page!=$this->nowPage){
				if($page<=$this->totalPages){
					$linkPage .= "<li class=\"orderBills_page_ty orderBills_page_color orderBills_page_two\"><a href='".str_replace('__PAGE__',$page,$url)."'>&nbsp;".$page."&nbsp;</a></li>";
				}else{
					break;
				}
			}else{
				if($this->totalPages != 1){
					$linkPage .= "<li class=\"orderBills_page_ty orderBills_page_color orderBills_page_two orderBills_page_bg\"><span class='current'>".$page."</span>";
				}
			}
		}
		$pageStr     =   str_replace(
				array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
				array($this->config['header'], $this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$this->config['theme']);
		return $pageStr;
	}

}