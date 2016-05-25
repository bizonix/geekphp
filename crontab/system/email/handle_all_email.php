<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
include_once WEB_PATH.'lib/PHPMailer/sendEmail.php';

$whereData = array("status"=>1);
$count = M("EmailQueue")->getDataCount($whereData);
//设置每页的数据
$perPage = 100;		
$page = ceil($count/$perPage);
for ($i=1;$i<=$page;$i++){
	$emails	 = M("EmailQueue")->getData("*",$whereData,' order by id asc ',1,$perPage);
	foreach ($emails as $k => $v) {
		$toEmail = array();
		$toCC    = array();
		if(validate_email($v["email"])){
			$toEmail[] = array("email"=>$v["email"]);
		}
		$template = M("EmailTemplates")->getSingleData("*","id={$v['template_id']}");
		$sendmail = sendEmail($toEmail, $template["title"], $template["content"],$toCC,'huanhuan');
		if(strlen($sendmail) > 1) {     //如果邮件发送失败，则将错误信息返回到$sendmail变量内，
			$updateData = array(
				"update_time" => time(),
				"status"	  => 3, //邮件发送失败
				"note"		  => $sendmail
			);
		}else{
			$updateData = array(
				"update_time" => time(),
				"status"	  => 2, //邮件发送成功
			);
		}
		M("EmailQueue")->updateData($v["id"],$updateData);

	}

}

