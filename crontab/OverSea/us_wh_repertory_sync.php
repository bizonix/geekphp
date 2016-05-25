<?php
/*
 * 海外仓库存同步
 */ 

if (! defined ( 'WEB_PATH' )) {
    define ( "WEB_PATH", dirname(dirname(__DIR__))."/" );
}

require_once WEB_PATH . "crontab/scripts.comm.php";
$currentTime    = date('Y-m-d H:i:s');

$url        = "http://us.oversea.valsun.cn/api/get_owtoerp_data.php?action=updatestock";
$fields		= "id,sku,stockqty";
$url       .= "&fields=$fields";

echo "--------- 同步海外仓库存  [$currentTime] ---------\n";
// echo $url;exit;
$ch     = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);                            //设置链接
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);             //设置是否返回信息
$response = curl_exec($ch);//接收返回信息

if (FALSE === $response) {
	echo '请求失败 --- ', $currentTime, "\n";
	exit;
}

$jsondata	= json_decode($response,true);
$unsuccessSql   = array();
if(count($jsondata)>0){
    foreach ($jsondata as $v) {
        $sql	= "REPLACE INTO ow_stock(id,sku,position,count,salensend) VALUES({$v['id']},'{$v['sku']}','{$v['position']}',{$v['stockqty']},{$v['salensend']})";
//         $unsuccessSql[] = $sql;
        if($dbConn->query($sql)){
            echo date('Y-m-d H:i:s',time())."-----".$v['sku']."-----".$v['stockqty']."-----sku库存同步成功\n";
        }else {
            echo date('Y-m-d H:i:s',time())."-----".$v['sku']."-----".$v['stockqty']."-----sku库存同步失败\n";
            $unsuccessSql[] = $sql;
        }
    }
}else{
    echo date('Y-m-d H:i:s',time())."-----sku库存同步数据失败\n";
    $data   = date('Y-m-d H:i:s',time())."-----sku库存同步数据失败\r\n\n";

}

if (!empty($unsuccessSql)) {
	echo "===============以下是插入失败的语句=================\n";
// 	print_r($unsuccessSql);
	echo "\n\n\n";
}

echo "done my work !!! [ $currentTime ] \n\n";