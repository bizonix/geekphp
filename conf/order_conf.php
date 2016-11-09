<?php
/**
*功能：定义常用变量
*版本：2014-12-29
*作者：wcx
*拿来当作订单状态配置文件
*/

if (!defined('WEB_PATH')) exit();
//全局配置信息
return  array(
	'PLATFORMS'	=> array(
		'0'		=> array(	 //表示所有平台，默认
			'platformName'			=> '所有',
			'orderStatus'	=> array(	//平台的订单状态
				'PLACE_ORDER_SUCCESS'		=> '等待买家付款',
				'IN_CANCEL'					=> '买家申请取消',
				'WAIT_SELLER_SEND_GOODS'	=> '等待您发货',
				'SELLER_PART_SEND_GOODS'	=> '部分发货',
				'WAIT_BUYER_ACCEPT_GOODS'	=> '等待买家收货',
				'FUND_PROCESSING'			=> '买家确认收货后，等待退放款处理',
				'FINISH'					=> '已结束的订单',
				'IN_ISSUE'					=> '含纠纷的订单',
				'IN_FROZEN'					=> '冻结中的订单',
				'WAIT_SELLER_EXAMINE_MONEY'	=> '等待您确认金额',
				'RISK_CONTROL'				=> '订单处于风控24小时中，从买家在线支付完成后开始，持续24小时',
				'REQUIRE_REVIEW'			=> '欺诈活动订单',
			),
			'logisticsStatus'	=> array(  //平台物流状态
				'WAIT_SELLER_SEND_GOODS'	=> '等待卖家发货',
				'SELLER_SEND_PART_GOODS'	=> '卖家部分发货',
				'SELLER_SEND_GOODS'			=> '卖家已发货',
				'BUYER_ACCEPT_GOODS'		=> '买家已确认收货',
				'NO_LOGISTICS'				=> '没有物流流转信息',
			),
			'issueStatus'		=> array(  //纠纷
				'NO_ISSUE'	=> '无纠纷',
				'IN_ISSUE'	=> '纠纷中',
				'END_ISSUE'	=> '纠纷结束',
			),
			'frozenStatus'		=> array(  //资金冻结状况
				'NO_FROZEN'	=> '无冻结',
				'IN_FROZEN'	=> '冻结中',
			),
		),
		'1'		=> array(
			'platformName'			=>	'ebay',
		),
		'2'		=> array(
			'platformName'			=>	'速卖通',
			'orderStatus'	=> array(
				'PLACE_ORDER_SUCCESS'		=> 'PLACE_ORDER_SUCCESS',
				'IN_CANCEL'					=> 'IN_CANCEL',
				'WAIT_SELLER_SEND_GOODS'	=> 'WAIT_SELLER_SEND_GOODS',
				'SELLER_PART_SEND_GOODS'	=> 'SELLER_PART_SEND_GOODS',
				'WAIT_BUYER_ACCEPT_GOODS'	=> 'WAIT_BUYER_ACCEPT_GOODS',
				'FUND_PROCESSING'			=> 'FUND_PROCESSING',
				'FINISH'					=> 'FINISH',
				'IN_ISSUE'					=> 'IN_ISSUE',
				'IN_FROZEN'					=> 'IN_FROZEN',
				'WAIT_SELLER_EXAMINE_MONEY'	=> 'WAIT_SELLER_EXAMINE_MONEY',
				'RISK_CONTROL'				=> 'RISK_CONTROL',
			),
			'logisticsStatus'	=> array(  //平台物流状态
				'WAIT_SELLER_SEND_GOODS'	=> 'WAIT_SELLER_SEND_GOODS',
				'SELLER_SEND_PART_GOODS'	=> 'SELLER_SEND_PART_GOODS',
				'SELLER_SEND_GOODS'			=> 'SELLER_SEND_GOODS',
				'BUYER_ACCEPT_GOODS'		=> 'BUYER_ACCEPT_GOODS',
				'NO_LOGISTICS'				=> 'NO_LOGISTICS',
			),
			'issueStatus'		=> array(  //纠纷
				'NO_ISSUE'	=> 'NO_ISSUE',
				'IN_ISSUE'	=> 'IN_ISSUE',
				'END_ISSUE'	=> 'END_ISSUE',
			),
			'frozenStatus'		=> array(  //资金冻结状况
				'NO_FROZEN'	=> 'NO_FROZEN',
				'IN_FROZEN'	=> 'IN_FROZEN',
			),
		),
		'3'		=> array(
			'platformName'			=>	'亚马逊',
		),
		'4'		=> array(
			'platformName'			=>	'Wish',
			'orderStatus'	=> array(	//平台的订单状态
				'APPROVED'			=> 'WAIT_SELLER_SEND_GOODS',
				'SHIPPED'			=> 'WAIT_BUYER_ACCEPT_GOODS',
				'REFUNDED'			=> 'IN_ISSUE',
				'REQUIRE_REVIEW'	=> 'REQUIRE_REVIEW',
			),
		),
		'5'		=> array(
			'platformName'			=>	'天猫',
		),
		'6'		=> array(
			'platformName'			=>	'淘宝',
		),
	),
	"ORDER_FEE"	=> array(
		"exchange_rate"	=> 6.17,
		"platfrom_handle_rate"	=> array(
			"1"	=> 0.1,
			"2"	=> 0.05,
			"4" => 0.15,
		),
		"pay_handle_rate"	=> array(
			'1' => 0.04,
			'2' => 0.01,
			'4'	=> 0.01,
		),
		'handle_fee'	=> array(
			'start'	=> 0.5,
		),
	),
	"ORDERHANDLESTATUS"	=> array(		//订单的处理状态
		"1"		=> '未处理',
		"2"		=> '推送失败',
		"3"		=> '推送成功',
		"4"		=> '异常订单',
		"5"		=> '回收站',
		"6"		=> "待审核",
		"7"		=> "通过审核",
		"8"		=> "不通过审核",
		"9"		=> "待发货",
		"10"	=> "待配货",
		"11"	=> "已配货",
		"12"	=> "已发货",
		"13"	=> "暂不寄",
		"14"	=> "取消交易",
		"15"	=> "缺货",
		"16"	=> "邮局退回",
		"17"	=> "异常需修改",
		"18"	=> "已包装",
		"40"	=> '未知',
	),
	'ORDER_STATICS_STATUS' => array(
		'handle_no'	=> array('1'),
		'handle_in' => array('3','6','7','9','10','11','18'),
		'handle_fd' => array('2','4','5','8','13','14','15','16','17','18','40'),
		'handle_ed'	=> array('12')
	),
	"ORDER_COMEIN_TYPE"	=> array(		//订单的导入状态
		'1'	=> '系统导入',
		'2'	=> '人工导入',
	),
	"ORDER_SELLER_SHIPPMENT_STATUS"	=> array(		//订单的导入状态
		'0'	=> '未标记',
		'1'	=> '全部',
		'2'	=> '部分',
		'3'	=> '失败',
	),
	"ORDER_NOTE"	=> array(
		"notice"	=> array(
			"1"	=> "停售通知",
		),
	),
);

?>