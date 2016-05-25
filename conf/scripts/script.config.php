<?php
	define('SCRIPT_ROOT', WEB_PATH.'crontab/');
	define('SCRIPT_DATA_LOG','/home/ebay_order_cronjob_logs/');
	define('SCRIPT_ROOT_LOG', '/data/scripts/ebay_order_cronjob_logs/');
	define('EBAY_RAW_DATA_PATH','/data/scripts/ebay_order_cronjob_raw_data/');
	
	define('WEB_PATH_LIB', WEB_PATH.'lib/');
	
	define('WEB_PATH_LIB_RABBITMQ', WEB_PATH_LIB.'rabbitmq/');
	
	define('WEB_PATH_LIB_SCRIPTS', WEB_PATH_LIB.'scripts/');
	define('WEB_PATH_LIB_SCRIPTS_EBAY', WEB_PATH_LIB_SCRIPTS.'ebay/');
	define('WEB_PATH_LIB_SCRIPTS_TAOBAO', WEB_PATH_LIB_SCRIPTS.'taobao/');
	define('WEB_PATH_LIB_SCRIPTS_ALIEXPRESS', WEB_PATH_LIB_SCRIPTS.'aliexpress/');
	define('WEB_PATH_LIB_SCRIPTS_AMAZON', WEB_PATH_LIB_SCRIPTS.'amazon/');
	define('WEB_PATH_LIB_SCRIPTS_DRESSLINK', WEB_PATH_LIB_SCRIPTS.'dresslink/');
	define('WEB_PATH_LIB_SCRIPTS_CNDIRECT', WEB_PATH_LIB_SCRIPTS.'cndirect/');
	
	define('WEB_PATH_LIB_SDK', WEB_PATH_LIB.'sdk/');
	define('WEB_PATH_LIB_SDK_EBAY', WEB_PATH_LIB_SDK.'ebay/');
	define('WEB_PATH_LIB_SDK_TAOBAO', WEB_PATH_LIB_SDK.'taobao/');
	define('WEB_PATH_LIB_SDK_ALIEXPRESS', WEB_PATH_LIB_SDK.'aliexpress/');
	define('WEB_PATH_LIB_SDK_AMAZON', WEB_PATH_LIB_SDK.'amazon/');
	define('WEB_PATH_LIB_SDK_DRESSLINK', WEB_PATH_LIB_SDK.'dresslink/');
	define('WEB_PATH_LIB_SDK_CNDIRECT', WEB_PATH_LIB_SDK.'cndirect/');
	
	define('WEB_PATH_CONF', WEB_PATH.'conf/');
	define('WEB_PATH_CONF_SCRIPTS', WEB_PATH_CONF.'scripts/');
	define('WEB_PATH_CONF_SCRIPTS_KEYS', WEB_PATH_CONF_SCRIPTS.'keys/');
	define('WEB_PATH_CONF_SCRIPTS_KEYS_EBAY', WEB_PATH_CONF_SCRIPTS_KEYS.'ebay/');
	define('WEB_PATH_CONF_SCRIPTS_KEYS_TAOBAO', WEB_PATH_CONF_SCRIPTS_KEYS.'taobao/');
	define('WEB_PATH_CONF_SCRIPTS_KEYS_ALIEXPRESS', WEB_PATH_CONF_SCRIPTS_KEYS.'aliexpress/');
	define('WEB_PATH_CONF_SCRIPTS_KEYS_AMAZON', WEB_PATH_CONF_SCRIPTS_KEYS.'amazon/');
	
	define('HTML_INCLUDE','/home/html_include/');
	//########configuration[php fetch_certain_php]##########
	define('PHP_CMD_FETCH_ORDER_CERTAIN', ' /usr/bin/php '.SCRIPT_ROOT.'ebay/fetch_order_certain.php ');
	define('LOG_PATH_FETCH_ORDER_CERTAIN', SCRIPT_ROOT_LOG.'fetch_order_certain/%s/'.date('Y-m').'/'.date('d').'/');
?>