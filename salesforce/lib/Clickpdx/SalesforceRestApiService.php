<?php
namespace Clickpdx;
use \Exception;
use Clickpdx\OAuth\OAuthGrantTypes;
use Clickpdx\SfRestApiRequestTypes;
use Clickpdx\Http\HttpRequest;
use Clickpdx\Salesforce\SfResult;
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
	
	public function __construct(/*\OAuthParameterCollection*/$c)
	{
		if($c)
		{
			$this->setParams($c);
		}
		$this->accessToken=$this->getSessionData('accessToken');
		$this->instanceUrl=$this->getSessionData('instanceUrl');
	}
	
	public function setDebug($init=false)
	{
		$this->debug = $init;
	}
	
	public function setParams($c)
	{
		$this->appName = $c['entityId'];
		$this->executed = false;
		$this->soqlEndpoint = $c['soqlEndpoint'];
		$this->serviceEndpoint = $c['serviceEndpoint'];
		$this->consumerId = $c['consumerId'];
		$this->clientSecret = $c['clientSecret'];
		$this->endpoints = $c['endpoints'];
		$this->endpoint = $c['soqlEndpoint'];
		$this->accessToken=$this->getSessionData('accessToken');
		$this->instanceUrl=$this->getSessionData('instanceUrl');
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
		if($data['error'])
		{
			throw new Exception("<h2>{$data['error']}: {$data['error_description']}</h2>");
			// throw new AuthenticationException($data['error_description']);
		}
		$this->setOAuthSession($data['access_token']);
		$this->saveInstanceUrlSession($data['instance_url']);
		return $oauthResponse;
	}
	
	public function executeQuery($query)
	{
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Invalid URL given for this API.");
		}
		$this->soqlQuery($query);
		if($this->debug)
		{
			print "<br />".$this->getSoqlQuery();
			print "<br />Access Token is: ".$this->getAccessToken();
		}
		$apiReq = $this->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_SOQL);
		$apiReq->addHttpHeader('Authorization',"OAuth {$this->getAccessToken()}");
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
			else throw new \Exception("There was an error executing the SOQL query: {$sfResult->getErrorMsg()}.");
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
	}
	
	function sfObjectsInfo($object)
	{
		if(!$this->hasInstanceUrl())
		{
			throw new RestApiInvalidUrlException("Invalid URL given for this API.");
		}
		$svc = ResourceLoader::getResource('forceApi');
		$svc->setEndpoint('sobjects');
		$apiReq=$svc->getHttpRequest(SfRestApiRequestTypes::REST_API_REQUEST_TYPE_ENTITY);
		$apiReq->addHttpHeader('Authorization',"OAuth {$svc->getAccessToken()}");
		$apiResp = $svc->sendRequest($apiReq);
		$apiInfo = json_decode($apiResp->read(),true);
		if($apiInfo['errorCode'])
		{
			$svc->resetOAuthSession();
		}
		return $apiInfo;
	}
	
	private function formatEndpoint($str,$params)
	{
		return \tokenize($str,$params);
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