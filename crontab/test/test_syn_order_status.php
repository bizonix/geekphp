<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$orders = M('Order')->getAllData('id,order_id,come_from,handle_status',"delivery_from = 3 and company_id = 3 and handle_status = 12",'id');
$hasSend = array_keys($orders);
/*foreach ($orders as $order){
    $orderInfo = M('Order')->getSingleData('id,handle_status',"order_id = '{$order['order_id']}' and company_id = {$order['come_from']}");
    if(empty($orderInfo)){
        continue;
    }
    if(!empty($orderInfo['handle_status']) && $order['handle_status'] != $orderInfo['handle_status']){
        $res = M('Order')->updateData($order['id'],array('handle_status' => $orderInfo['handle_status']));
        echo "\r\n id = {$order['id']},res = ".json_encode($res);
    }
    if($orderInfo['handle_status'] == 12){
        $hasSend[] = $order['id'];
    }
}*/
//收集已发货订单，用于统计费用
if(!empty($hasSend)){
    $res = A('Statistics')->importStatics($hasSend);
    // echo "\r\n res = ".json_encode($res);
}
exit;


$orders = M('Order')->getAllData('*',"delivery_from = 3 and company_id != 3 and handle_status = 12",'','order by id asc');

foreach ($orders as $order){
    $mainData = $order;
    //删除敏感信息
    unset($mainData["id"]);
    $mainData['company_id'] = $mainData['delivery_from'];
    $mainData['come_from'] = $order["company_id"];
    $mainData['shop_id'] = '0';
    $mainData['user_id'] = '0';
    $mainData['user_name'] = 'unkown';
    
    M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData["create_time"]));
    $detailOrder = M("OrderDetails")->getSingleData("*","id = {$order['id']}");
    //拦截物流运送不到国家
    $receiptAddress = json_decode($detailOrder['receiptAddress'],true);
    
    M("Common")->begin();
    $exist = M("Order")->getData("id,create_time",array("order_id"=>$mainData['order_id'],"company_id"=>$mainData['company_id'],"source_platform"=>$mainData['source_platform']));
    if(empty($exist[0]['id'])){//判断订单是否已经存在 ，或者取消，删除
        //订单插入
        $mainData['new_order_sys_id'] = $order['id'];
        $ret1 	= M("Order")->insertData($mainData);
        if(!$ret1){
            M("Common")->rollback();
            log::write("order push data ".M("Order")->getErrorMsg());
        }
        $detailOrder['id']   =   M("Order")->getLastInsertId();
        $ret2 = M("OrderDetails")->insertData($detailOrder);
        if(!$ret2){
            M("Common")->rollback();
            log::write("OrderDetails push data ".M("OrderDetails")->getErrorMsg());
        }else{
            //添加关联
            $whereData = array(
                "belong_company" 	=> $order["company_id"],
                "to_company"		=> $order["delivery_from"],
            );
            $relationData = $whereData;
            $relationData["add_time"] = time();
            M("CompanyRelation")->replaceDataWhere($relationData,$whereData);
        }
    }else{
        echo "exist \r\n";
    }
    /*else{
        //修改
        $mainData['new_order_sys_id'] = $order['id'];
        $ret1 = M("Order")->updateData($exist[0]['id'],$mainData);
        if(!$ret1){
            M("Common")->rollback();
            log::write("order push update data ".M("Order")->getErrorMsg());
        }
        unset($detailOrder['id']);
        $ret2	= M("OrderDetails")->updateData($exist[0]['id'],$detailOrder);
        if(!$ret2){
            M("Common")->rollback();
            log::write("OrderDetails push update data ".M("OrderDetails")->getErrorMsg());
        }else{
            //添加关联
            $whereData = array(
                "belong_company" 	=> $order["company_id"],
                "to_company"		=> $order["delivery_from"],
            );
            $relationData = $whereData;
            $relationData["add_time"] = time();
            M("CompanyRelation")->replaceDataWhere($relationData,$whereData);
        }
        $detailOrder['id'] = $exist[0]['id'];
    }*/
    if($ret1 && $ret2){
        $res = M("Order")->updateData($order['id'],array("update_time"=>time(),'new_order_sys_id'=>$detailOrder['id']));
        if(!$res){
            M("Common")->rollback();
            continue;
        }
        M("Common")->commit();
        echo $order['id'];
        var_dump($res);exit;
    }
    
}

// $res = A('Order')->synOrderSomeInfo(array(array('id' => '140002','handle_status' => '11', 'note' => '测试配货程序')));
// print_r($res);exit;