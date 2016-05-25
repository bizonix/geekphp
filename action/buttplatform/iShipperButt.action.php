
<?php
/*
* IShipper对接接口
* add by: zoujunrong @date 20150413
*/
class IShipperButtAct extends CheckAct
{

    private $userApi; //key
    private $ishipperObj; //赛维api对象

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($userApi)
    {
        $this->ishipperObj   = F("ishipper.IShipper");
        $this->userApi       = $userApi;
        $this->ishipperObj->setConfig($userApi);
    }

    /**
     * 同步订单
     */
    public function postOrder($orderIds){
        if(!empty($orderIds)){
            $ordersStr = implode(",", $orderIds);
            $orderArr = M("Order")->getAllData("*","id IN ({$ordersStr})");
            $postOrderArr = array();
            foreach ($orderArr as $key => $mainOrder) {
                M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainOrder["create_time"]));
                $detailData = M("OrderDetails")->getSingleData('receiptAddress,buyerInfo,childOrderList,orderDeclarationContent',array("id"=>$mainOrder["id"]));
                $receiptAddress = json_decode($detailData['receiptAddress'],true);
                $buyerInfo = json_decode($detailData['buyerInfo'],true);
                $childOrderList = json_decode($detailData['childOrderList'],true);
                $decContent = json_decode($detailData['orderDeclarationContent'],true);
                //订单的sku信息
                $orderItems = array("OrderItem" => array());
                foreach ($childOrderList as $childSku) {
                    $orderItems["OrderItem"] = array(
                        "Quantity"  => $childSku["lotNum"],
                        "Sku"       => $childSku['productAttributes']["sku"],
                        "Title"     => $childSku['productAttributes']['itemTitle'],
                        "ItemUrl"   => $childSku['productAttributes']['skuUrl'],
                    );
                }

                $postOrderArr["Order"] = array(
                    "SellerAccountName" => $mainOrder["user_name"],
                    "OrderId"           => $mainOrder["id"],
                    "SalesOrderId"      => $mainOrder["order_id"],
                    "BuyerId"           => 0,
                    "ReceiverName"      => $receiptAddress['contactPerson'],
                    "AddressLine1"      => $receiptAddress['address1'],
                    "AddressLine2"      => $receiptAddress['address2'],
                    "Country"           => $receiptAddress['country'],
                    "State"             => $receiptAddress['province'],
                    "City"              => $receiptAddress['city'],
                    "PostCode"          => $receiptAddress['zip'],
                    "PhoneNumber"       => empty($receiptAddress['phoneNumber']) ? $receiptAddress['mobileNo'] : $receiptAddress['phoneNumber'],
                    //"Email"             => '',//$buyerInfo['email'],
                    "ShipWayCode"       => 'USPS',
                    "OrderItems" => $orderItems,
                    "OrderCustoms" => json_decode($detailData['orderDeclarationContent'],true)
                );
            }
        }
        $ret = $this->ishipperObj->postOrder($postOrderArr);
        return $ret;
    }

    /*
     * 获取所有运输方式api
     * zjr
     */
    public function getallshipway(){
        return $this->ishipperObj->getallshipway();
    }
}
?>