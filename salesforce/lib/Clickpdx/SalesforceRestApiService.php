<?php
namespace Clickpdx;
use \Exception;
use Clickpdx\OAuth\OAuthGrantTypes;
use Clickpdx\SfRestApiRequestTypes;
use Clickpdx\Http\HttpRequest;
use Clickpdx\Salesforce\SfResult;
use Clickpdx\Salesforce\SObject;
use Clickpdx\Salesforce\RestApiAuthenticationException;
use Clickpdx\Salesforce\RestApiInvalidUrlException;

class SalesforceRestApiService extends Service\HttpService
{
	
	private $executed;
	
	private $instanceUrl;
	
	private $serviceEndpoint;
	
	private $accessToken;
	
	private $soqlQuery;
	
	private $endpoints;
	
	private $endpoint;
	
	private $debug;
	
	public function __construct(/*\OAuthParameterCollection*/$c=array())
	{
		if(!empty($c))
		{
			$this->setParams($c);
		}
	}
	
	public function setDebug($init=false)
	{
		$this->debug = $init;
	}
	
	
	// @TODO - These settings are redundant and should probably
	// $this->soqlEndpoint = $c['soqlEndpoint'];
	// $this->serviceEndpoint = $c['serviceEndpoint'];
	// $this->endpoint = $c['soqlEndpoint'];

	// @TODO - If the oauth data has already been requested and set then
	//   we shouldn't make the request again.
	// $this->accessToken = $this->getSessionData('accessToken');
	// $this->instanceUrl = $this->getSessionData('instanceUrl');
	public function setParams($c)
	{
		$this->appName = isset($c['entityId']) ? $c['entityId'] : null;
		$this->executed = false;
		$this->debug = isset($c['debug']) ? $c['debug'] : false;
		$this->soqlEndpoint = $c['soqlEndpoint'];
		$this->serviceEndpoint = $c['serviceEndpoint'];
		$this->consumerId = $c['consumerId'];
		$this->clientSecret = $c['clientSecret'];
		$this->endpoints = $c['endpoints'];
		$this->endpoint = $c['soqlEndpoint'];//'/services/data/v29.0/queryAll';
		
		// $this->accessToken = $this->getSessionData('accessToken');
		// $this->instanceUrl = $this->getSessionData('instanceUrl');
	}

	
	public function authorize()
	{
		// Return an HttpRequest object to be sent to the Authorization Server.
		$req = $this->authenticationService->getHttpRequest(OAuthGrantTypes::GRANT_PASSWORD);
	
		// Get a Redirect object whose output can be sent to the User-Agent.
		// This basically redirects the user to the Authorization Server per the
		// above Request.
		$oauthResponse = $this->authenticationService->sendRequest($req);
		$data = json_decode($oauthResponse->read(),true);
		
		if(isset($data['error']))
		{
			throw new Exception("<h2>{$data['error']}: {$data['error_description']}</h2>");
		}
		
		
		$this->setAccessToken($data['access_token']);
		$this->setInstanceUrl($data['instance_url']);
		
		return $oauthResponse;
	}
	
	
	
	public function executeQuery($query)
	{
	
		print "<br /><span style='font-weight:bold;'>About to execute API call using: {$query}</span><br />";
		
		// Sanity checks - make sure required credentials are supplied to the Salesforce Request.
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Invalid URL given for this API.");
		}

		
		if(!$this->hasAccessToken())
		{
			throw new RestApiAuthenticationException("No Access Token was provided with this API request or the Access Token isn't a valid length.");
		}
		

		$this->soqlQuery($query);
		
		
		if($this->debug)
		{
			print "<h3>SOQL query is:</h3>".$this->getSoqlQuery();
			print "<h3>Access Token is:</h3><p>".$this->getAccessToken()."</p>";
		}
		
		
		$apiReq = $this->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_SOQL);
		$apiReq->addHttpHeader('Authorization',"OAuth {$this->getAccessToken()}");
		
		$apiResp = parent::sendRequest($apiReq);
		
		
		$sfResult = new SfResult($apiResp);
		
		
		if($this->debug)
		{
			print "<h3>Response length is: </h3><p><pre>".print_r($apiResp,true)."</pre></p>";
		}
		// exit;

		if($sfResult->hasError())
		{
			if($sfResult->getErrorCode() == 'INVALID_SESSION_ID')
			{
				throw new RestApiAuthenticationException("Accessing the API Service at {$this->getInstanceUrl()} failed with error code: \n{$sfResult->getErrorCode()}.\n<br />".$sfResult->getErrorMsg());
			}
			else throw new \Exception("There was an error executing the SOQL query: {$sfResult->getErrorMsg()}.  Query: {$query}.");
		}
		
		
		return $sfResult;
	}

	
	public function updateRecord()
	{
		// Sanity checks - make sure required credentials are supplied to the Salesforce Request.
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Invalid URL given for this API.");
		}

		
		if(!$this->hasAccessToken())
		{
			throw new RestApiAuthenticationException("No Access Token was provided with this API request or the Access Token isn't a valid length.");
		}
		
		if($this->debug)
		{
			print "<h3>Access Token is:</h3>".$this->getAccessToken();
		}
		
		
		$apiReq = $this->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_RECORD_UPDATE);
		$apiReq->addHttpHeader('Authorization',"OAuth {$this->getAccessToken()}");
		$apiReq->addHttpHeader('Content-type',"application/json");
		if($debug)
		{
			print $apiReq;
		}

		$apiResp = parent::sendRequest($apiReq);
		$sfResult = new SfResult($apiResp);
		
		
		if($this->debug)
		{
			print "Response length is: ".count($apiResp->__toString());
			print_r($apiResp);
		}
		// exit;

		if($sfResult->hasError())
		{
			if($sfResult->getErrorCode() == 'INVALID_SESSION_ID')
			{
				throw new RestApiAuthenticationException("Accessing the API Service at {$this->getInstanceUrl()} failed with error code: \n{$sfResult->getErrorCode()}.\n<br />".$sfResult->getErrorMsg());
			}
			else throw new \Exception("There was an error executing the SOQL query: {$sfResult->getErrorMsg()}.  Query: {$query}.");
		}
		return $sfResult;
	}
	
	public function getObjectInfo($forceObjectName)
	{
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Invalid URL given for this API.");
		}
		// $this->soqlQuery($query);
		$this->setEndpoint('sobject',array('object'=>$forceObjectName));
		$apiReq = $this->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_ENTITY);
		$apiReq->addHttpHeader('Authorization',"OAuth {$this->getAccessToken()}");
		$apiResp = parent::sendRequest($apiReq);
		
		return new SObject(json_decode($apiResp->read(),true));
		/*
		return json_decode($apiResp->read(),true);
		
		$sfResult = new SfResult($apiResp);
		// print $apiResp;
		// exit;

		if($sfResult->hasError())
		{
			if($sfResult->getErrorCode() == 'INVALID_SESSION_ID')
			{
				throw new RestApiAuthenticationException($sfResult->getErrorMsg());
			}
			else throw new \Exception("There was an error executing the SOQL query: {$sfResult->getErrorMsg()}.");
		}
		return $sfResult;
		*/
	}
	
	function sfObjectsInfo($object)
	{
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Instance URL is empty!");
		}
		$svc = ResourceLoader::getResource('forceApi');
		$svc->setEndpoint('sobjects');
		
		$apiReq = $svc->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_ENTITY);
		$apiReq->addHttpHeader('Authorization',"OAuth {$svc->getAccessToken()}");
		$apiResp = $svc->sendRequest($apiReq);
		
		$apiInfo = json_decode($apiResp->read(),true);
		
		if(isset($apiInfo['errorCode']))
		{
			$svc->resetOAuthSession();
		}
		
		
		return $apiInfo;
	}
	
	private function formatEndpoint($str,$params)
	{
		return $this->tokenize($str,$params);
	}
	
	public function setEndpoint($endpointId,$params)
	{
		if(isset($params)&&count($params))
		{
			$this->endpoint=$this->formatEndpoint($this->getEndpoint($endpointId),$params);		
		}
		else
		{
			$this->endpoint=$this->getEndpoint($endpointId);
		}
	}
	
	public function entity_toString($entity)
	{
		if(!is_array($entity))
		{
			return htmlentities($entity);
		}
		return "<pre>".htmlentities(print_r($entity,true))."</pre>";
	}

	public function tokenize($str,$tokens)
	{
		foreach($tokens as $token=>$replace)
		{
			$str=str_replace('{'.$token.'}',$replace,$str);
		}
		return $str;
	}
	
	public function getEndpoint($endpointId)
	{
		return $this->endpoints[$endpointId];
	}
	
	public function setAccessToken($token)
	{
		$this->accessToken=$token;
	}
	
	public function getAccessToken()
	{
		return $this->accessToken;
	}
	
	
	public function hasAccessToken()
	{
		return (empty($this->accessToken) || strlen($this->accessToken) < 25) ? false : true;
	}
	
  
	
	public function deleteAccessToken()
	{
		$this->accessToken = null;
		try
		{
			$this->clearSessionData('accessToken');
		}
		catch(\Exception $e)
		{
			throw new \Exception("Error when trying to delete accessToken: {$e->getMessage()}");
		}
	}
	
	public function deleteInstanceUrl()
	{
		$this->instanceUrl = null;
		try
		{
			$this->clearSessionData('instanceUrl');
		}
		catch(\Exception $e)
		{
			throw new \Exception("Error when trying to delete instanceUrl: {$e->getMessage()}");
		}
	}
	
	public function getInstanceUrl()
	{
		return $this->instanceUrl;
	}
	
	public function setInstanceUrl($url)
	{
		$this->instanceUrl=$url;	
	}
	
	public function hasInstanceUrl()
	{
		return !empty($this->instanceUrl);
	}
	
	public function soqlQuery($query)
	{
		$this->soqlQuery=$query;
	}
	
	public function getSoqlQuery()
	{
		return $this->soqlQuery;
	}
	
	public function makeHttpResponse(){}
	
	public function makeHttpRequest($type)
	{
		if (!isset($access_token) || $access_token == "") {
				throw new Exception("Error - access token missing from session!");
		}

		if (!isset($instance_url) || $instance_url == "") {
				throw new Exception("Error - instance URL missing from session!");
		}
	}

	public function getServiceEndpoint()
	{
		return $this->serviceEndpoint;
	}
	
	public function getActiveEndpoint()
	{
		return $this->endpoint;
	}
	
	public function getHttpRequest($apiRequestType)
	{
		if(!isset($apiRequestType))
		{
			throw new Exception('Service requires an valid API Request Type.');
		}
		switch($apiRequestType)
		{
			case SfRestApiRequestTypes::REST_API_REQUEST_TYPE_SOQL:
				$qString = $this->formatRequestParams(
					$this->getRequestParamsByApiRequestType(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_SOQL)
				);
				$req = new \Clickpdx\Http\HttpPostRequest($this->instanceUrl . $this->endpoint.'?'.$qString);
				return $req; // Don't add any additional parameters
				break;
			case SfRestApiRequestTypes::REST_API_REQUEST_TYPE_RECORD_UPDATE:
				$req = new \Clickpdx\Http\HttpPostRequest($this->instanceUrl . 
					$this->endpoint);
				return $req; // Don't add any additional parameters
				break;
			default:
				$req = new \Clickpdx\Http\HttpPostRequest($this->instanceUrl . $this->endpoint);
				return $req; // Don't add any additional parameters				
		}
		$req->addParams($this->getRequestParamsByApiRequestType(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_ENTITY));
		return $req;
	}

	private function getRequestParamsByApiRequestType($apiRequestType)
	{
		switch($apiRequestType)
		{
			case SfRestApiRequestTypes::REST_API_REQUEST_TYPE_SOQL:
				$params = array(
					'q' => array($this->soqlQuery,true),
				);
				break;
		}
		return $params;
	}
	
	public function addAccessTokenHeader($h,$accessToken)
	{
		$this->addPostHeader($h,'Authorization',"OAuth {$accessToken}");
	}
	
	public function resetOAuthSession()
	{
		$this->destroyServiceSessionData();
	}
	
	public function setOAuthSession($accessToken)
	{
		$this->accessToken = $accessToken;
		$this->setSessionData('accessToken',$accessToken);
	}
	
	public function saveInstanceUrlSession($instanceUrl)
	{
		$this->instanceUrl = $instanceUrl;
		$this->setSessionData('instanceUrl',$instanceUrl);	
	}

	public function returnReponse($resp)
	{
		// print $json_response; exit;
    //$response = json_decode($json_response, true);

    $total_size = $response['totalSize'];

    $sResp =  "$total_size record(s) returned<br /><br />";
    foreach ((array) $response['records'] as $record) {
        $sResp.= ($record['Id'] . ", " . $record['Name'] . "<br />");
    }
		return $sResp;
	}
	
	public function __toString()
	{
		$s[]= "AppId: {$this->appName}";
		$s[]= "Executed: {$this->executed}.";
		$s[]= "endpoint: {$this->endpoint}.";
		$s[]= "soqlEndpoint: {$this->soqlEndpoint}.";
		$s[]= "consumerId: {$this->consumerId}.";		
		$s[]= "soqlQuery: {$this->soqlQuery}.";
		$s[]= "instanceUrl: {$this->instanceUrl}.";
		return "<p style='background-color:#eee;'>".implode('<br />',$s)."</p>";
	}
}