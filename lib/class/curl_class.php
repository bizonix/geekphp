<?php
/*
Sean Huber CURL library
This library is a basic implementation of CURL capabilities.
It works in most modern versions of IE and FF.
==================================== USAGE ====================================
It exports the CURL object globally, so set a callback with setCallback($func).
(Use setCallback(array('class_name', 'func_name')) to set a callback as a func
that lies within a different class)
Then use one of the CURL request methods:
get($url);
post($url, $vars); vars is a urlencoded string in query string format.
Your callback function will then be called with 1 argument, the response text.
If a callback is not defined, your request will return the response text.
*/
class CURL {
	var $callback = false;
	function setCallback($func_name) {
		$this->callback = $func_name;
	} 
	function doRequest($method, $url, $vars, $header_info = 1) {
		$header_info = !$header_info ? 0 : 1;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, $header_info);
		curl_setopt($ch, CURLOPT_USERAGENT, @$_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
		}
		if (stripos('https', '__'.$url)) {	//兼容https协议
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		$data = curl_exec($ch);

		if ($data) {
			curl_close($ch);
			if ($this->callback) {
				$callback = $this->callback;
				$this->callback = false;
				return call_user_func($callback, $data);
			} else {
				return $data;
			} 
		} else {
			$err = curl_error($ch);
			curl_close($ch);
			return $err;
		}
	} 
	function get($url, $header_info = 1) {
		return $this->doRequest('GET', $url, 'NULL', $header_info);
	} 
	function post($url, $vars, $header_info = 1) {
		return $this->doRequest('POST', $url, $vars, $header_info);
	} 
}

class CURL_Multi{
    //要并行抓取的url 列表
    private $urls = array();
 
    //curl 的选项
    private $options;
     
    //构造函数
    function __construct($options = array())
    {
        $this->setOptions($options);
    }
 
    //设置url 列表
    function setUrls($urls)
    {
        $this->urls = $urls;
        return $this;
    }
 
 
    //设置选项
    function setOptions($options)
    {
        $options[CURLOPT_RETURNTRANSFER] = 1;
        if (isset($options['HTTP_POST'])) 
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['HTTP_POST']);
            unset($options['HTTP_POST']);
        }
 
        if (!isset($options[CURLOPT_USERAGENT])) 
        {
            $options[CURLOPT_USERAGENT] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1;)';
        }
 
        if (!isset($options[CURLOPT_FOLLOWLOCATION])) 
        {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
        }
 
        if (!isset($options[CURLOPT_HEADER]))
        {
            $options[CURLOPT_HEADER] = 0;
        }
        $this->options = $options;
    }
 
    //并行抓取所有的内容
    function exec()
    {
        if(empty($this->urls) || !is_array($this->urls))
        {
            return false;
        }
        $curl = $data = array();
        $mh = curl_multi_init();
        foreach($this->urls as $k => $v)
        {
            $curl[$k] = $this->addHandle($mh, $v);
        }
        $this->execMulitHandle($mh);
        foreach($this->urls as $k => $v)
        {
            $data[$k] = curl_multi_getcontent($curl[$k]);
            curl_multi_remove_handle($mh, $curl[$k]);
        }
        curl_multi_close($mh);
        return $data;
    }
     
    //只抓取一个网页的内容。
    function execOne($url)
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init($url);
        $this->setOneOption($ch);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
     
    //内部函数，设置某个handle 的选项
    private function setOneOption($ch)
    {
        curl_setopt_array($ch, $this->options);
    }
 
    //添加一个新的并行抓取 handle
    private function addHandle($mh, $url)
    {
        $ch = curl_init($url);
        $this->setOneOption($ch);
        curl_multi_add_handle($mh, $ch);
        return $ch;
    }
 
    //并行执行(这样的写法是一个常见的错误，我这里还是采用这样的写法，这个写法
    //下载一个小文件都可能导致cup占用100%, 并且，这个循环会运行10万次以上
    //这是一个典型的不懂原理产生的错误。这个错误在PHP官方的文档上都相当的常见。）
    private function execMulitHandle($mh)
    {
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
    }
}
?>
