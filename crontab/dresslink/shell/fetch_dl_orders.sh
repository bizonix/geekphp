#!/bin/sh
#This shel run every  15 min
sh_argc="$#";
if [ $sh_argc != 0 ]; then
	echo "Usage: ./$0 ";
	exit
fi
year_month=`date -d "today" +"%Y-%m"`;
today=`date -d "today" +"%d"`;
logs_path="/home/ebay_order_cronjob_logs/CNDL/DL/${year_month}/${today}/";
log_name=`date -d "today" +"%Y-%m-%d_%H-%M-%S"`.log;
if [ ! -d "$logs_path" ]; then
	mkdir -p "$logs_path"
fi
/usr/local/bin/php /data/web/order.valsun.cn/crontab/dresslink/fetch_DL_orders.php >> ${logs_path}$log_name
