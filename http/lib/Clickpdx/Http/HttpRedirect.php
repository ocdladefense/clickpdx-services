<?php
namespace Clickpdx\Http;



class HttpRedirect extends HttpResponse
{

	public static function createFromRequest($req)
	{		
		return new HttpRedirect($req->getUrl());
	}
	public function __construct($uri)
	{
		$this->uri = $uri;
				
		$this->httpHeader = "Location: {$this->uri}";
	}

	public function write()
	{
		header($this->httpHeader);
		exit;
	}
	public function addHeader()
	{
		$this->headers[] = array('label','value');
	}
	public function read()
	{
		// A redirect might have a response that can be read in some sense...
	}
	public function __toString()
	{
		return parent::__toString();
	}
}