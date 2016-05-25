#!/bin/sh
if test $( pgrep -f 'auto_contrast_intercept.php' | wc -l ) -eq 0
then
	/usr/local/bin/php /data/web/order.valsun.cn/crontab/ebay/auto_contrast_intercept.php >> /home/ebay_order_cronjob_logs/auto_contrast_intercept/auto_contrast_intercept.log
fi
#/usr/local/bin/php /data/scripts/ebay_order_cron_job/split_part_intercept.php >> /home/ebay_order_cronjob_logs/auto_contrast_intercept/split_part_intercept.log
#/usr/local/bin/php /data/scripts/ebay_order_cron_job/out_stock_intercept.php >> /home/ebay_order_cronjob_logs/auto_contrast_intercept/out_stock_intercept.log
