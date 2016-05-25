<?php

$res = mail("820576704@qq.com",'the subject','the message',null,'-fwebmaster@example.com');
if($res){
	echo 'true';
}else{
	echo "false";
}