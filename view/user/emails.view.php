<?php
/**
 * 功能：邮件管理
 * @author wcx
 * v 1.0
 * 时间：2014/03/16
 *
 */
class EmailsView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    public function view_index(){
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
        $this->smarty->assign(A("Shops")->act_getCompanyAllShops(get_usercompanyid()));
        $this->smarty->assign(A("Emails")->act_getAllEmailTemplates(get_usercompanyid()));
        $this->smarty->display('user/emails/sendEmail.html');
    }

    /**
     * 发送邮件
     */
    public function view_sendEmail(){
         // var_dump($_REQUEST);exit;
        $receiver       = $_REQUEST['receiver'];
        $copyReceiver   = $_REQUEST['copyReceiver'];
        $title          = $_REQUEST['title'];
        $content        = $_REQUEST['content'];
        $type           = $_REQUEST['sendType'];
        $shopSelect     = $_REQUEST['shop'];
        $templateId     = $_REQUEST['templateId'];
        $sendRes = A("Emails")->act_sendEmail($receiver,$copyReceiver,$title,$content,$type,$shopSelect,$templateId);
        $msg = $this->collectMsg();
        redirect_to(WEB_URL."emails/index/errCode/".$msg['errCode']);
    }

    /**
     * 功能：获取模板信息
     * @return [type] [description]
     */
    public function view_emailTemplates(){
        $templateId = $_REQUEST['templateId'];
        $res = A("Emails")->act_getEmailTemplatesInfo($templateId);
        $this->ajaxReturn($res);
    }

    /**
     * 功能：ajax获取模板
     * @return [type] [description]
     */
    public function view_getEmailTemplates(){
        $list = A("Emails")->act_getEmailTemplates(get_usercompanyid());
        $this->ajaxReturn($list);
    }

    /**
     * 功能：发送邮件给卖家
     * wcx
     */
    public function view_sendEmailToBuyers(){
        $emails     = $_REQUEST['emails'];
        $templateId = $_REQUEST['templateId'];
        $orderSysId = $_REQUEST['orderSysId'];
        $res = A("Emails")->act_sendEmailToBuyers($emails,$templateId,$orderSysId);
        $this->ajaxReturn($res);
    }
    
}
?>