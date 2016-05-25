<?php
/*
* 利用PHPMailer发送邮件
*/
function sendEmail($to,$subject = "",$body = "",$tocc = array(),$from='') {
    //Author:Jiucool WebSite: http://www.jiucool.com 
    //$to 表示收件人地址 $subject 表示邮件标题 $body表示邮件正文
    //error_reporting(E_ALL);
    error_reporting(E_STRICT);
    date_default_timezone_set("Asia/Shanghai");//设定时区东八区
    include_once('class.phpmailer.php');
    include_once("class.smtp.php"); 
    $mail			= new PHPMailer(); //new一个PHPMailer对象出来
    $body			= eregi_replace("[\]",'',$body); //对邮件内容进行必要的过滤
    $mail->CharSet	= "UTF-8";//设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	$mail->Encoding	= "base64";		//编码方式
    $mail->IsSMTP(); // 设定使用SMTP服务
    $mail->SMTPDebug	= 0;       // 启用SMTP调试功能
	                                  // 1 = errors and messages
                                           // 2 = messages only
    $mail->SMTPAuth		= true;                  // 启用 SMTP 验证功能
    /*$mail->SMTPSecure	= "ssl";  // 安全协议，要把php.ini的openssl扩展打开extension=php_openssl.dll	//此处备用
    $mail->Host			= "smtp.gmail.com";      // SMTP 服务器
    $mail->Port			= 465;                   // SMTP服务器的端口号 465 or 587
    $mail->Username		= "valsunnet@gmail.com";  // SMTP服务器用户名
    $mail->Password		= "sailvan!@#$";            // SMTP服务器密码
	$mail->From			= 'valsunnet@gmail.com';
	$mail->FromName		= C("COMPANYNAME");
    $mail->SetFrom('valsunnet@gmail.com', C("COMPANYNAME"));
    $mail->AddReplyTo("valsunnet@gmail.com",C("COMPANYNAME"));*/
    $fromName       = empty($from) ? C("COMPANYNAME") : $from;
	//勿删
	$emailInfo		= C("EMAIL");
    $mail->Host		= $emailInfo['smtp'];		//"smtp.exmail.qq.com";      // SMTP 服务器
    $mail->Port		= $emailInfo['port'];		// SMTP服务器的端口号 465 or 587
    $mail->Username	= $emailInfo['user'];		//"valsun@sailvan.com";  // SMTP服务器用户名
    $mail->Password	= $emailInfo['password'];	// SMTP服务器密码
    $mail->SetFrom($emailInfo['user'], $fromName);
    $mail->AddReplyTo($emailInfo['user'], $fromName);
	$mail->From			= $emailInfo['user'];
	$mail->FromName		= $fromName;
	
	$mail->IsHTML(true); 
    $mail->Subject		= $subject;
    $mail->AltBody		= "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
	$mail->MsgHTML($body);
    //$mail->Body = $body;
    $address = $to;
	foreach($address as $key => $addr) {
    	$mail->AddAddress($addr['email'], '');
	}
	//添加抄送人
	if(count($tocc) > 0) {
		foreach($tocc as $cc) {
			$mail->addCC($cc['email']);
		}
	}
    //$mail->AddAttachment("images/phpmailer.gif");      // attachment 
    //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
    if(!$mail->Send()) {
        //echo "Mailer Error: " . $mail->ErrorInfo;
		$errStr = "Mailer Error: " . $mail->ErrorInfo;
		Log::write($errStr, 'NOTES');
		return $errStr;
    } else {
     //   echo "Message sent!恭喜，邮件发送成功！";
		return true;
    }

}