<?php
/*
 * 系统共用函数页面
 * @modify by lzx ,date 20140528
 */

/**
 * author:Herman.Xi
 * date:2012/3/23
 * last Modified:2013/06/20
 * 调用Google API 中文到英文互相翻译，默认，中文翻译成英文
 */
function google_translate($text, $fromLanguage = 'zh-cn', $toLanguage = 'en') {
	if (empty ($text))
		return false;
	$language = "{$fromLanguage}|{$toLanguage}";
	@ set_time_limit(0);
	$html = "";
	$ch = curl_init("http://translate.google.com/?langpair=" . urlencode($language) . "&text=" . urlencode($text));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$html = curl_exec($ch);
	if (curl_errno($ch))
		$html = "";
	curl_close($ch);
	if (!empty ($html)) {
		$x = explode("</span></span></div></div>", $html);
		$x = explode("onmouseout=\"this.style.backgroundColor='#fff'\">", $x[0]);
		return $x[1];
	} else {
		return false;
	}
}

/*
 * 获取图片
 */
function getImages($spu,$companyId){
	$spu = changeValsunSpu($spu);
	$ch = curl_init(C("IMAGE_SYS_ADDR")."/all/{$companyId}/{$spu}");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$res = curl_exec($ch);
	if (curl_errno($ch))
		$res = false;
	curl_close($ch);
	return $res;
}

/**
 * 料号转换
 */
function changeValsunSpu($spu){
	$firstPre	= substr($spu,0,2);
	//料号转换
	switch($firstPre){
		case "SV":
			$spu = str_replace("SV","WC",$spu);
			break;
		case "CB":
			$spu = str_replace("CB","WB",$spu);
			break;
		case "WC":
		case "WD":
			break;
		default:
			$spu = "WD".$spu;
	}
	return $spu;
}

/**
 * 简单的重定向函数 add by xiaojinhua
 * @param string 需要跳转的链接地址
 * @modify author lzx
 */
function redirect_to($location='') {
	if ($location != NULL) {
		header("Location: {$location}");
		exit;
	}
}

//生成app_key
function random_app_key($length=5)
{
	srand();
	$possible_charactors = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$app_key = '';
	for($i=1;$i<=5;$i++)
	{
		$string = "";
		while(strlen($string)<$length) {
			$string .= substr($possible_charactors,(rand()%(strlen($possible_charactors))),1);
		}
		$app_key .= $string.'_';
	}
	$app_key = rtrim($app_key,'_');
	return($app_key);
}

/**
 * 字符串、数组转化为标准的数组
 * @param string|array $array
 * @return array
 * @author lzx
 */
function format_array($var){
	if (empty($var)){
		return array();
	}else if(is_array($var)){
		return $var;
	}else if(is_numeric($var)){
		return array($var);
	}else if(is_string($var)){
		return strpos($var, ',')!==false ? implode(',', $var) : array($var);
	}else{
		return array();
	}
}

function write_log($file, $content) {
	$filepath = dirname($file);
	if (!is_dir($filepath)){
		mkdirs($filepath);
	}
	if (!$handle = fopen($file, 'a')) {
		return false;
	}
	if (flock($handle, LOCK_EX)) {
		if (fwrite($handle, $content) === FALSE) {
			return false;
		}
		flock($handle, LOCK_UN);
	}
	fclose($handle);
	return true;
}

function mkdirs($path,$i=0) {
	$path_out = preg_replace('/[^\/.]+\/?$/', '', $path);
	if (!is_dir($path_out)) {
		if($i<50){
			mkdirs($path_out,++$i);
		}
	}
	mkdir($path);
}

function write_a_file($file, $data) {
	$tmp_dir = dirname($file);
	if (!is_dir($tmp_dir)) {
		mkdirs($tmp_dir);
	}
	if (!$handle = fopen($file, 'a')) {
		return false;
	}
	if (flock($handle, LOCK_EX)) {
		if (fwrite($handle, $data) === FALSE) {
			return false;
		}
		flock($handle, LOCK_UN);
	}
	fclose($handle);
	return true;
}

function write_w_file($file, $data) {
	$tmp_dir = dirname($file);
	if (!is_dir($tmp_dir)) {
		mkdirs($tmp_dir);
	}
	if (!$handle = fopen($file, 'w')) {
		return false;
	}
	if (flock($handle, LOCK_EX)) {
		if (fwrite($handle, $data) === FALSE) {
			return false;
		}
		flock($handle, LOCK_UN);
	}
	fclose($handle);
	return true;
}

function read_file($file) {
	if (!is_file($file)) {
		return false;
	}
	return file_get_contents($file);
}

function read_and_empty_file($file) {
	if (!is_file($file)) {
		return false;
	}
	$contents = file_get_contents($file);
	if (!$handle = fopen($file, 'w')) {
		return false;
	}
	return $contents;
}

function round_num($f, $n) {
	$num = pow(10, $n);
	$intn = intval(round($f * $num));
	$r = $intn / $num;
	$r = $r +0.00001;
	return str_replace(',', '', number_format($r, 2));
}
/**
 * 查找带分隔符的字符中的某个字符
 * 例如：instr("1,2,11,23,34",3) 返回结果是false;
 * @param string $mystring
 * @param string $findme
 * @return boolean
 * @author yxd
 */
function instr($mystring,$findme){
	$myarray    = explode(",", $mystring);
	if(in_array($findme, $myarray))
		return true;
	else
		return false;
}
function multi2single($key, $arrays) {
	$results = array ();
	foreach ($arrays AS $array) {
		array_push($results, $array[$key]);
	}
	return $results;
}

/*
 * 获得指定sku是单料号还是组合料号及对应的真实sku及对应数量，
 * 如果在memcache中未找到直接返回false，如果在memcache中存在，
 * 返回格式为array('sku'=>array('$sku1'=>$nums1,'$sku2'=>nums2,...),'isCombine'=>1),
 * 其中sku键值对应的是真实料号及数量的键值对，isCombine键值对应是否是组合料号，1为组合料号，0为单料号
*/
function getRealSkuAndNums($sku) {
	global $memc_obj; //调用memcache对象
	$skuArr = array (
		'sku' => array (
			$sku => 1
		),
		'isCombine' => 0
	); //默认为单料号
	$skuInfo = $memc_obj->get_extral("sku_info_" . $sku);
	if (empty ($skuInfo)) {
		return false;
	}
	if (isset($skuInfo['sku']) && is_array($skuInfo['sku'])) { //如果为组合料号时
		$tmpArr = array ();
		foreach ($skuInfo['sku'] as $key => $value) { //循环$skuInfo下的sku的键，找出所有真实料号及对应数量,$key为组合料号下对应的真实单料号，value为对应数量
			$tmpArr[$key] = $value;
		}
		$skuArr['sku'] = $tmpArr;
		$skuArr['isCombine'] = 1;
	}
	return $skuArr;
}

//检查跟踪号是否有效
function validate_trackingnumber($num){
	if(empty($num)||preg_match("/^0/", $num)){
		return false;
	}else{
		return true;
	}
}

//生成随机数
function round_num2($f, $n){
	$num = pow(10, $n);
	$intn = intval(round($f*$num));
	$r = $intn/$num;
	$r = $r + 0.00001;
	return number_format($r,2);
}

function calceveryweight($weightarray, $totalfee){
	$feearray = array();
	$totalweight = array_sum($weightarray);
	foreach ($weightarray AS $weight){
		$feearray[] = round(($totalfee*$weight/$totalweight), 2);
	}
	return $feearray;
}

/**
 * 根据状态码code获取状态码名称
 * @param int $cid 状态码code
 * @return string
 * @author lzx
 */
function get_statusmenunamebyid($cid){
    $statusmenu = A('StatusMenu')->act_getStatusMenuByCode($cid);
	return $statusmenu['statusName'];
}

/**
 * 获取当前用户信息
 * @return string
 * @author wcx
 */
function get_userinfo($index=''){
	$data = json_decode(_authcode($_COOKIE['user']),true);
	if(empty($index)) return $data;
	else return $data[$index];
}

/**
 * 获取当前用户id
 * @return int
 * @author wcx
 */
function get_userid(){
	return get_userinfo('id');
}

/**
 * 获取当前用户用户名
 * @return int
 * @author wcx
 */
function get_username(){
	return get_userinfo('user_name');
}

/**
 * 获取当前用户邮箱
 * @return int
 * @author wcx
 */
function get_useremail(){
	return get_userinfo('email');
}

/**
 * 获取当前用户所属公司
 * @return int
 * @author wcx
 */
function get_usercompanyid(){
	return get_userinfo('company_id');
}

/**
 * 验证日期是否合法
 * @param date $date
 * @return bool
 * @author lzx
 */
function validate_date($date){
	return preg_match("/^20[0-9]{2}-[0-1][0-9]-[0-3][0-9]$/", $date)>0;
}
/**
 * 验证日期和时间是否合法
 * @param datetime $datetime
 * @return bool
 * @author lzx
 */
function validate_datetime($datetime){
	return preg_match("/^20[0-9]{2}-[0-1][0-9]-[0-3][0-9]\s[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/", $datetime)>0;
}
/**
 * 验证email
 * @param string $emial
 * @return  bool
 * @author yxd
 */
function validate_email($email){
	return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $email);
}
/**
 * 验证字母数字-组合  12122-1212-3r
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_numTchar($value){
	return preg_match('/^[a-z0-9A-Z]+([a-z0-9A-Z]+-[a-z0-9A-Z]*)*[a-z0-9A-Z]$/', $value);
}

/**
 * 验证用户名：字母/数字/下划线 5-15位
 * @param string $value
 * @return  bool
 * @author wcx
 */
function validate_userName($value){
	return preg_match('/^[a-zA-Z][_a-zA-Z0-9]{5,15}$/', $value);
}

/**
 * 验证字母数字大写字母  3J447573WK9855335
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_numUpchar($value){
	return preg_match('/^[0-9A-Z]*[0-9A-Z]$/', $value);
}
/**
 * 验证字符和空格3J447573WK9855335
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_spaceNchare($value){
	return preg_match('/^[A-Za-z0-9][A-Za-z0-9\s]*[A-Za-z0-9]*$/', $value);
}

/**
 * 验证2为小数浮点数
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_float2($value){
	return preg_match('/^(([1-9]\d*.\d{2})|(0.\d{2}))$/', $value);
}

/**
 * 验证电话格式
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_phone($value){
	return preg_match('/^\d{5,11}$|^(\+|\d)((\d{1,4})(\s|-)\d{1,11})*\d$/', $value);
}

/**
 * 验证邮编格式
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_zipCode($value){
	return preg_match('/^[A-Za-z0-9]*[\s\-]?[A-Za-z0-9]*$/', $value);
}

/**
 * 验证3为小数浮点数
 * @param string $value
 * @return  bool
 * @author yxd
 */
function validate_float3($value){
	return preg_match('/^(([1-9]\d*.\d{3})|(0.\d{3}))$/', $value);
}

/**
 * 驼峰类命名方式转化为下划线命名方式
 * @param string $hname
 * @return string
 * @author lzx
 */
function hump2underline($hname){
	return str_replace(
			array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"),
			array("_a", "_b", "_c", "_d", "_e", "_f", "_g", "_h", "_i", "_j", "_k", "_l", "_m", "_n", "_o", "_p", "_q", "_r", "_s", "_t", "_u", "_v", "_w", "_x", "_y", "_z"), $hname);
}
/**
 * 下划线命名方式转化为驼峰类命名方式
 * @param string $uname
 * @author lzx
 */
function underline2hump($uname){
	return str_replace(
				array("_a", "_b", "_c", "_d", "_e", "_f", "_g", "_h", "_i", "_j", "_k", "_l", "_m", "_n", "_o", "_p", "_q", "_r", "_s", "_t", "_u", "_v", "_w", "_x", "_y", "_z"),
				array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"), $uname);
}

/**
 * 根据提示码获取提示信息
 * string get_promptmsg ( string $msgNum, mixed var [, mixed ...] )
 * @param int $msgNum 提示信息编号
 * @param string $var 替换变量
 * @param .... 多变量扩展
 * @global $dbConn 数据库对象全局变量
 * @global $memc_obj 缓存对象全局变量
 * @return string
 * @author lzx
 */
function get_promptmsg($msgNum){
	$msgNum  = intval($msgNum);

	$numargs = func_num_args();
    if ($numargs===0){
        return get_promptmsg(10004);
    }
    if ($msgNum===0){
    	 return get_promptmsg(10015);
    }
    $arg_list = func_get_args();
	if (!isset(C("SYSTEM_ERRORCODE")[$msgNum])){
		return get_promptmsg(10016, $msgNum);
	}
	$arg_list[0] = C("SYSTEM_ERRORCODE")[$msgNum];
	if($msgNum===200 || $numargs===1){
	    return preg_replace('/\%s/','',$arg_list[0]);
	}
	return call_user_func_array('sprintf',$arg_list);
}

/**
 * 数组转为字符串数组
 * @param array $array
 * @return array
 * @author lzx
 */
function array2strarray($array) {
	$results = array ();
	foreach ($array AS $_k => $_v) {
		$results[$_k] = "'{$_v}'";
	}
	return $results;
}

/**
 * 数组转化为http请求参数
 * @param array $array
 * @return string
 * @author lzx
 */
function array2http($array){
	$param = array();
	foreach ($array as $key => $val) {
		$param[] = "{$key}=".urlencode($val);
	}
	return implode('&', $param);
}

/**
 * 数组转化为查询语句
 * @param array $data
 * @return string
 * @author lzx
 */
function array2sql($array) {
	$sql_array = array ();
	foreach ($array AS $_k => $_v) {
		if (empty ($_k)) {
			continue;
		}
		$_v = trim($_v);
		if (ctype_digit($_v) && preg_match("/^[1-9][0-9]+$/", $_v)) {
			$sql_array[] = "`{$_k}`={$_v}";
		} else {
			$sql_array[] = "`{$_k}`='{$_v}'";
		}
	}
	return implode(',', $sql_array);
}

/**
 * 数组转化为查询语句,键累加,用于原有数据基础累积增加
 * @param array $data
 * @return string
 * @author lzj
 */
function array3sql($array) {
	$sql_array = array ();
	foreach ($array AS $_k => $_v) {
		if (empty ($_k)) {
			continue;
		}
		if(!is_array($_v)) {
			$_v = trim($_v);
			if (ctype_digit($_v) && preg_match("/^[1-9][0-9]+$/", $_v)) {
				$sql_array[] = "`{$_k}`={$_v}";
			} else {
				$sql_array[] = "`{$_k}`='{$_v}'";
			}
		}else{
			if(!empty($_v['+']) && $_v['+'] == true && empty($_v['-'])) {
				$sql_array[] = "`{$_k}`=`{$_k}`+{$_v['value']}";
			}elseif(!empty($_v['-']) && $_v['-'] == true && empty($_v['+'])) {
				$sql_array[] = "`{$_k}`=`{$_k}`-{$_v['value']}";
			}else {
				return '格式出错';
			}
		}
	}
	
	return implode(',', $sql_array);
}

/**
 * 数组转化为查询语句
 * @param array $data
 * @return string
 * @author wcx
 */
function array3where($array) {
	$sql_array = array ();
	foreach ($array AS $_k => $_v) {
		if (empty ($_k)) {
			continue;
		}
		$_v = trim($_v);
		if (ctype_digit($_v) && preg_match("/^[1-9][0-9]+$/", $_v)) {
			$sql_array[] = "`{$_k}`={$_v}";
		} else {
			$sql_array[] = "`{$_k}`='{$_v}'";
		}
	}
	return implode(' and ', $sql_array);
}
/**
 * 数组转化为查询简单条件
 * @param array $data
 * @return array
 * @author lzx
 */
function array2where($data, $pre=''){
	$sqlarr = array();
	foreach ($data AS $key=>$value){
		if(is_array($value)){
			foreach ($value AS $k=>$v){
				if($k=='$gte'){
					$sqlarr[] = is_numeric($v) ? "{$key}>={$v}" : "{$key}>='{$v}'";
				}else if($k=='$gt'){
					$sqlarr[] = is_numeric($v) ? "{$key}>{$v}" : "{$key}>'{$v}'";
				}else if($k=='$lt'){
					$sqlarr[] = is_numeric($v) ? "{$key}<{$v}" : "{$key}<'{$v}'";
				}else if($k=='$lte'){
					$sqlarr[] = is_numeric($v) ? "{$key}=<{$v}" : "{$key}=<'{$v}'";
				}else if($k=='$ne'){
					$sqlarr[] = is_numeric($v) ? "{$key}!={$v}" : "{$key}!='{$v}'";
				}else if($k=='$e'){
					$sqlarr[] = is_numeric($v) ? "{$key}={$v}" : "{$key}='{$v}'";
				}else if($k=='$b'){
					list($starttime, $endtime) = explode('-', $v);
					$sqlarr[] = "{$key} BETWEEN {$starttime} AND {$endtime}";
				}else if($k=='$in'){
					$v_str = is_array($v) ? implode(',', $v) : $v;
					$sqlarr[] = "{$key} IN ({$v_str})";
				}else if($k=='$nin'){
					$v_str = is_array($v) ? implode(',', $v) : $v;
					$sqlarr[] = "{$key} NOT IN ({$v_str})";
				}
			}
		}
	}
	if (!empty($pre)) array_walk($sqlarr , 'add_tableprefix', $pre);
	return $sqlarr;
}

/**
 * array2where函数调用，用来自动增加前缀
 * Enter description here ...
 * @param string &$field
 * @param string $key
 * @param string $prefix
 * @return null
 * @author lzx
 */
function add_tableprefix(&$field, $key, $prefix){
	$field="{$prefix}.$field";
}

/**
 * 获取请求IP
 * @return string 用户IP地址
 * @author lzx
 */
function get_ip() {
	if (isset($_SERVER)) {
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$realip = $_SERVER["HTTP_CLIENT_IP"];
		} else {
			$realip = $_SERVER["REMOTE_ADDR"];
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$realip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$realip = getenv('HTTP_CLIENT_IP');
		} else {
			$realip = getenv('REMOTE_ADDR');
		}
	}

	$realip_arr = explode(',' , $realip) ;
	$realip = (isset($realip_arr[0]) && $realip_arr[0] != 'unknown') ? $realip_arr[0] : '0.0.0.0' ;
	return $realip ;
}

/**
 * 传入参数为中国重庆时区时间戳
 * @param int $time
 * @return array
 * @author lzx
 */
function get_ebay_timestamp($time){
	return	$time-(3600*8);
}

function encode_pass($tex,$key,$type="encode"){
	$chrArr=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
	'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
	'0','1','2','3','4','5','6','7','8','9');
	if($type=="decode"){
		if(strlen($tex)<9)return false;
		$verity_str=substr($tex, 0,3);
		$tex=substr($tex, 3);
		if($verity_str!=substr(md5($tex),0,3)){
		//完整性验证失败
			return false;
		} 
	}
	$key_b=$type=="decode"?substr($tex,0,6):$chrArr[rand()%62].$chrArr[rand()%62].$chrArr[rand()%62].$chrArr[rand()%62].$chrArr[rand()%62].$chrArr[rand()%62];
	$rand_key=$key_b.$key;
	$rand_key=md5($rand_key);
	$tex=$type=="decode"?base64_decode(substr($tex, 6)):$tex;
	$texlen=strlen($tex);
	$reslutstr="";
	for($i=0;$i<$texlen;$i++){
		$reslutstr.=$tex{$i}^$rand_key{$i%32};
	}
	if($type!="decode"){
		$reslutstr=trim($key_b.base64_encode($reslutstr),"==");
		$reslutstr=substr(md5($reslutstr), 0,3).$reslutstr;
	}
	return $reslutstr;
}

/**
 * 加密函数,需要定义密钥AUTH_KEY
 * @param string $string
 * @author wcx
 */
function _authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key ? $key : C('AUTH_KEY'));
	$keya = md5(substr($key, 0, 16));
	//$keya 密钥前16位md5加密字串
	$keyb = md5(substr($key, 16, 16));
	//$keyb 密钥后16位md5加密字串
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	//$keyc 解密时取出字符串前4个字符，加密时取出当前时间的md5加密字串后4个字符
	$cryptkey = $keya.md5($keya.$keyc);
	//$cryptkey $keya后面加keya和keyc的md5加密串
	$key_length = strlen($cryptkey);
	//$key_length = 64
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	//解密时 返回截取string ckey_length后面的字符串的 64位解码字符
	//加密时 10位数字的过期时间+(待加密字串+keyb)的md5字串前面16位+待加密字串
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);
	//0-255数组

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	//$rndkey 记录$cryptkey的asi ii码值

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}



/*
 * 上传xls文件(input的name值：$id 存放位置"cache/xls/XXX.xls",文件名XXX:"eg:time().xls",规定后缀（数组）：$suffixArr)
* 返回值：返回“Success”字符串是成功。其他的是错误
*/
/**
 * 上传文件
 * @param string $id            //$_FILE['name']
 * @param string $upload_dir    //存放位置
 * @param string $name          //文件名
 * @return string               //Success成功,其他的失败
 * @author wcx
 */
function uploadFile($id,$upload_dir,$name,$suffixArr)
{
    $msg	=	"";
    $suffixFormat	=	explode('.',$_FILES[$id]["name"]);
    $suffixFormat	=	$suffixFormat[count($suffixFormat)-1];
    //echo $suffixFormat;var_dump($suffixArr);exit;
    if(!in_array($suffixFormat, $suffixArr))
    {
        return "格式不对";
    }
    if($_FILES[$id]["name"])
    {
        $file_path = $upload_dir. $name.'.'.$suffixFormat;
    }
    if ($_FILES[$id]["error"] > 0)    //error 大于0 ,出错
    {
        switch( $_FILES[$id]["error"]){
        	case 1:
        	    $msg	=	"文件大小超出了服务器的空间大小";
        	    break;
        	case 2:
        	    $msg	=	"要上传的文件大小超出浏览器限制";
        	    break;
        	case 3:
        	    $msg	=	"文件仅部分被上传";
        	    break;
        	case 4:
        	    $msg	=	"没有找到要上传的文件";
        	    break;
        	case 5:
        	    $msg	=	"服务器临时文件夹丢失";
        	    break;
        	case 6:
        	    $msg	=	"文件写入到临时文件夹出错";
        	    break;
        }
        return $msg;
    }
    else
    {
        if(!is_dir($upload_dir))                //判断是否上传目录存在
        {
            if(!mkdir($upload_dir,0777,true))                //创建目录
            {
                $msg	=	"文件上传目录不存在并且无法创建文件上传目录";
                return $msg;
            }
            if(!chmod($upload_dir,0755))
            {
                $msg	=	"文件上传目录的权限无法设定为可读可写";
                return $msg;
            }
        }
        if($_FILES[$id]["size"] == 0)
        {
            $msg	=	"没有找到要上传的文件";
            return $msg;
        }
        if(!move_uploaded_file($_FILES[$id]["tmp_name"], $file_path))   //从临时文件夹中取出数据放到设置的文件目录下
        {
            $msg	=	"复制文件失败，请重新上传";
            return $msg;
        }
        //弹出上传信息
        $msg	=	"Success";
        return $msg;
    }
}



/**
 * 替换特殊字符（'\','''）
 * @param string $str
 * @return mixed
 * @author czq
 */
function str_rep($str){
    $str  = str_replace("'","&acute;",$str);
    $str  = str_replace("\"","&quot;",$str);
    return $str;
}

/**
 * API接口返回函数封装
 * @param int|array $message 信息编码 匹配表---om_prompt_msg
 * @param array $data
 * @author lzx
 */
function json_return($message, $data=''){
    if (is_array($message)){
        $msgNo = array_shift(array_keys($message));
        $msg = implode('<br>', $message);
    }else{
        $msgNo   = intval($message);
        $numargs = func_num_args();
        if ($numargs<3){
            $msg = get_promptmsg($msgNo);
        }else{
            $arg_list = func_get_args();
            $varlist  = array_slice($arg_list, 2);
            $varstr   = implode(',', array2strarray(array_map('addslashes', array_map('htmlspecialchars', $varlist))));
            eval("\$msg = get_promptmsg({$msgNo}, {$varstr});");
        }
    }
    echo json_encode(array("errCode"=>$msgNo, "errMsg"=>$msg, "status"=>$msg=='Success' ? true : false, "data"=>$data));
    exit;
}

/**
 * 对象转为数组
 * @param object $obj
 * @return array
 * @author czq
 */
function object_array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	}
	if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object_array($value);
		}
	}
	return $array;
}

/**
 * 根据传过来的channelId数组，组装成array('transportId'=>array(channelId1,channelId2)))的格式返回
 * @param array $channelIdArr
 * @return array
 * @author zqt
 */
function returnTCArrFromChannelIdArr($channelIdArr) {
    $returnArr = array();
    $channelIdList = M('InterfaceTran')->key('id')->getChannelList();//获取所有的channel列表
    foreach($channelIdArr as $channelId){
        if(intval($channelId) > 0){
            $tmpTransportId = $channelIdList[$channelId]['carrierId'];
            if(intval($tmpTransportId) > 0){
                $returnArr[$tmpTransportId][] = $channelId;
            }
        }
    }
	return $returnArr;
}

/**
 * 获取所有的transportId和channelId信息，组装成array('transportId'=>array(channelId1,channelId2)))的格式返回
 * @return array
 * @author zqt
 */
function returnTCArrAll() {
    $returnArr = array();
    $transportIdList = M('InterfaceTran')->key('id')->getCarrierList(2);
    $channelIdList = M('InterfaceTran')->key('id')->getChannelList();//获取所有的channel列表
    foreach($channelIdList as $channelId=>$channelArr){
        if(intval($channelId) > 0){
            $returnArr[$channelIdList[$channelId]['carrierId']][] = $channelArr;
        }
    }
	return $returnArr;
}

/**
 * 根据运输方式Id获取渠道列表信息
 * @author yxd
 * @return array
 */
function getChinnelbyCarrierId($carrierId){
	$chinnels     = M('InterfaceTran')->getChannelList();
	$reData       = array();
	foreach($chinnels as $value){
		$reData[$value['carrierId']][]    = array("id"=>$value['id'],"channelName"=>$value['channelName']);
	}
	return $reData[$carrierId];
}

/**
 * 将二维关系数组转换成字符串返回
 * @param array $Tarr
 * @param symbol1 $symbol 第一,二维度之间的字符串，默认为':'
 * @param symbol2 $symbol 第二维度之间的字符串，默认为','
 * @param symbol2 $symbol 第一维度之间的字符串，默认为'|'
 * @return str
 * @author zqt
 */
function Tarr2Str($Tarr, $symbol1=':', $symbol2=',', $symbol3='|') {
    $tmpArr1 = array();
    foreach($Tarr as $key=>$valueArr){
        $tmpArr2 = array();
        foreach($valueArr as $value){
            $tmpArr2[] = $value;
        }
        $tmpArr1[] = implode($symbol1, array($key, implode($symbol2, $tmpArr2)));
    }
	return implode($symbol3, $tmpArr1);
}

/**
 * 格式化数据库查询数组中的查询条件
 * @param array $values array('1', 'eq')
 * @return boolean|string
 * @author jbf
 */
function buildEquation($values) {
	if (is_array($values) && isset($values[0]) && $values[0] !== null && $values[0] !== '' && isset($values[1])) {
		$equation = '';
		$operator = strtoupper($values[1]);
		switch ($operator) {
			case 'EQ'	: $equation = " = '".$values[0]."'"; break;
			case 'NEQ'	: $equation = " != '".$values[0]."'"; break;
			case 'GT'	: $equation = " > '".$values[0]."'"; break;
			case 'EGT'	: $equation = " >= '".$values[0]."'"; break;
			case 'LT'	: $equation = " < '".$values[0]."'"; break;
			case 'ELT'	: $equation = " <= '".$values[0]."'"; break;
			case 'LIKE'	: $equation = " LIKE '".$values[0]."'"; break;
			case 'EXP'	: $equation = $values[0]; break;
			default : $equation = false;
		}
			
		unset($operator);
		return $equation;
			
	} else return false;
}
/*
 * 获取查询条件中某个字段的值
 */
function getWhereStrOneFieldValue($whereData){
    $whereArr   =   explode(" ", $whereData);
    foreach($whereArr as $k=>$v){
        if(strpos('_'.$v, $field)===false){
            continue;
        }else if (strpos('_'.$v, '`'.$field.'`')!==false) {
            $field  =   '`'.$field.'`';
            if(trim($v,"=")==trim($field)){
                if($whereArr[($k+1)]=='='){
                    $ret    =   trim($whereArr[($k+2)]);
                }else{
                    $ret    =   trim($whereArr[($k+1)],"=");
                }
                $ret    =   trim($ret,"'");
                $ret    =   trim($ret,'"');
                return $ret;
            }else{
                $ret    =   explode("=",$v);
                $ret    =   trim($ret[1]);
                $ret    =   trim($ret,"'");
                $ret    =   trim($ret,'"');
                return $ret;
            }
        }else{
            if(trim($v,"=")==trim($field)){
                if($whereArr[($k+1)]=='='){
                    $ret    =   trim($whereArr[($k+2)]);
                }else{
                    $ret    =   trim($whereArr[($k+1)],"=");
                }
                $ret    =   trim($ret,"'");
                $ret    =   trim($ret,'"');
                return $ret;
            }else{
                $ret    =   explode("=",$v);
                $ret    =   trim($ret[1]);
                $ret    =   trim($ret,"'");
                $ret    =   trim($ret,'"');
                return $ret;
            }
        }
    }
}

// 对特殊字符串进行处理 add by xiaojinhua
function getStr($str) {
    $tmpstr = trim($str);
    $tmpstr = strip_tags($tmpstr);
    $tmpstr = htmlspecialchars($tmpstr);
    $tmpstr = addslashes($tmpstr);
    return $tmpstr;
}

function checkData($data){
    if(is_array($data)){
        foreach($data as $key => $v){
            $data[$key] = checkData($v);
        }
    }else{
        $data = getStr($data);
    }
    return $data;
}
/**
 * 自动创建目录（非递归）
 * @param unknown $path
 * by wcx
 */
function mkdirs2($path) {
	$arrdir	=	explode("/",$path);
	$arrdir2=	array();
	foreach($arrdir as $v){
		$tmp	=	explode("\\",$v);
		if(count($tmp)>1){
			foreach($tmp as $vv){
				$arrdir2[]	=	$vv;
			}
		}else{
			$arrdir2[]	=	$v;
		}
	}
	$arrdir	=	$arrdir2;
	unset($arrdir2);
	$length = 	count($arrdir);
	$mulu	=	array();
	for($i=0;	$i<$length;	$i++){
		$tmp	=	'';
		
		for($j=0;	$j<$i;	$j++){
			$tmp	.=	$arrdir[$j]."/";
		}
		if(empty($tmp)) continue;
		if(!is_dir($tmp)){
			mkdir($tmp);
		}
	}
}
/**
 * 生成xls
 * @param unknown $dir
 * @param unknown $name
 * @param unknown $data
 * by wcx
 */
function write_a_xls($data,$name,$type="file",$dir=""){
	$excel = new ExportDataExcel($type);
	if(!empty($dir)){
		mkdirs2($dir);
	}else{
		if($type=='file'){
			$dir	=	WEB_PATH."html/files/".date("Y_m_d",time())."/";
			mkdirs2($dir);
		}
	}
	$excel->filename =	$dir.$name;
	$excel->initialize();
	$data	=	array_values($data);
	foreach($data as $v) {
		$excel->addRow($v);
	}
	$excel->finalize();
}
/**
 * 数组合并(数字索引，如果重复则覆盖)
 * @param unknown $arr1
 * @param unknown $arr2
 * @return unknown
 * by wcx
 */
function array_merge2($arr1,$arr2){
	foreach($arr2 as $k=>$v){
		$arr1[$k]	=	$v;
	}
	return $arr1;
}

function filterHTMLFlag($string) {
    return strip_tags($string);
}

/**
 * 将数组转换成json数据并解决中文乱码问题
 * @param array $arr
 * by wcx
 */
function jsonCN_encode($arr){
    $na = array();
    foreach ( $arr as $key => $value ) {
        $na[$key] = urlencode ( $value );
    } 
    return urldecode ( json_encode ( $na ) );
}

function getExec($paramStr=''){
	if(PHP_OS=="WINNT"){
		exec("start /b php.exe ".$paramStr);
		return true;
	}else{
		exec("/usr/local/bin/php ".$paramStr." &> /dev/null &",$out,$status);
		if($status==0) return true;
		return false;
	}
}

function onceByOnce($file=__FILE__){
	exec("ps aux | grep $file",$a);
	$c=count($a);
	var_dump($c);
	if($c>3) {
		return false;
	}else{
		return true;
	}
}

/**
 * URL组装
 * @param string $url URL表达式，格式：'[模块/控制器]?参数1=值1&参数2=值2..'
 * @return string
 */
function route($url='') {

	$info   =  parse_url($url);
	$path   =  @$info['path'];
	$query  =  @$info['query'];
	$depr   =   C('URL_PATHINFO_DEPR');
	parse_str($query,$params);
	$path   =  explode($depr,$path);
	$mod    =  '';
	$act    =  '';
	$key    =  '';
	$value  =  '';
	foreach($path as $k=>$v){
		if(empty($v)||$v=='index.php'){
			unset($path[$k]);
			continue;
		}else{
			break;
		}
	}
	foreach($path as $k=>$v){
		if(empty($mod)){
			$mod = $v;
			continue;
		}
		if(empty($act)){
			$act = $v;
			continue;
		}
		if(empty($key)){
			$key = $v;
			continue;
		}
		if(!isset($params[$key])){

			$params[$key]=$v;
		}
		$key = '';
	}
	$url = '';
	if(!empty($mod)&&!empty($act)){
		$url = $mod.'/'.$act;
	}else{
		return '';
	}
	foreach($params as $k=>$v){
		$url .= '/'.$k.'/'.$v;
	}
	return $url.C('URL_HTML_SUFFIX');
}

function trimExt($filename){
	$fileArr = explode('.',$filename);
	array_pop($fileArr);
	return implode('.', $fileArr);
}
function ia2xml($array) { 
	$xml	=	""; 
    foreach($array as $key => $value) { 
    	$key_strings	=	explode(" ", $key);
    	$keyEnd	=	$key;
    	if(sizeof($key_strings) > 1){
    		$keyEnd	=	$key_strings[0];
    	}
		if(is_array($value)) {
			$array_keys	=	array_keys($value);
			$enterFlag	=	1;
			foreach($array_keys as $key_val){
				if(!preg_match("/^[0-9]+$/", $key_val)){
					$enterFlag	=	0;
					break;
				}
			}
			if($enterFlag){
				//多节点
				foreach($value as $val){
					if(is_array($val)){
						$xml .= "<$key>".ia2xml($val)."</$keyEnd>";
					} else {
						$xml .= "<$key>$val</$keyEnd>"; 
					}
				}
			}else{
				$xml .= "<$key>".ia2xml($value)."</$keyEnd>"; 
			}
		} else { 
			$xml .= "<$key>".$value."</$keyEnd>"; 
		} 
	} 
	return $xml; 
} 