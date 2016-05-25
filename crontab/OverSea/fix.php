<?php
/*
 * 同步海外仓已打印订单到海外仓
 */ 

if (! defined ( 'WEB_PATH' )) {
    define ( "WEB_PATH", dirname(dirname(__DIR__))."/" );
}

require_once WEB_PATH . "crontab/scripts.comm.php";
$currentTime    = date('Y-m-d H:i:s');
$resultAr       = array();
$info   = array();
$sql    = "select * from om_unshipped_order where orderStatus=911 and orderType=917";
$order  = $dbConn->fetch_array_all($dbConn->query($sql));
$owOrderMg  = new OwOrderManageModel();
foreach ($order as $or){
    $orderId    = $or['id'];                                    //订单号
    $sellerInfo = $owOrderMg->getSellerInfoById($or['accountId']);
    $seller     = $sellerInfo['ebay_account'];                  //卖家ID
    $recordNum  = $or['recordNumber'];                          //recordNum
    $trackInfo  = $owOrderMg->getShippingInfo($orderId);        
    $trackNum   = $trackInfo['tracknumber'];                    //跟踪号
    $shippnig   = $trackInfo['shippingWay'];                    //运输方式
    $pid        = $or['platformId'];                            //平台id
    $resultAr[] = array('orderId'=>$orderId, 'seller'=>$seller, 'recordNum'=>$recordNum, 'trackNum'=>$trackNum, 'shipping'=>$shippnig);
    /*生成执行脚本*/
    if ($pid != 1) {
    	continue;
    }
    $getDetail  = "select * from om_unshipped_order_detail where omOrderId=$orderId";
    $detailAll  = $dbConn->fetch_array_all($dbConn->query($getDetail));
    foreach($detailAll as $detail)
    {
         $command   = "php /data/scripts/ebay_order_cron_job/update_tracknum_fix.php ";
         $account   = $owOrderMg->getPlatformInfoByPid($pid);
         $extension = $account['suffix'];
         $sql       = "select * from om_unshipped_order_detail_extension_$extension where omOrderdetailId=$detail[id] ";
         $skuall    = $dbConn->fetch_first($sql);
         if ($shippnig == 'UPS Ground') {
         	$shippnig = 'UPS\ Ground';
         }
         
         $sql       = "select * from om_unshipped_order_extension_ebay where omOrderId=$orderId";
//          echo $sql,"\n";
         $info      = $dbConn->fetch_first($sql);
         $tid       = $info['PayPalPaymentId'];
         
         echo $command, $seller , " ", $recordNum, ' ', $trackNum, ' ', $shippnig, ' ', $skuall['itemId'], ' ', $tid, "\n";
    }
}
// var_export($resultAr);

