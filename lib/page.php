<?php
	class Page {
		private $total; //数据表中总记录数
		private $listRows; //每页显示行数
		private $limit;
		private $uri;
		private $pageNum; //总页数
		private $config;
		private $listNum=8;
		private $language;//语言种类
		/*
		 * $total 
		 * $listRows
		 */
		public function __construct($total=0, $listRows=10, $pa="",$lang="CN"){
			$this->total=$total;
			$this->listRows=$listRows;
			$this->uri=$this->getUri($pa);
			$this->language=$lang;
			if($this->language=='CN')
			{   
				$this->config=array('header1'=>" 共有",'header2'=>"个记录 ","current"=>" 本页显示","recode"=>"记录 ","prev"=>"上一页", "next"=>"下一页", "first"=>"首页", "last"=>"末页","goPage"=>'跳转',"page"=>"页 ");
			}
			else if($this->language=='EN')
			{
				$this->config=array('header1'=>" There are ",'header2'=>" recodes ", "current"=>" CurrentShow ","recode"=>" recodes ","prev"=>"← Previous", "next"=>"Next →", "first"=>"first", "last"=>"last","goPage"=>'GO',"page"=>"Pages ");
			}
			else if($this->language=='TW')
			{
				$this->config=array('header1'=>" 共有",'header2'=>"個記錄 ","current"=>" 本頁顯示","recode"=>"記錄 ","prev"=>"上一頁", "next"=>"下一頁", "first"=>"首頁", "last"=>"末頁","goPage"=>'跳轉',"page"=>"頁 ");
			}
			$page=!empty($_GET["page"]) ? intval($_GET["page"]) : 1;//当前页数
			if(ceil($total/$listRows) < $page || $page < 1)//判断当前页数是否“正常”
			{
				$this->page=1;
			}else{
				$this->page=$page;
			}
			$this->pageNum=ceil($this->total/$this->listRows);//
			$this->limit=$this->setLimit();
		}
		
		//显示每页从第几条到第几条数据
		private function setLimit(){
			return "Limit ".($this->page-1)*$this->listRows.", {$this->listRows}";
		}

		private function getUri($pa){
			if(stripos($_SERVER["REQUEST_URI"] , "/page/") !== false) {
				$_SERVER["REQUEST_URI"] = preg_replace('/\/page\/[0-9]*/', '', $_SERVER["REQUEST_URI"]);
			}
			$url=$_SERVER["REQUEST_URI"].(strpos($_SERVER["REQUEST_URI"], '?')?'':"").$pa;
			$noChack = explode("/", $_SERVER["REQUEST_URI"]);
			$noChack[0] = 'mod';
			$noChack[]	= 'act';
			foreach ($_REQUEST as $key => $value) {
				if(!in_array($key, $noChack) && !empty($value)){
					$url .= "/".$key."/".$value;
				}
			}
			/*if(isset($parse["query"])){
				parse_str($parse['query'],$params);
				unset($params["page"]);
				$url=$parse['path'].'?'.http_build_query($params);				
			}*/

			return $url;
		}

		public function __get($args){
			if($args=="limit")
				return $this->limit;
			else
				return null;
		}

		private function start(){
			if($this->total==0)
				return 0;
			else
				return ($this->page-1)*$this->listRows+1;
		}

		private function end(){
			return min($this->page*$this->listRows,$this->total);
		}

		private function first(){
		    $html =   '';
			if($this->page==1)
				$html.='';
			else
				$html.="<li><a class='prev' href='{$this->uri}/page/1'>{$this->config['first']}</a></li>";
			return $html;
		}

		private function prev(){
		    $html =   '';
			if($this->page==1)
				$html.='';
			else
				$html.="<li class='prev'><a href='{$this->uri}/page/".($this->page-1)."'>{$this->config["prev"]}</a></li>";
			return $html;
		}

		private function pageList(){
		    
			$linkPage="";
			$inum=floor($this->listNum/4);
		    
			for($i=$inum; $i>=1; $i--){
				$page=$this->page-$i;

				if($page<1)
					continue;

				$linkPage.="<li><a href='{$this->uri}/page/{$page}'>{$page}</a></li>";

			}
		
			$linkPage.="<li class='active'><a href='{$this->uri}/page/{$this->page}'>{$this->page}</a></li>";	
            
			for($i=1; $i<=$inum; $i++){
				$page=$this->page+$i;
				if($page<=$this->pageNum)
					$linkPage.="<li><a href='{$this->uri}/page/{$page}'>{$page}</a></li>";
				else
					break;
			}
			return $linkPage;
		}

		private function next(){
		    $html =   '';
			if($this->page==$this->pageNum)
				$html.='';
			else
				$html.="<li class='next'><a href='{$this->uri}/page/".($this->page+1)."'>{$this->config['next']}</a></li>";
			return $html;
		}
		
		//判断是否是最后一页
		private function last(){
		    $html =   '';
			if($this->page==$this->pageNum)
				$html.='';
			else
				$html.="<li class='next'><a href='{$this->uri}/page/".($this->pageNum)."'>{$this->config['last']}</a></li>";
			return $html;
		}
		
		//跳转到那一页
		private function goPage($go){
			//return '<span class="goPage"><input class="goPage-text" type="text" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>'.$this->pageNum.')?'.$this->pageNum.':this.value;location=\''.$this->uri.'&page=\'+page+\'\'}" value="'.$this->page.'"><input class="jumpbt" type="button" value="'.$go.'" onclick="javascript:var page=(this.previousSibling.value>'.$this->pageNum.')?'.$this->pageNum.':this.previousSibling.value;location=\''.$this->uri.'&page=\'+page+\'\'" class="int"></span>';
			return '';
		}
		
		//输出样式
		public function fpage($display=array(4,5,6,7,8)){
			$html[0]=$this->config["header1"].$this->total.$this->config["header2"];
			$html[1]=$this->config["current"].($this->end()-$this->start()+1).$this->config["recode"];
			$html[2]=$this->config["current"].$this->start().'-'.$this->end().$this->config["recode"];
			$html[3]=$this->page.'/'.$this->pageNum.$this->config["page"];
			
			$html[4]=$this->first();
			$html[5]=$this->prev();
			$html[6]=$this->pageList();
			$html[7]=$this->next();
			$html[8]=$this->last();
			$html[9]=$this->goPage($this->config["goPage"]);
			$fpage='<div class="row">';
			$shows='<div class="span6"><div class="dataTables_info" id="example2_info">';
			$list='<div class="span6"><div class="dataTables_paginate paging_bootstrap pagination"><ul>';
			foreach($display as $index){
				if($index < 4){
					$shows	.= $html[$index];
				}else{
					$list	.= $html[$index];
				}
			}
			$shows	.= '</div></div>';
			$list	.= '</ul></div></div>';
			$fpage	.= $shows.$list.'</div>';
			return $fpage;
		}	
	}