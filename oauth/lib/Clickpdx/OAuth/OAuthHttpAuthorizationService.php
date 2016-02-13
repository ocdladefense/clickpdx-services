<?php
namespace Clickpdx\OAuth;



class OAuthHttpAuthorizationService extends \Clickpdx\Service\HttpService
{
	private $responseType;
	
	private $consumerId;
	
	private $redirectUri;
	
	private $authUri;
	
	private $authEndpoint;
	
	private $loginUri;
	
	private $executed;
	
	private $username;
	
	private $password;
	

	public function setOAuthParams($c)
	{
		$this->executed = false;
		$this->loginUri = $c['loginUri'];
		$this->consumerId = $c['consumerId'];
		$this->clientSecret = $c['clientSecret'];
		$this->redirectUri = $c['redirectUri'];
		$this->authEndpoint = $c['authEndpoint'];
		$this->accessTokenEndpoint = $c['accessTokenEndpoint'];
		$this->username = $c['username'];
		$this->password = $c['password'];
	}
	public function setAuthorizationCode($code)
	{
		$this->authorizationCode=$code;
	}
	public function makeHttpResponse()
	{
		$this->httpResponse = new \Clickpdx\Http\HttpRedirect($this->authUri);
		return $this;
	}
	
	public function makeHttpRequest($type){}
	
	public function getAccessToken()
	{
		// create new HttpRequest('POST');
		
		// format the Request body
		
		// store the Request in this Service object
		
		// call the send method, which stores the response

		$token_url = $this->getAccessTokenUrl();

		$params->
		
		$req = $this->createHttpRequest($token_url,$params);
	
		$json_response = $this->sendHttpRequest($req);
	}

	
	public function getUserAgentHttpRedirect($req)
	{
		return \Clickpdx\Http\HttpRedirect::createFromRequest($req);
	}
	
	public function getHttpRequest($authorizationGrantType)
	{
		if(!isset($authorizationGrantType))
		{
			throw new Exception('A valid Authorization Grant Type is required.');
		}
		switch($authorizationGrantType)
		{
			case OAuthGrantTypes::GRANT_NONE:
				$req = new \Clickpdx\Http\HttpRequest($this->loginUri . $this->getAuthorizationEndpoint());
				break;
			case OAuthGrantTypes::GRANT_AUTHORIZATION_CODE:
				$req = new \Clickpdx\Http\HttpPostRequest($this->loginUri . $this->accessTokenEndpoint);
				break;
			case OAuthGrantTypes::GRANT_PASSWORD:
				$req = new \Clickpdx\Http\HttpPostRequest($this->loginUri . $this->accessTokenEndpoint);
				break;
		}
		$req->addParams($this->getRequestParamsByAuthorizationGrantType($authorizationGrantType));
		return $req;
	}
	
	private function checkAuthorizationCode($code)
	{//function checkrequestparams()
		if(!isset($code))
		{
			throw new Exception('Authorization code required but missing from request');
		}
	}

	
	private function getRequestParamsByAuthorizationGrantType($authorizationGrantType)
	{
		switch($authorizationGrantType)
		{
			case OAuthGrantTypes::GRANT_NONE:
				$params = array(
					'response_type' => array('code',false),
					'client_id' => array($this->consumerId,false),
					'redirect_uri' => array($this->redirectUri,true),
				);
				break;
			case OAuthGrantTypes::GRANT_AUTHORIZATION_CODE:
				$params = array(
					'code' 							=> array($this->authorizationCode,false),
					'grant_type'				=> array('authorization_code',false),
					// 'response_type' 		=> array('code',false),
					'client_id' 				=> array($this->consumerId,false),
					'client_secret'			=> array($this->clientSecret,false),
					'redirect_uri' 			=> array($this->redirectUri,true),
				);
			case OAuthGrantTypes::GRANT_PASSWORD:
				$params = array(
					'grant_type'				=> array('password',false),
					'client_id' 				=> array($this->consumerId,false),
					'client_secret'			=> array($this->clientSecret,false),
					'username' 					=> array($this->username,false),
					'password' 					=> array($this->password,false),
				);
		}
		return $params;
	}



	private function getResponseBody($responseType)
	{
		switch($responseType)
		{
			case 'code':
				$params = array(
					'response_type' => array('code',false),
					'client_id' => array($this->consumerId,false),
					'redirect_uri' => array($this->redirectUri,true),
				);
				break;
			case 'userpass':
				break;
		}
		return $this->formatRequestParams($params);
	}


	private function getAuthorizationUrl()
	{
		return $this->loginUri . $this->authEndpoint;
	}
	private function getAccessTokenUrl()
	{
		return $this->loginUri . $this->accessTokenEndpoint;
	}
	private function getAuthorizationEndpoint()
	{
		return $this->authEndpoint;
	}
	private function getAccessTokenEndpoint()
	{
		return $this->accessTokenEndpoint;
	}
	public function __construct(/*\OAuthParameterCollection*/$c)
	{
		if($c)
		{
			$this->setOauth($c);
		}
	}
	public function __toString()
	{
		$s[]= "Executed: {$this->executed}.";
		$s[]= "loginUri: {$this->loginUri}.";
		$s[]= "consumerId: {$this->consumerId}.";
		$s[]= "redirectUri: {$this->redirectUri}.";				
		$s[]= "authEndpoint: {$this->authEndpoint}.";				
		return implode('<br />',$s);
	}
}