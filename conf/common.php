<?php
if (!defined('WEB_PATH')) exit();
//全局配置信息
return  array(
	//运行相关
	"RUN_LEVEL"		=>	"DEV",		//	运行模式。 DEV(开发)，GAMMA(测试)，IDC(生产)

	//日志相关
	"LOG_RECORD"	=>	true,		//	开启日志记录
	"LOG_TYPE"		=>	3,			//	1.mail  2.file 3.api
	"LOG_PATH"	    =>	WEB_PATH."log/",	//文件日志目录
	"LOG_FILE_SIZE"	=>	2097152,
	"LOG_DEST"		=>	"",			//	日志记录目标
	"LOG_EXTRA"		=>	"",			//	日志记录额外信息
    
	//数据接口相关
	"DATAGATE"		=>	"db",		//	数据接口层 cache, db, socket
	"DB_TYPE"		=>	"mysql",	//	mysql	mssql	postsql	mongodb
    "SOURCEDATABASE"=>  "weclu",
    "NEEDCOPYTABLES"=>  array(
        "we_order_details","we_order",'we_sku_sales','we_order_sales','we_goods','we_template','we_goods_templates','we_wish_template_detail','we_listing','we_wish_listing_detail'
    ),
    "NEEDSUBMETER"  =>  array(
    	'we_order_details','we_sku_sales','we_order_sales','we_wish_template_detail','we_wish_listing_detail'
    ),
	//mysql db	配置
	"DB_CONFIG"		=>	array(
// 		"master1"	=>	array("localhost","root","123019","3306","weclu")//主DB
	    "master1"	=>	array("114.215.116.159","root","huanhuan123019","3306","weclu")//主DB
        // "master1"	=>	array("173.254.246.105","root","zoujunrong123019Z","3306","weclu")//主DB
	),
	//需要自动缓存的表
	"CACHE_TABLE"	=>	array(
		"developer",
	),
	"CACHE_CONFIG"	=>	array(
		//array("192.168.200.198", "11211"),
		//array("112.124.41.121", "11211")
		// array("192.168.200.150", "11211"),
	),

	"LANG"	=>	"zh",	//语言版本开关  zh , en

	"CACHEGROUP" => 'order_system_info',   //memcache上的保存组名
    "CACHELIFETIME" => 7200,     //memcache 过期时间默认为 两小时
	'DB_PREFIX'=>'we_',            //数据库默认前缀

	//mysql db	配置
	"RMQ_CONFIG"		=>	array(//队列配置
		//"sendOrder"     =>	array("112.124.41.121","valsun_sendOrder","sendOrder%123","5672","sendOrder")			//生产环境RabbitMQ 获取权限
	),
	//图片系统配置
	'PICS_SYS_URL_LOCAL' => 'http://pics.valsun.cn/json.php?',//开放系统内网地址
	//开放系统配置
	'OPEN_SYS_URL_LOCAL' => 'http://gw.open.valsun.cn:88/router/rest?',//开放系统内网地址
	'OPEN_SYS_URL' 		 => 'http://idc.gw.open.valsun.cn/router/rest?',//开放系统外网地址
	'OPEN_SYS_USER'		 => 'Purchase',//开放系统用户名
	'OPEN_SYS_TOKEN' 	 => 'a6c94667ab1820b43c0b8a559b4bc909',//开放系统用户token

	//鉴权系统相关配置
	//'AUTH_HTML_EXT' 	 => '.htm',//模版后缀
	'AUTH_SYSNAME' 		 => 'hc',//系统名称
	'AUTH_SYSTOKEN' 	 => 'f27a4b69900d34567f1db100099beca6',//系统token

	//自动加载文件目录配置--关联F函数
	'AUTO_DIR' 		 => array('functions'=>'file', 'class'=>'object', 'api'=>'object'),//文件目录


	'IS_DEBUG' 			 => false,    //用户管理DEBUG
	'IS_AUTH_ON' 		 => true,	// 是否开启验证
	'USER_AUTH_TYPE'	 => 2,		// 验证模式1为登录时验证，2为实时验证
	'USER_AUTH_ID'		 => 'userId', // 储存userId的SESSION 的 keyy
	'USER_COM_ID'		 => 'companyId', // 储存companyId的SESSION 的 key
	'USER_AUTH_KEY'		 => 'userpowers',
	'USER_GO_URL'		 => 'index.php?mod=Order&act=index&ostatus=100&otype=101',
	'NOT_AUTH_NODE' 	 => 'index,login,register,backstagesIndex,public-showErr,public-getSmtRefrashToken,track',	// 默认无需认证模块
	'AUTH_COMPANY_ID'    => 1,

	//+++++++++++++++begin  发送邮件的配置
	'EMAIL'	=> array(
			'user'		=> "theweclu@163.com",
			'password'	=> "zoujunrong123019",
			'smtp'		=> "smtp.163.com",
			'port'		=> "25",		//465 or 587
	),
	'COMPANYNAME'	=> "维库网络科技",
	//+++++++++++++++end  发送邮件的配置
	'EMAILCONTENTS'	=> "<table width='100%' border=1 cellpadding=0 cellspacing=0 style='border-collapse:collapse'>
							<tr>
								<td>动作</td>
								<td>SPU</td>
								<td>虚拟SKU</td>
								<td>真实SKU</td>
								{sellerFields}
								<td>采购人</td>
								<td>可用库存数</td>
								{availableInventoryDaysFields}
								<td>缺货天数</td>
								<td>仓位</td>
							</tr>
							{values}
						</table>",

	//开发者身份证,营业执照,税务登记证等关键信息图片目录
	'DISTRIBUTOR_KEY_PICTURE_DIR'    =>  WEB_PATH."html/images/distributor/",

    //开发者授权状态
    "AUTHORIZATIONSTATUS"   =>  array(
		"1"   =>  "未申请",
		"2"   =>  "等待审核",
		"3"   =>  "已授权",
		"4"   =>  "审核不通过",
		"5"   =>  "授权中",
	),

	//不同授权状态触发的动作
	"AUTHORIZATIONACT"   =>  array(
	        "1"   =>  "申请授权",
	        "2"   =>  "查看",
	        "3"   =>  "查看授权",
	        "4"   =>  "查看",
	),

	//账户状态开发者账户状态：0.未申请,已认证,1审核中(授权中)，2通过审核(通过授权)，3未通过审核(未通过授权)，4停用该账户,5没激活,6.未认证
	"ACCOUNT_STATUS"   =>  array(
            "0"   =>  "已认证",
	        "1"   =>  "审核中",//(授权中)
	        "2"   =>  "通过审核",//(通过授权)
	        "3"   =>  "未通过审核",//(未通过授权)
	        "4"   =>  "停用该账户",
	        "5"   =>  "邮箱没激活",
	        "6"   =>  "未认证",
	),
	//API审核状态：1审核中(授权中)，2通过审核(通过授权)，3未通过审核(未通过授权)
	"API_STATUS"   =>  array(
            "1"   =>  "审核中",
	        "2"   =>  "审核通过",
	        "3"   =>  "未通过审核",
	),
	'SITES'		=> array(
		'0'    	=> '美国',
		'2'    	=> '加拿大',
		'3'    	=> '英国',
		'15'    => '澳大利亚',
		'77'    => '德国',
		'71'    => '法国',
		'186'   => '西班牙',
		'101'   => '意大利',
		'216'   => '新加坡',
		'211'   => '菲律宾',
		'100'   => 'eBay摩托',
		'207'   => '马来西亚',
	),
	'SITESSIMPLE'		=> array(
			'0'    	=> 'US',
			'2'    	=> 'Canada',
			'3'    	=> 'UK',
			'15'    => 'Australia',
			'77'    => 'Germany',
			'71'    => 'France',
			'186'   => 'Spain',
			'101'   => 'Italy',
			'216'   => 'Singapore',
			'211'   => 'Philippines',
			'100'   => 'eBayMotors',
			'207'   => 'Malaysia',
	),
	'SHOPSTATUS'	=> array(
		"1"   =>  "未申请",
		"2"   =>  "待审核",
		"3"   =>  "已通过",
		"4"   =>  "未通过",
		"5"   =>  "授权中",
	),
	'EMAILADDRESS' => array(
		"sailvan.com"		=>	'http://exmail.qq.com/login',
		"qq.com"			=>	'http://mail.qq.com',
		"vip.qq.com"		=>	'http://mail.vip.qq.com',
		"foxmail.com"		=>	'http://www.foxmail.com',
		"163.com"			=>	'http://mail.163.com',
		"gmail.com"			=>	'http://gmail.google.com',
		"126.com"			=>	'http://www.126.com',
		"yahoo.com"			=>	'http://mail.yahoo.com',
		"yahoo.com.cn"		=>	'http://mail.yahoo.com',
		"sohu.com"			=>	'http://mail.sohu.com',
		"sina.com"			=>	'http://mail.sina.com.cn',
		"aliyun.com"		=>	'http://mail.aliyun.com',
		"tom.com"			=>	'http://web.mail.tom.com',
		"outlook.com"		=>	'http://www.outlook.com',
		"139.com"			=>	'http://mail.10086.cn',
		"189.cn"			=>	'http://mail.189.cn',
		"21cn.com"			=>	'http://mail.21cn.com',
		"263.com"			=>	'http://mail.263.com',
		"263.net"			=>	'http://mail.263.net',
		"263.net.cn"		=>	'http://mail.263.net',
		"wo.com.cn"			=>	'http://mail.wo.com.cn/mail/login.action',
		"wo.cn"				=>	'http://mail.wo.com.cn',
		"188.com"			=>	'http://www.188.com',
		"yeah.net"			=>	'http://yeah.net',
		"hotmail.com"		=>	'http://mail.live.com',
		"cntv.cn"			=>	'http://mail.cntv.cn',
		"eyou.com"			=>	'http://mail.eyou.com',
		"mail.com"			=>	'http://mail.com',
		"4399.com"			=>	'http://mail.4399.com',
		"china.com"			=>	'http://mail.china.com',
		"china-channel.com"	=>	'http://mail.35.com',
		"aol.com"			=>	'http://mail.aol.com',
		"**.edu.cn"			=>	'http://mail.**.edu.cn',
	),

	'COUNTRY_MAP'	=> array(
		'CN'	=> 	'China',
		'US'	=>	'USA',
		'AU'	=>	'Australia',
		'DE'	=>	'Germany',
		'MY'	=>	'Malaysia',
		'SG'	=>	'Singapore',
		'HK'	=>	'HongKong',
	),
	'CANCELORDERSTATUS'  =>   '5',
	'NOAUDITORDERSTATUS' =>   '1',
	//出账状态
	"BILL_STATUS"		 =>	array(
		"##"	=>	"账单状态",
		"1"		=>	"待初审",
		"2"		=>	"待复审",
		//"3"		=>	"待对账",//已删除
		"4"		=>	"已结账",
		"5"		=>	"初审不通过",
		"6"		=>	"复审不通过",
		"7"		=>	"废弃账单",
		"8"		=>	"待付款",
	),
	//前台暴露给用户的账单状态
	"BILL_STATUS_USER"	=>	array(
		"###"	=>	"账单状态",
		"4"		=>	"已结账",
		"7"		=>	"废弃账单",
		"8"		=>	"待付款",
	),
	//结算类型
	"SETTLEMENT_TYPE"	=>	array(
		"##"	=>	"所有结算类型",
		"1"		=>	"预付款",
	),
	//不可以取消订单的状态
	"UNABLECANCELORDERSTATUS"	=>	array("6","7","10","12"),
    // 不可暂停订单的状态
    "UNABLEHOLDSTATUS"          => array('1','2','6','8','7','10','12','13'),
    
    "UNABLERESETSTATUS"         => array('1','2','3','4','5','6','7','9', '10','11','12','13'),
    
	"UNSYNCHRONOUS"             => array('1'),
	
	"DELIVERY_ORDER_STATUS"	=>	array("2","3","4","5","6","8","13","11","14"),//订单已预付款但是未发货的状态
	"SPLITSTATUS"	=>	array(
		"##"	=>	"是否拆分",
		"1"		=>	"是",
		"2"		=>	"否",
	),
	"FUNDS_TYPE"	=> array(
		'1'		=>	"充值付款",//额户充值产生的交易
		'2'		=>	"预扣款", //订单审核通过后推送订单系统成功预扣款产生的交易
		'3'		=>	"账单另扣款",//账单复审通过后异常款项扣款产生
		'4'		=>	"退款-取消账单",//重新出账原账单作废产生的退款
		'5'		=>	"退款-取消交易",//用户取消交易产生的原订单预扣款退回
		'6'		=>	"退款-邮局退回",//邮局退回产生的货本或货本加邮费退款
		'7'		=>	"退款-拆分订单",//订单拆分退款
		'8'		=>	"其他-快递费用调整",//后台业务人员导入的
		'9'		=>	"订单已发货补扣款",//订单已发货后实际款与预扣款之间的差额
		'10'	=>	"其他-邮局丢包",//后台业务人员导入的邮局丢包订单退款
	),
	"FUNDS_ORDER_TYPE" => array(
		'1'		=> "订单号",
		'2'		=> "账单号",
		'3'		=> "充值流水号"
	),
	//公司银行账号信息
	"COMPANY_BANK_ACCOUNT"	=> array(
		'1'	=> array(
			'bank'	=> '招商银行',
			'account' => '陈南香',
			'card'	=> '6333 4444 5555 6666 777',
		),
		'2' => array(
			'bank'	=> '建设银行',
			'account' => '陈浩南',
			'card'	=> '6333 4444 5555 6666 555',
		)
	),
    "COMPANY_ANDIT_STATUS" => array(
    	1 => "申请中",
    	2 => "审核通过",
    	3 => "审核不通过",
    ),
    "GROUP_TYPE" => array(
    	1 => "公司",
    	2 => "团队",
    	3 => "个人",
    ),
    "IMPORT_TYPE" => array(
    	1 => "url抓取",
    	2 => "api导入",
    	3 => "手动创建",
    ),
    "VALSUN_CONF"	=> array(
    	"appKey"	=> '7SPHW_TXNGZ_ND4ZI_WNUVV_T4HF0',
    	"appToken"	=> 'fb8ba39df9c6cbdee8478510d2358000',
    ),
    "IMAGE_SYS_ADDR" => 'http://image.huanhuan365.com',
    'CAHCE_FILE_DIR' => WEB_PATH."log/",//缓存文件目录
);