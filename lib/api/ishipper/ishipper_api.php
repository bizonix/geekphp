<?php
	/********************************************************************************
	  * AUTHOR: junny zou *
	  *******************************************************************************/
class iShipper
{
	protected $baseUrl 		= "http://ishipper.harpost.com/webservices/";
	protected $serverUrl;

	public function __construct(){
	}
	
	//$userRequestToken, $developerID, $applicationID, $certificateID, $serverUrl, $compatabilityLevel, $siteToUseID
	public function setConfig($userApi="12ef1361549b0b959658fd4b63f8bf2a"){
		F("xmlhandle");
		$this->baseUrl = $this->baseUrl.$userApi."/";
	}
	
	
	/**	sendHttpRequest
		Sends a HTTP request to the server for this session
		Input:	$requestBody
		Output:	The HTTP Response as a String
	*/
	public function sendHttpRequest($requestBody){
		
		//initialise a CURL session
		$connection = curl_init();
		//set the server we are using (could be Sandbox or Production server)
		curl_setopt($connection, CURLOPT_URL, $this->serverUrl);
		
		//stop CURL from verifying the peer's certificate
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
				
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
		var_dump($response);exit;
		//return the response
		return $response;
	}
	
	
	
	/**	buildEbayHeaders
		Generates an array of string to be used as the headers for the HTTP request to eBay
		Output:	String Array of Headers applicable for this call
	*/
	private function buildEbayHeaders(){
		$headers = array (
			
		);
		
		return $headers;
	}
	
	/*
	 * 推送订单
	 * zjr
	 */
	public function postOrder($orders){
		$this->serverUrl = $this->baseUrl."postorder";
		$xmlRequest = arrayToXml($orders,"Orders");
		return $this->sendHttpRequest($xmlRequest);
	}

	/**
	 * 申请跟踪号码
	 * 格式：$order = array(array("OrderId"=>"","EubPrintProductFormat"=>""))
	 */
	public function applyTracking($orders){
		$this->serverUrl = $this->baseUrl."applytracking";
		$xmlRequest = arrayToXml($orders,"Orders");
		return $this->sendHttpRequest($xmlRequest);
	}

	/**
	 * 订单打印
	 * 格式：$order = array("LableFormat"=>"")
	 */
	public function printOrder($request){
		$this->serverUrl = $this->baseUrl."printorder";
		$xmlRequest = arrayToXml($request,"Request");
		return $this->sendHttpRequest($xmlRequest);
	}

	/**
	 * 订单删除
	 * 格式：$order = array("LableFormat"=>"")
	 */
	public function deleteOrder($request){
		$this->serverUrl = $this->baseUrl."deleteorder";
		$xmlRequest = arrayToXml($request,"Orders");
		return $this->sendHttpRequest($xmlRequest);
	}

	/**
	 * 获取运输方式
	 */
	public function getallshipway(){
		$this->serverUrl = $this->baseUrl."getallshipway";
		$xmlRequest = '';
		return $this->sendHttpRequest($xmlRequest);
	}

}
?>