<?php
/*
* taobao平台对接接口
* add by: linzhengxiang @date 20140618
*/
class TaobaoButtAct extends CheckAct
{

    private $session; //token session
    private $account;
    private $appKey; //填写自己申请的AppKey
    private $appSecret; //填写自己申请的$appSecret
    private $defalut_carrier;

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($account)
    {
    	$keyname    = WEB_PATH.'/conf/scripts/keys/taobao/config_'.$account.'.php';
    	if (file_exists($keyname)) {
    		include_once $keyname;
    	} else {
    		exit ("未找对应的key文件!");
    	}
    	$this->session            = $session;
    	$this->account            = $account;
    	$this->appKey             = $appKey;
    	$this->appSecret          = $appSecret;
    	$this->defalut_carrier    = $defalut_carrier;
        ######################以后扩展到接口获取 start ######################
        $this->session = "620151733660340af2640f105ZZ8be4e09d553fde22b5d2855055625"; //token session
        $this->account = "finejoly";
        $this->appKey = '21460636'; //填写自己申请的AppKey
        $this->appSecret = 'df0cb97ac64f603c799082dde8966c6b'; //填写自己申请的$appSecret
        $this->defalut_carrier = '申通快递';
        ######################以后扩展到接口获取  end  ######################
    }

    /**
     * 根据条件抓取订单号信息
     */
    public function taobaoTradesSoldGet()
    {
        $OrderIdsObject = F('taobao.package.TaobaoOrderGet');
        $OrderIdsObject->setConfig($this->account, $this->session, $this->appSecret, $this->appKey, $this->defalut_carrier);
        return $OrderIdsObject->taobaoTradesSoldGet();
    }

    /**
     *  根据淘宝订单号获取该定的详情
     *	@param $tid	淘宝订单号
     */
    public function taobaoTradeGet($tid)
    {
        $OrderIdsObject = F('taobao.package.TaobaoOrderGet');
        $OrderIdsObject->setConfig($this->account, $this->session, $this->appSecret, $this->appKey, $this->defalut_carrier);
        return $OrderIdsObject->taobaoTradeGet($tid);
    }

    /**
     * 标记发货
     * @param $recordnumber 淘宝订单号
     * @param $company_code 国家码
     * @param $tracknumber  跟踪号
     */
    public function taobaoLogisticsOfflineSend($recordnumber, $company_code, $tracknumber)
    {
        $OrderIdsObject = F('taobao.package.TaobaoLogisticsOfflineSend');
        $OrderIdsObject->setConfig($this->account, $this->session, $this->appSecret, $this->appKey, $this->defalut_carrier);
        return $OrderIdsObject->taobaoLogisticsOfflineSend($recordnumber, $company_code, $tracknumber);
    }
    
    /********************************************
	 *	 获取快递code信息
	 *	 @param  $Logistic string  物流公司名(erp系统里的)
	 */
    function getLogisticCode($Logistic){
		$code	=	"";
		switch($Logistic){
			case "EMS":
					$code	=	"EMS";
					break;
			case "中国邮政平邮":
					$code	=	"POST";
					break;
			case "FedEx":
					$code	=	"FEDEX";
					break;
			case "顺丰快递":
					$code	=	"SF";
					break;
			case "韵达快递":
					$code	=	"YUNDA";
					break;
			case "申通快递":
					$code	=	"STO";
					break;
			case "中通快递":
					$code	=	"ZTO";
					break;
			case "圆通快递":
					$code	=	"YTO";
					break;
			case "天天快递":
					$code	=	"TTKDEX";
					break;
			case "中国邮政挂号":
					$code	=	"POSTB";
					break;
			default:
					$code	=	"";
					break;
		}
		return $code;
	}
}
?>