<?php
/**
 * 功能：公司管理
 * @author wcx
 * v 1.0
 * 时间：2014/01/27
 *
 */
class GroupView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 添加公司
     */
    public function view_addGroupView(){
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
    	$this->smarty->display('user/group/addGroup.html');
    }

    /**
     * 团体信息
     */
    public function view_groupDetailView(){
        $infos = A("Group")->act_getGroupInfoById(get_usercompanyid());
        if(empty($infos)){
            redirect_to(WEB_URL."group/addGroupView");
            exit;
        }
        $this->smarty->assign($infos);
        $this->smarty->display('user/group/groupDetail.html');
    }

    /**
     * 添加公司
     */
    public function view_updateGroupView(){
        $infos = A("Group")->act_getGroupInfoById(get_usercompanyid());
        $this->smarty->assign($infos);
        $this->smarty->display('user/group/addGroup.html');
    }

    /**
     * 团体列表
     */
    public function view_groupListView(){
        $list = A("Group")->act_getGroupList();
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
        $this->smarty->assign($list);
        $this->smarty->display('user/group/groupList.html');
    }

    /**
     * 申请加入
     */
    public function view_applyAddGroup(){
        $companyId = $_REQUEST['companyId'];
        A("Group")->act_applyAddGroup($companyId);
        $msg = $this->collectMsg();
        redirect_to(WEB_URL."group/groupListView/errCode/".$msg['errCode']);
    }

    /**
     * 添加公司
     */
    public function view_addGroupPost(){
        $group    = $_POST;
        if(empty($group)){
            redirect_to(WEB_URL."group/addGroupView/errCode/20014");
        }
        $res = A("Group")->act_addGroup($group);
        if($res){
            redirect_to(WEB_URL."group/groupDetailView");
        }else{
            $msg = $this->collectMsg();
            redirect_to(WEB_URL."group/addGroupView/errCode/".$msg['errCode']);
        }
    }

    /**
     * 成员管理
     */
    public function view_groupMemberAdmin(){
        $list = A("Group")->act_getGroupMembers(get_usercompanyid());
        if(isset($_REQUEST['errCode'])){
            $this->showOperateRes();
        }
        $this->smarty->assign($list);
        $this->smarty->display('user/group/groupMembers.html');
    }

    /**
     * 修改成员状态
     */
    public function view_updateMemberStatus(){
        $memberId   = $_REQUEST['memberId'];
        $status     = $_REQUEST['status'];
        $members    = A("Group")->act_updateMembersStatus($memberId,$status);
        $msg = $this->collectMsg();
        redirect_to(WEB_URL."group/addGroupView/errCode/".$msg['errCode']);
    }

    /**
     * 功能：检测该名称是不是存在
     * wcx
     */
    public function view_checkGroupIsExit(){
        $groupCnName = $_REQUEST["cn_name"];
        $groupEnName = $_REQUEST["en_name"];
        if(!empty($groupCnName)){
            $groupInfo = A("Group")->act_getGroupInfoByCnName($groupCnName);
        }elseif(!empty($groupEnName)){
            $groupInfo = A("Group")->act_getGroupInfoByEnName($groupEnName);
        }
        echo $this->ajaxReturn($groupInfo);
    }
}
?>