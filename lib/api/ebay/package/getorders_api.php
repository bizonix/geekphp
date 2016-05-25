<?php
/*
 * 获取订单抓取ID列表
 * @add lzx, date 20140612
 */
include_once WEB_PATH."lib/api/ishipper/ishipperSession.php";
class GetOrders extends iShipperSession{
	
	protected $verb = 'GetOrders';
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取订单id键值
	 * @param datetime $start
	 * @param datetime $end
	 * @param int $pcount
	 * @return array
	 * @author lzx
	 */
	public function pushOrders($start, $end, $pcount){
		$requestXmlBody = '<?xml version="1.0" encoding="UTF-8"?>
			<Orders>
			<!-- 订单基本信息-->
				<Order>
				<!-- 订单传入到系统哪个卖家账号下-->
				<SellerAccountName>aaaa</SellerAccountName>
				<!-- 订单在你们系统的唯一标识ID -->
				<OrderId>10011245194</OrderId>
				<!--订单在销售平台的编号-->
				<SalesOrderId>1101815390</SalesOrderId>
				夏浦物流iShipper 系统API 使用规范及其说明
				- 33 -
				<!-- 订单的买家账号-->
				<BuyerId>Giorgi Khobua</BuyerId>
				<!-- 收件人名称-->
				<ReceiverName>Giorgi Khobua</ReceiverName>
				<!-- 地址第一行-->
				<AddressLine1>8 McCullough dr.</AddressLine1>
				<!-- 地址第二行-->
				<AddressLine2>U00729</AddressLine2>
				<!-- 目的国家-->
				<Country>UNITED STATES</Country>
				<!-- 州-->
				<State>DE</State>
				<!-- 城市-->
				<City>Nev Castle</City>
				<!-- 邮编-->
				<PostCode>19720</PostCode>
				<!-- 电话号码-->
				<PhoneNumber>UNITED STATES</PhoneNumber>
				<!-- 电子邮箱-->
				<Email>UNITED STATES</Email>
				<!-- 运送方式-->
				<ShipWayCode>USPS</ShipWayCode>
				<!-- 订单物品信息-->
				<OrderItems>
				<OrderItem>
				<!-- 数量-->
				<Quantity>1</Quantity>
				<!-- sku（产品种类的区别标识） -->
				<Sku>ipone_case001</Sku>
				<!-- 产品标题-->
				<Title>Green car mp3</Title>
				<!-- 销售连接-->
				<ItemUrl>Green car mp3</ItemUrl>
				</OrderItem>
				<OrderItem>
				<Quantity>1</Quantity>
				<Sku>10155339</Sku>
				<Title>Green car mp3</Title>
				<ItemUrl>Green car mp3</ItemUrl>
				</OrderItem>
				</OrderItems>
				<!-- 订单报关信息-->
				<OrderCustoms>
				<!-- 币种-->
				<Currency>RMB</Currency>
				<!-- 报关类型-->
				<CustomsType>礼物</CustomsType>
				夏浦物流iShipper 系统API 使用规范及其说明
				- 44 -
				<OrderCustom>
				<!-- 数量-->
				<Quantity>1</Quantity>
				<!-- 报关内容描述（英文） -->
				<DescriptionEn> Green car mp3</DescriptionEn>
				<!-- 报关内容描述（中文） -->
				<DescriptionCn>绿色MP3 </DescriptionCn>
				<!-- 报关重量-->
				<Weight>2.5</Weight>
				<!-- 报关价值-->
				<Value>10.5</Value>
				</OrderCustom>
				<OrderCustom>
				<Quantity>1</Quantity>
				<DescriptionEn>10155339</DescriptionEn>
				<DescriptionCn>Green car mp3</DescriptionCn>
				<Weight>Green car mp3</Weight>
				<Value>Green car mp3</Value>
				</OrderCustom>
				</OrderCustoms>
				</Order>
			</Orders>';
		return $this->sendHttpRequest($requestXmlBody);
	}
	public function getOrderTransactions($order_id){
		$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
			<GetOrderTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
				</RequesterCredentials>  
				<OrderIDArray><OrderID>'.$order_id.'</OrderID></OrderIDArray>
				<OutputSelector>OrderArray.Order.TransactionArray.Transaction.Buyer.Email</OutputSelector>
				
				';
		
		
		
		$requestXmlBody .= '</GetOrderTransactionsRequest>';
		$receivelists = $this->sendHttpRequest($requestXmlBody);
		
		
		return $receivelists;
	}
	
	public function getSingleOrder($order_id){
		$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
				</RequesterCredentials>  
				<DetailLevel>ReturnAll</DetailLevel>
				<OrderRole>Seller</OrderRole>
				<OrderStatus>Completed</OrderStatus>
				<OutputSelector>PaginationResult</OutputSelector>
				<OutputSelector>HasMoreOrders</OutputSelector>
				<OutputSelector>ReturnedOrderCountActual</OutputSelector>				
				<OutputSelector>OrderArray.Order.OrderID</OutputSelector>
				<OutputSelector>OrderArray.Order.PaidTime</OutputSelector>
				<OutputSelector>OrderArray.Order.ShippedTime</OutputSelector>
				<OutputSelector>OrderArray.Order.CheckoutStatus</OutputSelector>
				<OutputSelector>OrderArray.Order.CheckoutStatus</OutputSelector>
				<OutputSelector>OrderArray.Order.TransactionArray.Transaction.ShippingDetails.SellingManagerSalesRecordNumber</OutputSelector>
				';
		
		if(!empty($order_id)){
			$requestXmlBody .= '<OrderIDArray><OrderID>'.$order_id.'</OrderID></OrderIDArray>';
		}else{
			die('please input order id');
		}
		
		$requestXmlBody .= '<IncludeFinalValueFee>true</IncludeFinalValueFee>
				<OrderRole>Seller</OrderRole>
				<OrderStatus>All</OrderStatus>
			</'.$this->verb.'Request>';
		return $receivelists = $this->sendHttpRequest($requestXmlBody);
		
	}
	/**
	 * 获取订单详情
	 * @param array $order_ids
	 * @return array
	 * @author lzx
	 */
	public function getOrderLists($order_ids){
		//echo '---<pre>';print_r($order_ids);exit;
		$order_ids = array_filter($order_ids, create_function('$orderid', 'return preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid)>0;'));
		if (count($order_ids)==0){
			return false;
		}
		$valid_orderids = array_map(create_function('$a','return "<OrderID>".$a."</OrderID>";'), $order_ids);
		$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
				</RequesterCredentials>  
				<DetailLevel>ReturnAll</DetailLevel>
				<IncludeFinalValueFee>true</IncludeFinalValueFee>
				<OrderRole>Seller</OrderRole>
				<OrderStatus>Completed</OrderStatus>
				<OrderIDArray>'."\n".implode("\n\t",$valid_orderids)."\n".'</OrderIDArray>
			</'.$this->verb.'Request>';
		return $this->sendHttpRequest($requestXmlBody);
	}
}
?>