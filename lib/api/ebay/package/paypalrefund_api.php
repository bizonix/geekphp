<?php
/**
 * ebay paypal退款
 * add by yxd 2014/08/18
 */
include_once WEB_PATH."lib/api/ebay/eBaySession.php";
include_once WEB_PATH."lib/curl.class.php";
class PaypalRefund extends eBaySession{
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * curl发送退款信息
	 * @param array
	 * @return array
	 * @author yxd
	 */
	public function curlRefund($dataArr){
		$paypal_account     = trim($dataArr['paypalAccount']);//'digitalzone889_api1.gmail.com';//'keyhere_api1.gmail.com';//
		$paypal_passwd      = trim($dataArr['pass']);//'BCCTAJERQ6FV558B';//'Z2MM4MR7JYNXJJTU';//
		$signature          = trim($dataArr['signature']);//'AxiNQr8NQtq3PDrf23ikvkotFKRsAIlurEMxKh0sATv828NvMQJWW9sr';//'A.KeI6NrmIaMvyjNhuLwy2pLV0zPAQ20fUMuWkw30BkqbQWQM8mSQ2MX';//
		$account			= trim($dataArr['totalSum']);//2.13;//
		$transactionID		= urlencode(trim($dataArr['PayPalPaymentId']));
		//$transactionID	= urlencode("3RS284873E199552A"); //for test.........
		$refundType			= urlencode(trim($dataArr['refundType']));
		//$refundType			= urlencode('Full');
	    $currencyID			= urlencode($dataArr['currency']);
		//$currencyID			= urlencode('GBP');
		$amount				= trim($dataArr['refundSum']);//2.13;//
		$memo               = $dataArr['note'];//"缺货";//
		//$nvpStr             = "&TRANSACTIONID=$transactionID&REFUNDTYPE=$refundType&CURRENCYCODE=$currencyID&NOTE=$memo";
		$nvpStr             = "&TRANSACTIONID=$transactionID&REFUNDTYPE=$refundType&CURRENCYCODE=$currencyID&NOTE=$memo";
		if($refundType == 'Partial'){
			$nvpStr=$nvpStr."&AMT=$amount";
		}
		//var_dump($paypal_account,$paypal_passwd,$signature,'RefundTransaction', $nvpStr , $account);exit;
		$ppRtnInfo = $this->CUR_POST($paypal_account,$paypal_passwd,$signature,'RefundTransaction', $nvpStr , $account);
		//$ppRtnInfo = $this->PPHttpPost($paypal_account,$paypal_passwd,$signature,'RefundTransaction', $nvpStr , $account);
		//var_dump($ppRtnInfo);exit();
		return $ppRtnInfo;
	}

	/**
	 * 跳转到国外服务器间接退款
	 */
	public function CUR_POST($paypal_account,$paypal_passwd,$signature,$methodName_, $nvpStr ,$account){
		$curl = new CURL();
		$us_url = "http://us.oversea.valsun.cn/paypal_refund_api.php";
		$post_data = array(
				"paypal_account" => $paypal_account,
				"paypal_passwd" => $paypal_passwd,
				"signature" => $signature,
				"refundType" => "RefundTransaction",
				"nvpStr" => $nvpStr,
				"account" => $account
		);
		//var_dump($post_data);exit;
		$nowTime	 = time();
		$logdir    = WEB_PATH."log/".date("Y-m",$nowTime)."/".date('d',$nowTime)."/";
		if (!is_dir($logdir)){
			mkdir($logdir,0777,true);
		}
		$operator    = get_username();
		$date        = date("Y-m-d H:i:s",$nowTime);
		$logfile     = $logdir."PaypalRefund.txt";
		error_log("\n\n{$operator}--{$date}\n".josnCN_encode($post_data)."\n\n",3,$logfile);
		$httpParsedResponseJson = $curl->post($us_url,$post_data,0);
		$httpParsedResponseAr = json_decode($httpParsedResponseJson,true);
		foreach($httpParsedResponseAr as $key=>$value){
			$httpParsedResponseAr[$key]    = urldecode($value);
		}
		error_log("\n处理结果：\n".json_encode($httpParsedResponseAr)."\n\n",3,$logfile);
		return $httpParsedResponseAr;
	}
	
	/**
	 * 直接操作ebay API退款
	 * @param string $paypal_account
	 * @param string $paypal_passwd
	 * @param string $signature
	 * @param string $methodName_
	 * @param string $nvpStr_
	 * @param string $account
	 * @return array
	 */
public function PPHttpPost($paypal_account,$paypal_passwd,$signature,$methodName_, $nvpStr_ ,$account) {

	//global $environment,$dbcon;

	$API_UserName	= $paypal_account;
	$API_Password	= $paypal_passwd;
	$API_Signature	= $signature;



	// Set up your API credentials, PayPal end point, and API version.
	$API_UserName = urlencode($API_UserName);
	$API_Password = urlencode($API_Password);
	$API_Signature = urlencode($API_Signature);
	$API_Endpoint = "https://api-3t.paypal.com/nvp";
	//$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp"; //for test

	//define('API_ENDPOINT', 'https://api-3t.sandbox.paypal.com/nvp');

	//$version = urlencode('51.0');
	$version = urlencode('65.1');
	// Set the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);

	// Turn off the server and peer verification (TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_POST, 1);

	// Set the API operation, version, and API signature in the request.
	$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";

	// Set the request as a POST FIELD for curl.
	curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

	// Get response from the server.
	$httpResponse = curl_exec($ch);

	if(!$httpResponse) {
		exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
	}

	// Extract the response details.
	$httpResponseAr = explode("&", $httpResponse);

	$httpParsedResponseAr = array();
	foreach ($httpResponseAr as $i => $value) {
		$tmpAr = explode("=", $value);
		if(sizeof($tmpAr) > 1) {
			$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
		}
	}

	if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
		exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
	}

	return $httpParsedResponseAr;
}
}
?>
