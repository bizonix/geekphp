<?php
/**
 * 类名：OrderAct
 * 功能：订单管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2014/12/16
 * errCode：
 */ 
class EbayTemplateAct extends CheckAct {
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * 模板列表
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     */
    public function act_templateList(){
        $sort = "order by id";
        $templateData = M($this->act_getModel())->getData("*",$this->_getwhere(),$sort,$this->page, $this->perpage);
        foreach($templateData as $k=>$v){
            $templateData[$k]['ready_num'] = 1;
        }
        return $templateData;
    }
    private function _getwhere(){
        return $this->_getCondition(array("company_id","spu"));
    }
    public function getCategory($siteId=0,$pid=0){
        //
        $cache = C("CAHCE_FILE_DIR")."EbayGoodsCategory/{$siteId}/{$pid}.'log'";
        $time = time();
        $content = file_get_contents($cache);
        if(empty($content)){
            $content = A("EbayButt")->runOrigin($fun,$account,$param);
        }else{
            $content = json_decode($content,true);
            if($time-$content['update_time']>2592000){//缓存一个月失效
                $tmp = A("EbayButt")->runOrigin($fun,$account,$param);
                if(!empty($tmp)){
                    $content = $tmp;
                }
            }
        }
        return $content;
    }
    
}
