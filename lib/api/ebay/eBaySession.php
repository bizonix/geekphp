<?php
	/********************************************************************************
	  * AUTHOR: Michael Hawthornthwaite - Acid Computer Services (www.acidcs.co.uk) *
	  *******************************************************************************/
class eBaySession
{
	protected $requestToken;
	protected $devID;
	protected $appID;
	protected $certID;
	protected $serverUrl;
	protected $compatLevel;
	protected $siteID;
	protected $account;
	protected $logname;
	protected $xmlHeader;
	protected $xmlBody;
	protected $xmlFooter;
	protected $verb;
	protected $bodyData;
	/**	__construct
		Constructor to make a new instance of eBaySession with the details needed to make a call
		Input:	$userRequestToken - the authentication token fir the user making the call
				$developerID - Developer key obtained when registered at http://developer.ebay.com
				$applicationID - Application key obtained when registered at http://developer.ebay.com
				$certificateID - Certificate key obtained when registered at http://developer.ebay.com
				$useTestServer - Boolean, if true then Sandbox server is used, otherwise production server is used
				$compatabilityLevel - API version this is compatable with
				$siteToUseID - the Id of the eBay site to associate the call iwht (0 = US, 2 = Canada, 3 = UK, ...)
				$callName  - The name of the call being made (e.g. 'GeteBayOfficialTime')
		Output:	Response string returned by the server
	*/
	public function __construct($account = ''){
		$this->logname = date("Y-m-d_H-i-s").rand(1, 9).'.log';
		//$this->setTokenInfo($account);
		
		
	}
	
	//$userRequestToken, $developerID, $applicationID, $certificateID, $serverUrl, $compatabilityLevel, $siteToUseID
	public function setRequestConfig($authorize){
		if (empty($authorize)||!is_array($authorize)){
			return false;
		}
		list($this->requestToken, $this->devID, $this->appID, $this->certID, $this->serverUrl, $this->compatLevel, $this->siteID, $this->account) = array_values($authorize);
		return true;
	}
	
	
	/**	sendHttpRequest
		Sends a HTTP request to the server for this session
		Input:	$requestBody
		Output:	The HTTP Response as a String
	*/
	public function sendHttpRequest($requestBody=''){

		//build eBay headers using variables passed via constructor
		if(empty($requestBody)){
			$requestBody = $this->getXml();
		}
		$headers = $this->buildEbayHeaders();
		//echo $requestBody;exit;
		//initialise a CURL session
		$connection = curl_init();
		//set the server we are using (could be Sandbox or Production server)
		curl_setopt($connection, CURLOPT_URL, $this->serverUrl);
		
		//stop CURL from verifying the peer's certificate
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		
		//set the headers using the array of headers
		curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
		
		//set method as POST
		curl_setopt($connection, CURLOPT_POST, 1);
		
		//set the XML body of the request
		curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
		
		//set it to return the transfer as a string from curl_exec
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		
		//Send the Request
		$response = curl_exec($connection);
		
		//close the connection
		curl_close($connection);
		
		$this->backupRequestAndResponseXml($requestBody, $response);
		//return the response
		return $response;
	}
	
	
	
	/**	buildEbayHeaders
		Generates an array of string to be used as the headers for the HTTP request to eBay
		Output:	String Array of Headers applicable for this call
	*/
	private function buildEbayHeaders(){
		$headers = array (
			//Regulates versioning of the XML interface for the API
			'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,
			
			//set the keys
			'X-EBAY-API-DEV-NAME: ' . $this->devID,
			'X-EBAY-API-APP-NAME: ' . $this->appID,
			'X-EBAY-API-CERT-NAME: ' . $this->certID,
			
			//the name of the call we are requesting
			'X-EBAY-API-CALL-NAME: ' . $this->verb,			
			
			//SiteID must also be set in the Request's XML
			//SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
			//SiteID Indicates the eBay site to associate the call with
			'X-EBAY-API-SITEID: ' . $this->siteID,
		);
		
		return $headers;
	}
	
	private function backupRequestAndResponseXml($requestBody, $responseBody){
		$tracelists = debug_backtrace();
		$savelist = array('class'=>'errorclass', 'function'=>'errorfunction');
		foreach ($tracelists AS $tracelist){
			// /data/web/re.order.valsun.cn/action/buttplatform/ebayButt.action.php  调用demo
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
		$savepath	= EBAY_RAW_DATA_PATH.'ebay/'.$savelist['class'].'/'.$savelist['function'].'/'.$this->account.'/'.date('Y-m').'/'.date('d').'/'.$this->logname;
		//echo 'savepath:'.$savepath;exit;
		write_log($savepath, $savecontent);
	}
	/**
	 * 设置xml请求头和尾
	 * @return [type] [description]
	 */
	public function init(){
		$this->xmlHeader	=	'<?xml version="1.0" encoding="utf-8"?>
								<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
								<ErrorLanguage>en_US</ErrorLanguage>
								<WarningLevel>High</WarningLevel>
								<RequesterCredentials>
									<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
								</RequesterCredentials>';
		$this->xmlFooter	=	'</'.$this->verb.'Request>';
		return $this;
	}


	public function setVerb($verb){
		$this->verb = $verb;
	}

	/****************************************
	 * 获取待传递的xml信息
	 */
	public function getXml(){
		$this->xmlBody	=	ia2xml($this->bodyData);
		return $this->xmlHeader.$this->xmlBody.$this->xmlFooter;
	}
	public function setItemID($ItemID){
		$this->bodyData['ItemID']		=	$ItemID;
	}
	
	public function setIncludeItemSpecifics($IncludeItemSpecifics){
		$this->bodyData['IncludeItemSpecifics']		=	$IncludeItemSpecifics;
	}
	
	public function setDetailLevel($DetailLevel){
		$this->bodyData['DetailLevel']		=	$DetailLevel;
	}
	
	/*
	 * 多个filter逗号连接
	 */
	public function setReturnFilter($filter){
		$this->bodyData['OutputSelector']		=	$filter;
	}

	/**
	 * 设置PostalCode
	 */
	public function setPostalCode($postalCode) {
		$this->bodyData['PostalCode'] = $postalCode;
	}

	/**
	 * 设置OutputSelectorW
	 */
	public function setOutputSelector($outputSelector) {
		$this->bodyData['OutputSelector'] = $outputSelector;
	}
	public function setSiteId($siteId) {
		$this->bodyData['CategorySiteID']	=	$siteId;
	}

	public function setCategoryParent($parentId) {
		$this->bodyData['CategoryParent']	=	$parentId;
	}
	public function setLevelLimit($levelLimit) {
		$this->bodyData['LevelLimit'] = $levelLimit;
	}

	public function setTokenInfo($account = ''){
		if(!empty($account)){
			$this->account = $account;
		}else{
			return false;
		}
		$path = WEB_PATH."conf/scripts/keys/ebay/keys_".$account.".php";
		include_once($path);
		$this->devID = $devID;
	 	$this->appID = $appID;
	 	$this->certID= $certID;
	 	$this->serverUrl = $serverUrl;
	 	$this->compatLevel = $compatabilityLevel;
	 	$this->siteID = $siteID;
	 	$this->requestToken = $userToken;
		$siteID =0;
		$this->init();
	}

}
?>