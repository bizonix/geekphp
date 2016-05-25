<?php
/********************************
 * 
 * zqt 2014/11/21
 */
class WishSession
{  
    protected $account;
    protected $key;
    protected $logname;
    
    function __construct() {
	   $this->logname = date("Y-m-d_H-i-s").rand(1, 9).'.log';
	}

	public function setConfig($account, $key){
	    $this->account = $account;
		$this->key	   = $key;
	}
    
    public function wish_file_get_contents($url, $type='GET'){
        if($type == 'GET'){
            $context = stream_context_create(array(
                'http' => array(
                    'method'        => 'GET',
                    'ignore_errors' => true,
                ),
            ));
        }else{
            $context = stream_context_create(array(
                'http' => array(
                    'method'        => 'POST',
                    'ignore_errors' => true,
                ),
            ));
        }        
		$response = file_get_contents($url, TRUE, $context);
        // $this->backupRequestAndResponseXml($url, $response);
		return $response;
	}
	
    //日志方法
	private function backupRequestAndResponseXml($requestBody, $responseBody){
		$tracelists = debug_backtrace();
		$savelist = array('class'=>'errorclass', 'function'=>'errorfunction');
		foreach ($tracelists AS $tracelist){
			if (preg_match("/action\/buttplatform\/[a-z]*\.action\.php$/i", $tracelist['file'])>0){
				$savelist = array('class'=>$tracelist['class'], 'function'=>$tracelist['function']);
				break;
			}
		}
		$savecontent = "##############################################  requestBody start ###################################################\n\n".
		$savecontent .= "{$requestBody}\n\n";
		$savecontent .= "##############################################  requestBody end   ###################################################\n\n\n\n";
		$savecontent .= "############################################## responseBody start ###################################################\n\n";
		$savecontent .= "{$responseBody}\n\n";
		$savecontent .= "##############################################  responseBody end  ###################################################";
		$savepath	= EBAY_RAW_DATA_PATH.'wish/'.$savelist['class'].'/'.$savelist['function'].'/'.$this->account.'/'.date('Y-m').'/'.date('d').'/'.$this->logname;
		write_log($savepath, $savecontent);
	}
	
	
	/**
	 * 根据运输方式获取物流服务简称
	 * @param string ebay_carrier
	 * @return string serivceName
	 */
    public function get_carrier_name($ebay_carrier){
    	switch (strtoupper($ebay_carrier)){
    		case "香港小包挂号":
    			$serviceName		= 'HKPAM';	//Hongkong Post Air Mail
    			break;
    		case "UPS":
    		case "UPS美国专线":
    			$serviceName		= 'UPS';
    			break;
    		case "DHL":
    			$serviceName		= 'DHL';
    			break;
    		case "FEDEX":
    			$serviceName		= 'FEDEX_IE';
    			break;
    		case "TNT":
    			$serviceName		= 'TNT';
    			break;
    		case "EMS":
    			$serviceName		= 'EMS';
    			break;
    		case "中国邮政挂号":
    			$serviceName		= 'CPAM';	//China Post Air Mail
    			break;
    		case "瑞士小包挂号":
    			$serviceName		= 'CHP';	//
    			break;
    		case "俄速通挂号":
    		case "俄速通大包":
    			$serviceName		= 'CPAM_HRB';	//俄速通专线
    			break;
    		case "EUB":
    			$serviceName		= 'EMS_ZX_ZX_US';	//EUB
    			break;
    		case "新加坡小包挂号":
    			$serviceName		= 'SGP';
    			break;
    		case "WEDO":
            case "中国邮政平邮":
    		case "俄速通平邮":
    		case "瑞士小包平邮":
    		case "新加坡DHL GM平邮":
    		case "香港小包平邮":
    			$serviceName		= 'Other';
    			break;
    		default:
    			$serviceName	   = false;
    			break;
    	}
    	return $serviceName;
    }


    public function time_shift($origin_num) { //转换成时间戳
    	$time_offset	=	0;
    	$i	=	0;
    	$i	=	strpos($origin_num,"-");
    	
    	if($i > 0){
    		$temp	=	explode("-", $origin_num);
    		$utc	=	intval(preg_replace("/0/","",$temp[1]));
    		$time_offset	=	time() - 3600*(8+ $utc);	
    	}
    	$i	=	0;
    	$i	=	strpos($origin_num,"+");
    	if($i > 0){
    		$temp	=	explode("+", $origin_num);
    		$utc	=	intval(preg_replace("/0/","",$temp[1]));
    		if($utc > 8){
    			$time_offset	=	time() + 3600*($utc - 8);	
    		}else{
    			$time_offset	=	time() - 3600*(8 - $utc);	
    		}
    	}
    	$time	=	strtotime(substr($origin_num,0,14));
    	return array($time, $time_offset);
    }
}
?>