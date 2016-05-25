<?php
/*
 * 同步海外仓已打印订单到海外仓
 */ 

if (! defined ( 'WEB_PATH' )) {
    define ( "WEB_PATH", dirname(dirname(__DIR__))."/" );
}

require_once WEB_PATH . "crontab/scripts.comm.php";
$currentTime    = date('Y-m-d H:i:s');

$url        = "http://us.oversea.valsun.cn/api/get_owtoerp_data.php?action=updatestock";

echo "--------- 同步海外仓已打印订单  [ $currentTime ] ---------\n";

$sql        = "select * from om_unshipped_order where orderStatus=911 and orderType=910";
$resultData = $dbConn->fetch_array_all($dbConn->query($sql));

$owOrderMg  = new OwOrderManageModel();
$orderAct   = new OrderindexAct();
$orderSync  = new OwOrderSyncModel();

foreach ( $resultData as $row ){
    $orderId    = $row['id'];
    $userSql    = "select * from om_unshipped_order_userInfo where omOrderId = '$orderId'";
    $UserInfo   = $dbConn->fetch_first($userSql);                                               //获取用户信息
    if ( empty($UserInfo) ) {
    	echo "订单未找到用户信息 [订单号：] === $orderId \n\n";
    	continue;
    }
    $skuList    = $orderAct->act_getRealskulist($orderId);                                      //获取sku信息列表
    if ( empty($skuList) ) {
        echo "无法获取该订单的料号信息 [订单号：] === $orderId \n\n";
        continue;
    }
    
    $transInfo  = $owOrderMg->getShippingInfo($orderId);
    if ( empty($transInfo) ) {                                                                  //获取运输方式信息
        echo "无法获取该订单运输方式信息 [订单号：] === $orderId \n\n";
        continue;
    }
    
    $platformInfo   = $owOrderMg->getPlatformInfoByPid($row['platformId']);
    if (FALSE == $platformInfo) {
    	echo "获取平台信息失败!!!";
    	continue;
    }
    
    $platSuffix     = $platformInfo['suffix'];
    $extensionTabel =  'om_unshipped_order_extension_'.$platSuffix;                                 //扩展信息表名
    $extensionInfo  = $owOrderMg->getExtensionInfo($extensionTabel, $orderId);
    if ($extensionInfo) {
        if ( "amazon" == $platSuffix ) {                                                            //亚马逊订单
        	$row['note'] = $extensionInfo['note'];
        } else if ( 'ebay' == $platSuffix ) {                                                       //ebay订单
        	$row['note']     = $extensionInfo['feedback'];
        }
    }
    
    $sellerInfo     = $owOrderMg->getSellerInfoById($row['accountId']);                             //获得卖家账号信息
    if ($sellerInfo) {
    	$row['account']    = $sellerInfo['ebay_account'] ;
    } else {
        $row['account']    = '' ;
    }
    
    $submitData = array(
    	'orderInfo'    => $row,
        'userInfo'     => $UserInfo,
        'transInfo'    => $transInfo,
        'skuList'      => $skuList
    );
    
//     print_r($submitData);
    
    $syncResult = $orderSync->pushPrintedOrderToUsWh($orderId, $submitData);
    if ($syncResult) {                                                                          //同步成功
        $return =  $owOrderMg->changeOrderStatus(911, 917, $orderId);
        if ( $return ) {
        	echo "同步订单成功! 订单号 : $orderId  === [ $currentTime ]\n\n\n";
        } else {
            echo "更新数据库状态出错: $orderId === ", "\n\n\n";
        }
    } else {
        echo "同步出错: $orderId === ", OwOrderSyncModel::$errMsg, "\n\n\n";
    }
}

echo "done my work !!! [ $currentTime ]\n\n\n";
