<?php
namespace Clickpdx;
use \Exception;

class SalesforceRestApiService extends Service\HttpService
{
	
	private $executed;
	
	private $instanceUrl;
	
	private $serviceEndpoint;
	
	private $accessToken;
	
	private $soqlQuery;
	
	private $endpoints;
	
	private $endpoint;
	
	public function __construct(/*\OAuthParameterCollection*/$c)
	{
		if($c)
		{
			$this->setParams($c);
		}
		$this->accessToken=$this->getSessionData('accessToken');
		$this->instanceUrl=$this->getSessionData('instanceUrl');
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
	
	public function setInstanceUrl($url)
	{
		$this->instanceUrl=$url;	
	}
	
	public function soqlQuery($query)
	{
		$this->soqlQuery=$query;
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
		$this->setSessionData('accessToken',$accessToken);
	}
	
	public function saveInstanceUrlSession($instanceUrl)
	{
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
		$s[]= "Executed: {$this->executed}.";
		$s[]= "endpoint: {$this->endpoint}.";
		$s[]= "soqlEndpoint: {$this->soqlEndpoint}.";
		$s[]= "consumerId: {$this->consumerId}.";		
		$s[]= "soqlQuery: {$this->soqlQuery}.";
		$s[]= "instanceUrl: {$this->instanceUrl}.";
		return implode('<br />',$s);
	}
}