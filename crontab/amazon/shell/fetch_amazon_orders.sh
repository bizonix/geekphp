#!/bin/sh
#This shel run every  15 min
sh_argc="$#";
if [ $sh_argc != 3 ]; then
	echo "Usage: ./$0 ebay_account minute_range";
	exit
fi
ebay_account="$1";
site="$3";
year_month=`date -d "today" +"%Y-%m"`;
today=`date -d "today" +"%d"`;
logs_path="/home/ebay_order_cronjob_logs/fetch_amazon_orders/${site}/${ebay_account}/${year_month}/${today}/";
log_name=`date -d "today" +"%Y-%m-%d_%H-%M-%S"`.log;
if [ ! -d "$logs_path" ]; then
	mkdir -p "$logs_path"
fi
/usr/local/bin/php /data/web/order.valsun.cn/crontab/amazon/fetch_amazon_orders.php ${ebay_account} $2 $3 >> ${logs_path}$log_name
