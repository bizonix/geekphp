<?php
/**
 * 类名：AuthUser
 * 功能：岗位的权限控制
 * 版本：1.0
 * 日期：2013/8/7
 * 作者：林正祥
 * 配置文件增加设置：
 * USER_AUTH_ON 		是否需要认证
 * USER_AUTH_ID 		认证用户id
 * USER_AUTH_COMPANY 	认证公司id
 * USER_AUTH_TYPE 		认证类型
 * USER_AUTH_KEY 		认证识别号
 * NOT_AUTH_NODE 		无需认证节点
 * USER_AUTH_GATEWAY 	认证网关
 * TABLE_USER_INFO 		用户表名称
 * modify by lzx, date 20140605
 */

class AuthUser {

    /**
     * 验证当前访问节点是否有权限
     * @param string $module	模块名称
     * @param string $node		节点名称
     * @return bool ture/false:
     */
    static function checkLogin($module, $node){
        // 判断该项目是否需要认证
        if (C('USER_AUTH_ON')===false){
        	return true;
        }
        // 判断当前模块是否为不需要认证模块
        if (C('NOT_AUTH_NODE')!=''){
        	$notauths = explode(',', C('NOT_AUTH_NODE'));
        	if (in_array($module.'-'.$node, $notauths) || in_array($module, $notauths)){
        		return true;
        	}
        }
 return false;
        $module	= ucfirst($module."View");
        $node	= "view_".$node;
        //var_dump(C('USER_AUTH_TYPE'));exit;
        // 认证方式1为登陆认证，2为实时认证 
        if (C('USER_AUTH_TYPE')===1){
        	$accesslists = isset($_SESSION[C('USER_AUTH_KEY')]) ? $_SESSION[C('USER_AUTH_KEY')] : M('InterfacePower')->getUserPower(get_usertoken());
        }
   		if (C('USER_AUTH_TYPE')===2){
        	$accesslists = M('InterfacePower')->getUserPower(get_usertoken());
        }
//         if($module=='OrderDetailsView'){
// 	        foreach($accesslists[$module] as $k=> $vv){
// 	        	if($vv=='view_update'){
// 	        		unset($accesslists[$module][$k]);
// 	        	}	
// 	        }
//         }
        if (isset($accesslists[$module])&&in_array($node, $accesslists[$module])){
        	return true;
        }else{
        	return false;
        }
    }
}
?>