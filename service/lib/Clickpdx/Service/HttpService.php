<?php
namespace Clickpdx\Service;
use \Clickpdx\Http\HttpResponse;
use \Clickpdx\Http\HttpRequest;

abstract class HttpService
{
	const HTTP_GET_REQUEST = 'GET';
	
	const HTTP_POST_REQUEST = 'POST';
	
	const HTTP_PUT_REQUEST = 'PUT';
	
	const HTTP_PATH_REQUEST = 'PATCH';
	
	const HTTP_HEAD_REQUEST = 'HEAD';
	
	protected $appName;
	
	protected $requestType;
	
	protected $httpResponse;
	
	protected $httpRequest;
	
	protected $writeHandlers = array();

	public abstract function makeHttpRequest($type);
	
	public function registerWriteHandler($name,$func)
	{
		$this->writeHandlers[$name]=$func;
	}
	
	public function getWriteHandler($name)
	{
		return $this->writeHandlers[$name];
	}
	
	public function sendRequest(HttpRequest $req)
	{
		$resp = new HttpResponse();

		$resp->setBody($req->writeUsingDelegate($this->getWriteHandler('POST')));
		// setHeaders
		// setResponseCode
		// setResponseBody
		// setHandler
		return $resp;
	}
	
	protected function initServiceSession()
	{
		if (\session_status() == \PHP_SESSION_NONE)
		{
				session_start();
		}
	}
	
	protected function destroyServiceSessionData()
	{
		$this->initServiceSession();
		unset($_SESSION[$this->appName]);
	}
	
	public function setSessionData($name,$value)
	{
		$this->initServiceSession();
		if(!isset($_SESSION[$this->appName]))
		{
			$_SESSION[$this->appName]=array();
		}
		$_SESSION[$this->appName][$name]=$value;
	}
	
	public function sessionToString()
	{
		return implode('<br />',$_SESSION[$this->appName]);
	}
	
	public function getSessionData($name)
	{
		$this->initServiceSession();
		return $_SESSION[$this->appName][$name];
	}
	
	protected function clearSessionData($key)
	{
		$this->initServiceSession();
		if(!isset($_SESSION[$this->appName][$key]))
			throw new \Exception("$key is not a valid session key for this application.");
		else unset($_SESSION[$this->appName][$key]);
	}
	
	
	public function createHttpRequest($url,$type='GET')
	{
		switch($type)
		{
			case 'GET':
				return $this->createHttpGetRequest($url);
				break;
			case 'POST':
				return $this->createHttpPostRequest($url);
				break;
		}
	}

/*	
	public function sendRequest($req)
	{
		switch($req->requestType())
		{
			case 'POST':
				return $this->sendHttpPostRequest($req);
				break;
			case 'GET':
				return $this->sendHttpGetRequest($req);
				break;
		}
	}
*/
	
	protected function sendHttpRequest($req)
	{
		
		$json_response = curl_exec($curl);

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if($status != 200 )
		{
			die("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}

		curl_close($curl);
		$this->httpResponse = json_decode($json_response, true);
	}

	protected function createHttpGetRequest($url,$params)
	{
		$req=new \Clickpdx\HttpRequest($url);
		$req->setRequestBody($params);
		return $req;
	}
	
	protected function createHttpPostRequest($url,$params)
	{
		$curl = new \Clickpdx\HttpPostRequest($url);
		$curl->setRequestBody($params);
		return $curl;
	}
	
	public function getResponse()
	{
		return $this->httpResponse;
	}
	
	public function writeResponse()
	{
		$this->httpResponse->write();
	}
	
	public function makeHttpResponse()
	{
		$this->httpResponse = new \Clickpdx\Http\HttpRedirect($this->authUri);
		return $this;
	}

	protected function formatRequestParams($params)
	{
		$rp = '';
		foreach($params as $k=>$v)
		{
			$rp[]=($k . "=" . ($v[1]?urlencode($v[0]):$v[0]));
		}
		return implode('&',$rp);
	}
}