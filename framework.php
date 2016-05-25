<?php
class Core {
    private static $_instance = array();
    private static $classFile;

	private function __construct(){
		//-----------需要页面显示调试信息,	注释掉下面两行即可---
		
		//-------------------------------------------------------
// 		set_error_handler(array("Core",'appError'));	
// 		set_exception_handler(array("Core",'appException'));
		
        date_default_timezone_set("Asia/Shanghai");
		if(version_compare(PHP_VERSION,'5.4.0','<') ) {
 			@set_magic_quotes_runtime (0);
			define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
		}
        if(!defined('WEB_PATH')){
			define("WEB_PATH",str_replace(DIRECTORY_SEPARATOR, '/',dirname(__FILE__)).DIRECTORY_SEPARATOR);	
		}
		include	WEB_PATH."lib/common.php";
		//加载全局配置信息
		C(include WEB_PATH.'conf/common.php');
		C(include WEB_PATH.'conf/error_code.php'); //定义系统的错误码
		C(include WEB_PATH.'conf/url_conf.php'); //预定义回掉url //add wcx 2015/1/13
        C(include WEB_PATH.'conf/route.php'); //预定义回掉url //add wcx 2015/5/27
		//C(include WEB_PATH.'conf/default_var.php');//订单状态码列表定义
		include	WEB_PATH."lib/auth.php";	//鉴权
		//include	WEB_PATH."lib/authuser.class.php";	//新鉴权
		include	WEB_PATH."lib/php-export-data.class.php";	//excel
		//Auth::setAccess(include WEB_PATH.'conf/access.php');
		include	WEB_PATH."lib/log.php";
        include WEB_PATH."conf/constant_order.php";

		//加载数据接口层及所需支撑
		include	WEB_PATH."lib/service/http.php";	//网络接口
		include	WEB_PATH."lib/functions.php";
		include	WEB_PATH."lib/page.php";
		include	WEB_PATH."lib/template.php";		//PHPLIB 的模板类
		include	WEB_PATH."lib/cache/cache.php";		//memcache
		include WEB_PATH."lib/productstatus.class.php";

		//加载语言包
		$lang	=	WEB_PATH."lang/".C("LANG").".php";		//memcache
		
		if(file_exists($lang)){
			//echo $lang;
			//C(include $lang);
		}
		
		if(C("DATAGATE") == "db"){
			$db	=	C("DB_TYPE");
			include	WEB_PATH."lib/db/".$db.".php";	//db直连
			if($db	==	"mysql"){ 
				global	$dbConn;
				$db_config	=	C("DB_CONFIG");
				$dbConn	=	new mysql();
				$dbConn->connect($db_config["master1"][0],$db_config["master1"][1],$db_config["master1"][2]);
				$dbConn->select_db($db_config["master1"][4]);
			}
			if($db	==	"mongodb"){
				//.......
			}
		}
		//初始化memcache类
		global $memc_obj;
		$memc_obj 	= new Cache(C('CACHEGROUP'));
		//自动加载类
		 spl_autoload_register(array('Core', 'autoload'));
		

	}
	
	//自动加载实现
	public function autoload($class){
		//加载act
		if(strpos($class,"Act")){
			$name	=	preg_replace("/Act/","",$class);
			$fileName	=	lcfirst($name).".action.php";
			Core::getFile($fileName,WEB_PATH."action/");
			if(empty(Core::$classFile)){
				throw new Exception("action no exits");
			}
			include_once Core::$classFile;
		}
		if(strpos($class,"Model")){
			$name	=	preg_replace("/Model/","",$class);
			$fileName	=	lcfirst($name).".model.php";
			Core::getFile($fileName,WEB_PATH."model/");
			if(empty(Core::$classFile)){
				throw new Exception("action no exits");
			}
			include_once Core::$classFile;
		}
		
		if(strpos($class,"View")){
			$name	=	preg_replace("/View/","",$class);
			$fileName	=	lcfirst($name).".view.php";
			Core::getFile($fileName,WEB_PATH."view/");
			if(empty(Core::$classFile)){
				throw new Exception("action no exits");
			}
			include_once Core::$classFile;
		}
		
	}
	
	
	public static function getFile($fileName,$path){ 
		if ($handle = @opendir($path)) { 
		    while(false !== ($file = @readdir($handle))) { 	    	
		        if(is_dir($path.$file) && ($file != "." && $file != "..")){ 
		        	Core::getFile($fileName,$path.$file."/");
		        }else{
		       	 	if($file==$fileName){
		        		Core::$classFile	=	$path.$file;
		        	}
		        }
		    }
		} 
		@closedir($handle);
	}
	
	
	private function __clone() {}
	
	//单实例
    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
             self::$_instance = new Core();
        }
        return self::$_instance;
    }


    /**
     +----------------------------------------------------------
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
		//echo $e;
        //halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
		//echo $errstr;
		//exit;
    	switch ($errno) {
			case E_WARNING:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
// 				if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
				//echo ($errorStr)."<br>"."<br>";
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
				if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
				//echo($errorStr)."<br>"."<br>";
				break;
			case E_STRICT:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			default:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
				Log::record($errorStr,Log::NOTICE);
				break;
		}
    }
}
