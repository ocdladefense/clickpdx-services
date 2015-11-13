<?php
namespace Clickdpx\Oauth;

class OAuthHttpAuthorizationResponse extends HttpResponse
{
	private $oauthAccessToken;
	
	private $oauthInstanceUrl;
	
	
	public function __construct($uri)
	{
		parent::__construct($uri);
	}
	public function read()
	{
		// $this->responseBody
		$data = json_decode($this->responseBody, true);

		$access_token = $data['access_token'];
		$instance_url = $data['instance_url'];

		if (!isset($access_token) || $access_token == "") {
				die("Error - access token missing from response!");
		}

		if (!isset($instance_url) || $instance_url == "") {
				die("Error - instance URL missing from response!");
		}
		return $data;
	}
}