<?php
/**
 * 类名：TemplateAct
 * 功能：范本管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/5/20
 * errCode：
 */ 
class TemplateAct extends CheckAct {
    private $params;
    public function __construct(){
        parent::__construct();
        $this->params = array(
            'company_id'    => isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : 0,
            'platform'      => isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 0,
            'name'          => isset($_REQUEST['name']) ? $_REQUEST['name'] : '',
            'spu'           => isset($_REQUEST['spu']) ? $_REQUEST['spu'] : '',
            'create_user'   => isset($_REQUEST['create_user']) ? $_REQUEST['create_user'] : '',
        );
    }
    
    /**
     * 模板列表
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     */
    public function act_templateList($where){
        $sort = "order by id";
        $templateData = M($this->act_getModel())->getData("*",$where,$sort,$this->page, $this->perpage);
        foreach($templateData as $k=>$v){
            $templateData[$k]['ready_num'] = 1;
        }
        return $templateData;
    }    
    
    /**
     * 模板列表
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     * wcx
     */
    public function templateList($companyId){
        $sort     = "order by id";
        $this->params['company_id'] = $companyId;
        $where    = $this->_buildWhere($this->params);
        $count	  = M($this->act_getModel())->getDataCount($where);
        $p 		  = new Page ($count,10);
        $tpData   = M($this->act_getModel())->getData("*",$where,$sort,$this->page, $this->perpage);
        $page 		= $p->fpage();
        $platforms  = M("Platform")->getAllData("id,platform_en_name,platform_cn_name","type IN (1,2,3)","id");
        $shops      = M('Shops')->getAllData('id,shop_account',array('belong_company' => $companyId),'id');
        //解析店铺账号
        foreach ($tpData as &$tp){
            $account = '';
            if(!empty($tp['account'])){
                $account = json_decode($tp['account'],true);
                foreach ($account as &$acc){
                    if(isset($shops[$acc])) $acc = $shops[$acc]['shop_account'];
                }
            }
            if(!empty($tp['listed_account'])){
                $listed_account = json_decode($tp['listed_account'],true);
                foreach ($listed_account as &$acc){
                    if(isset($shops[$acc])) $acc = $shops[$acc]['shop_account'];
                }
            }
            $tp['account'] = !empty($account) ? implode(', ',$account) : '-';
            $tp['listed_account'] = !empty($listed_account) ? implode(', ',$listed_account) : '-';
        }
        return array('tpData'=>$tpData,'platforms'=>$platforms,'shops' => $shops,'page'=>$page,'count'=>$count);
    }
    
    /**
     * 验证范本是否存在
     * wcx
     */
    public function checkTemplateIsExist($tpName){
        if(empty($tpName)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"范本名称");
			return false;
        }
        $tpInfo = M('Template')->getSingleData("*",array("name" => $tpName));
        if(empty($tpInfo)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"范本");
			return false;
        }else{
            return $tpInfo;
        }
    }
    
    /**
     * 获取范本的信息 通过范本ID
     * param $idArr = array('1231','32132');
     * return array(
     *      '2' => array(
     *          "tpMain"    => array(....),
     *          "tpDetail"  => array(....),
     *      )
     * );
     * by wcx
     * 2015-06-27
     */
    public function getTpInfoByIds($idArr){
        if(empty($idArr)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"范本ID");
            return false;
        }
        $retTpArr = array();
        $tpInfos = M('Template')->getAllData("*","id IN ('".implode("','",$idArr)."')");
        if(empty($tpInfos)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"范本");
            return false;
        }
        //获取所有平台
        $platforms  = M("Platform")->getAllData("id,platform_en_name,platform_cn_name","type IN (1,2,3)","id");
        foreach($tpInfos as &$tpmain){
            $tableName = $platforms[$tpmain['platform']]['platform_en_name'].'TemplateDetail';
            M($tableName)->setTablePrefix('_'.$tpmain['table_suffix']);
            $tpDetail = M($tableName)->getSingleData("*",array("tp_id" => $tpmain['id']));
            $tpmain['account']  = !empty($tpmain['account']) ? json_decode($tpmain['account'],true) : array();
            if(!empty($tpDetail)){
                $tpDetail['tags']           = json_decode($tpDetail['tags'],true);
                if(!empty($tpDetail['tags'])){
                    $tpDetail['tags'] = implode(',',$tpDetail['tags']);
                }
                if(!empty($tpDetail['variations'])){
                    $tpDetail['variations'] = json_decode($tpDetail['variations'],true);
                }
                if(!empty($tpDetail['main_images'])){
                    $tpDetail['main_images']      = json_decode($tpDetail['main_images'],true);
                }
                if(!empty($tpDetail['extra_images'])){
                    $tpDetail['extra_images']      = json_decode($tpDetail['extra_images'],true);
                }
                $retTpArr[$tpmain['id']]    = array('main' => $tpmain,'detail' => $tpDetail);
            }
        }
        return array("tpInfo" => $retTpArr);
    }
    
    /**
     * 功能： 通过api制作范本
     */
    public function apiToTp($params){
        if(!isset($params['platform']) && empty($params['platform'])){
            self::$errMsg[10007]   =   get_promptmsg(10007,"platform");
            return false;
        }
        if(!isset($params['flag']) && empty($params['flag'])){
            self::$errMsg[10007]   =   get_promptmsg(10007,"flag");
            return false;
        }
        if(!isset($params['appkey']) && empty($params['appkey'])){
            self::$errMsg[10007]   =   get_promptmsg(10007,"appkey");
            return false;
        }
        if(!isset($params['startTime']) && empty($params['startTime'])){
            self::$errMsg[10007]   =   get_promptmsg(10007,"startTime");
            return false;
        }
        $companyId = get_usercompanyid();
        if(empty($companyId)){
            self::$errMsg[10007]   =   get_promptmsg(10007,"公司");
            return false;
        }
        
        if($params['flag'] == "appkey"){
            $appkey = $params['appkey'];
        }
        $shopAccount = '';
        $limit = 500;
        $since = date('Y-m-d',strtotime($params['startTime']));
        $start = 0;
        $tpName = 'tp_wish_';
        $ret   = array();
        //获取结果
        do{
            A("WishButt")->setConfig($shopAccount , $appkey);
            $res = array();
            $res = A("WishButt")->listAllProducts($start,$limit,$since);
            $res = json_decode($res,true);
            //处理结果
            if(!empty($res["data"])){
                foreach ($res["data"] as $product) {
                    $params = array(
                        'company_id'        => $companyId,
                        'tp_name'           => $tpName.$product['Product']['parent_sku'],
                        'parent_sku'        => $product['Product']['parent_sku'],
                        'name'              => $product['Product']['name'],
                        'account'           => $shopAccount,
                        'tags'              => implode(',',$this->buildWishTags($product['Product']['tags'])),
                        'comVar'            => $this->buildWishComVarTags($product['Product']['variants']),
                        'main_images'       => array($product['Product']['main_image']),
                        'extra_images'      => explode('|', $product['Product']['extra_images']),
                        'description'       => $product['Product']['description'],
                    );
                    $saveRes = A('WishTemplate')->saveTemplateData($params,$companyId);
                    if($saveRes){
                        $ret[$product['Product']['parent_sku']] = array('200','同步成功！');
                    }else{
                        $ret[$product['Product']['parent_sku']] = array('2001','同步失败！');
                    }
                }
            }
            //切换到下一页
            $start += $limit;
        }while(!empty($res["paging"]) && !empty($res["paging"]['next']));
        
    }
    
    protected function buildWishTags($tagsArr){
        $ret = array();
        if(!empty($tagsArr)){
            foreach ($tagsArr as $tag){
                $ret[] = $tag['Tag']['name'];
            }
        }
        return $ret;
    }
    
    protected function buildWishComVarTags($variants){
        $comVar = array();
        if(!empty($variants)){
            foreach ($variants as $k=>$variant){
                foreach ($variant['Variant'] as $kk=>$var){
                    if(in_array($kk, array('sku','inventory','price','shipping','color','size','msrp','shipping_time'))){
                        $comVar[$kk][] = $var;
                    }
                }
            }
        }
        return $comVar;
    }
    
}
