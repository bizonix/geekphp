<?php
/*
 * 相关通用函数
* @add by wcx ,date 20140704
*/

/**
 * 根据后图片名称获取分销商图片信息目录下图片名称+.+图片后缀名称
 * @param int $id 平台编号
 * @return string
 * @author wcx
 */
function get_getSuffixByName($name,$userEmail=''){
    if(empty($userEmail)){
        $loginName  =   _authcode($_COOKIE['hcUser']);
        $loginName  =   json_decode($loginName,true);
        $loginName  =   $loginName['email'];
    }else{
        $loginName  =   $userEmail;
    }
    $baseDir    =   C("DISTRIBUTOR_KEY_PICTURE_DIR").$loginName."/";
    $idCardUrl  =   $baseDir.$name;
    $tmpPic     =   glob($idCardUrl."*");
    $tmpPic     =   @$tmpPic[0];
    $picName    =   explode('/',$tmpPic);
    $picName    =   array_pop($picName);
    if(empty($picName)){
    	return '';
    }
    return $picName;
}

/**
 * 根据后图片名称删除分销商图片信息目录下图片
 * @param int $id 平台编号
 * @return string
 * @author wcx
 */
function del_picByName($name,$userName = ''){
	if(!isset($userName) || $userName == ''){		//add by yyn
		$loginName  =   _authcode($_COOKIE['hcUser']);
		$loginName  =   json_decode($loginName,true);
		$loginName  =   $loginName['email'];
	} else {
		$loginName	=	$userName;
	}
    $baseDir    =   C("DISTRIBUTOR_KEY_PICTURE_DIR").$loginName."/";
    $idCardUrl  =   $baseDir.$name;
    $tmpPic     =   glob($idCardUrl."*");
    if(empty($tmpPic)){
         return true;
    }
    $tmpPic     =   $tmpPic[0];
    if(is_file($tmpPic)){
        return unlink($tmpPic);
    }
    return true;
}
/**
 * 下载文件
 * @param path 文件路径
 * @return name 名字
 * @author wcx
 */
function downFile($path,$name){
    if(!file_exists($path)){   //检查文件是否存在
        echo   "文件找不到";
        exit;
    }else{
        $file = fopen($path,"r"); // 打开文件
        // 输入文件标签
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: ".filesize($path));
        Header("Content-Disposition: attachment; filename=" . $name);
        // 输出文件内容
        echo fread($file,filesize($path));
        fclose($file);
        exit();
    }
}