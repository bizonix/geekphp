<?php
/**
 * 类名：TemplateAct
 * 功能：范本管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/5/20
 * errCode：
 */ 
class AccountSiteRelationAct extends CheckAct {
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * 模板列表
     * @param  string $orderdatas [description]
     * @param  string $email      [description]
     * @return [type]             [description]
     */
    public function act_getList($where,$companyId=0){
        $prefix = C('DB_PREFIX');
        $sql = "select `as`.id,`as`.site_id as site_id,`as`.shop_id,status,shop_account from  `{$prefix}account_site_relation` as `as` left join `{$prefix}shops` as `s` on `as`.`platform`=`s`.`platform`";
        $sort = " order by `as`.update_time desc ";
        if(!is_array($where)&&!empty($where)){
            $where .= " and belong_company='{$companyId}' and `as`.is_delete =0 ";
        }
        return M("AccountSiteRelation")->getDataBySql($sql,$where,$sort);
    }
    public function act_getAccountBySiteId($field="*",$where){
        return  M("AccountSiteRelation")->getAllData($field,$where,"site_id");
    }
}