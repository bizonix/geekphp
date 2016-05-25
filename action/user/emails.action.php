<?php
/**
 * 功能：邮件管理
 * @author zjr
 * v 1.0
 * 时间：2014/12/16
 *
 */
class EmailsAct extends CheckAct {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 添加店铺
     */
    public function act_sendEmail($receiver,$copyReceiver,$title,$content,$type,$shopSelect="#",$templateId="#"){
        //var_dump($receiver,$copyReceiver,$title,$content,$type);exit;
        if(empty($title)){
            self::$errMsg[10008] = get_promptmsg(10008,"标题");
            return false;
        }
        if(empty($content)){
            self::$errMsg[10008] = get_promptmsg(10008,"邮件内容");
            return false;
        }
        //模板处理
        if(in_array("2", $type)){
            $companyId = get_usercompanyid();
            if(!$companyId){
                self::$errMsg[10008] = get_promptmsg(10008,"公司");
                return false;
            }
            if($templateId != "#" && !empty($templateId)){
                $updateData = array(
                    "add_user"   => empty(get_username()) ? "unkown" : get_username(),
                    "title"      => $title,
                    "content"    => $content,
                    "update_time"=> time(),
                );
                $updateRet = M("EmailTemplates")->updateData($templateId,$updateData);
                if(!$updateRet){
                    self::$errMsg[10001] = get_promptmsg(10001,"修改邮件模板");
                    return false;
                }
            }else{
                $insertData = array(
                    "company_id" => $companyId,
                    "add_user"   => empty(get_username()) ? "unkown" : get_username(),
                    "title"      => $title,
                    "content"    => $content,
                    "update_time"=> time(),
                    "add_time"   => time(),
                );
                $insertRet = M("EmailTemplates")->insertData($insertData);
                $templateId = M("EmailTemplates")->getLastInsertId();
                if(!$insertRet){
                    self::$errMsg[10001] = get_promptmsg(10001,"保存邮件模板");
                    return false;
                }
            }
            self::$errMsg[200] = get_promptmsg(200,"保存邮件");
        }

        //邮件发送
        if(in_array("1", $type)){
            if($shopSelect == "#"){
                //直接发送邮件
                if(empty($receiver)){
                    self::$errMsg[10008] = get_promptmsg(10008,"收件人");
                    return false;
                }
                $receiver = explode(";", $receiver);
                $copyReceiver    = explode(";", $copyReceiver);
                $toEmail = array();
                $toCC    = array();
                foreach ($receiver as $k => $v) {
                    if(validate_email($v)){
                        $toEmail[] = array("email"=>$v);
                    }
                }
                foreach ($copyReceiver as $k => $v) {
                    if(validate_email($v)){
                        $toCC[] = array("email"=>$v);
                    }
                }
                include_once WEB_PATH.'lib/PHPMailer/sendEmail.php';
                $sendmail = sendEmail($toEmail, $title, $content,$toCC,get_username());
                if(strlen($sendmail) > 1) {     //如果邮件发送失败，则将错误信息返回到$sendmail变量内，
                    self::$errMsg[20006] = get_promptmsg(20006);
                    return false;
                }
                self::$errMsg[200] = get_promptmsg(200,"发送邮件");
            }else{
                exec("php ".WEB_PATH."crontab/system/email/feth_shop_buyer_email.php $companyId $shopSelect $templateId  &> /dev/null &");
                //加入邮件发送队列后台发送
                /*$nowTime = time();
                if($shopSelect == "all"){
                    //选择了所有店铺
                    $where    = array("belong_company" => $companyId);
                    $shops    = M("Shops")->getAllData("id",$where);
                    $shopStr  = implode(",",$shops);
                    $shopSql  = "shop_id IN ({$shopStr})";
                }else{
                    $shopSql = "shop_id = {$shopSelect}";
                }
                $orders = M("Order")->getAllData("id,create_time,shop_id","company_id = {$companyId} and source_platform !=4  and {$shopSql}");
                foreach($orders as $k=>$v){
                    M("OrderDetails")->setTablePrefix('_'.date('Y_m',$v["create_time"]));
                    $buyerInfo = M("OrderDetails")->getSingleData("buyerInfo","id=".$v['id']);
                    if(empty($buyerInfo['buyerInfo'])){
                        continue;
                    }
                    $buyerInfo = json_decode($buyerInfo['buyerInfo'],true);
                    $insertData = array(
                        "template_id"   => $templateId,
                        "email"         => $buyerInfo['email'],
                        "company_id"    => $companyId,
                        "update_time"   => $nowTime,
                        "add_time"      => $nowTime,
                    );
                    M("EmailQueue")->insertData($insertData);
                }*/
            }
        }

        return true;

    }

    /**
     * 功能：获取模板信息
     * zjr
     */
    public function act_getEmailTemplates($companyId){
        $where    = array("company_id" => $companyId);
        $count    = M("EmailTemplates")->getDataCount($where);
        $p        = new Page ($count,10);
        $page     = $p->fpage();
        $templates = M("EmailTemplates")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
        return array("templates"=>$templates,"count"=>$count,"page"=>$page);
    }

    /**
     * 功能：获取模板信息
     * zjr
     */
    public function act_getEmailTemplatesInfo($templateId){
        $where          = array("id" => $templateId);
        $templateInfo   = M("EmailTemplates")->getSingleData("*",$where);
        return array("templateInfo"=>$templateInfo);
    }

    /**
     * 功能：获取所有模板信息
     * zjr
     */
    public function act_getAllEmailTemplates($companyId){
        $where    = array("company_id" => $companyId);
        $templates = M("EmailTemplates")->getAllData("id,title",$where,"id");
        return array("templates"=>$templates);
    }

    /**
     * 功能：发送邮件给卖家
     * zjr
     */
    public function act_sendEmailToBuyers($emails,$templateId,$orderSysId){
        $emails = explode(";", $emails);
        $toEmail = array();
        foreach ($emails as $k => $v) {
            if(validate_email($v)){
                $toEmail[] = array("email"=>$v);
            }
        }
        if(empty($toEmail)){
            self::$errMsg[10008] = get_promptmsg(10008,"收件人");
            return false;
        }
        if(empty($templateId)){
            self::$errMsg[10008] = get_promptmsg(10008,"模板");
            return false;
        }

        $templateInfo = M("EmailTemplates")->getSingleData("*","id={$templateId}");
        include_once WEB_PATH.'lib/PHPMailer/sendEmail.php';
        $sendmail = sendEmail($toEmail, $templateInfo['title'], $templateInfo['content'],array(),get_username());
	    $orderInfo = M("Order")->getSingleData("note","id={$orderSysId}");
        if(strlen($sendmail) > 1) {     //如果邮件发送失败，则将错误信息返回到$sendmail变量内，
	    $updataData = array("note"=>$orderInfo["note"]." (邮件发送失败：“{$sendmail}”)");
            M("Order")->updateData($orderSysId,$updataData);
            self::$errMsg[20006] = get_promptmsg(20006);
            return false;
        }
        if(!empty($orderSysId)){
            $updataData = array("note"=>$orderInfo["note"]." (已发邮件通知，使用的邮件模板“{$templateInfo['title']}”)");
            M("Order")->updateData($orderSysId,$updataData);
        }
        return true;
        
    }

    
}
?>