<?php
namespace Clickdpx\Oauth;

class OAuthHttpRedirect extends HttpRedirect
{
	public function __construct($uri)
	{
		parent::__construct($uri);
	}
}