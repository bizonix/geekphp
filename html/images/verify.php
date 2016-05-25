<?php

/**
 * 随机码
 * @param int $length
 * @return string $string
 */
function random_code($length=8) {
	srand();
	$possible_charactors = "0123456789";
	$string = "";
	while(strlen($string)<$length) {
		$string .= substr($possible_charactors,(rand()%(strlen($possible_charactors))),1);
	}
	return($string);
}

ob_clean();  //关键代码，防止出现'图像因其本身有错无法显示'的问题。
Header("Content-type: image/PNG");
session_start();
$str=strtoupper(random_code(4)); //随机生成的字符串 
$width = 100; //验证码图片的宽度 
$height = 30; //验证码图片的高度 
setcookie('verifycode', $str,time()+3600,"/");
$_SESSION["verifycode"] = $str;  
$im=imagecreate($width,$height);//要先开启GD库,解决办法:在php.ini中找到;extension=php_gd2.dll去掉前边的分号 	 
$back=imagecolorallocate($im,0xFF,0xFF,0xFF); //背景色
$pix=imagecolorallocate($im,231,248,252); //模糊点颜色	 
//$font=imagecolorallocate($im,41,163,238); 
$font=imagecolorallocate($im,238,0,148); //字体色
$font2=imagecolorallocate($im,255,255,255);  
mt_srand(); //绘模糊作用的点
for($i=0;$i<1000;$i++) { 
	imagesetpixel($im,mt_rand(0,$width),mt_rand(0,$height),$pix); 
} 
imagestring($im, 5, 4, 3,$str, $font); 
imagerectangle($im,0,0,$width-1,$height-1,$font2); 

imagepng($im); 
imagedestroy($im);
?>