<?php
namespace Clickpdx\Http;

class HttpRequest extends HttpMessage implements IWriteable
{

	public static function createFromGlobals()
	{
		$p = array();
		foreach($_GET as $k=>$v)
		{
			$p[$k]=array($v,false);
		}
		$r = new HttpRequest(self::parseUrl($_SERVER['PHP_SELF']));
		$r->setParams($p);
		return $r;
	}
	
	public function writeUsingDelegate($delegate)
	{
		return $delegate($this);
	}	
	
	public function addHeaders()
	{
    curl_setopt($this->h, CURLOPT_HTTPHEADER, $this->formatHttpHeaders());
	}
	
	public static function parseUrl($url)
	{
		return (
			false !== ($qPos=strpos($url,'?'))?
			substr($url,0,$qPos):
			$url
		);
	}
	
	public function __construct($url)
	{
		$this->uri = $url;
	}
	
	public function __toString()
	{
		return parent::__toString();
	}
	
	public function getAsHttpRedirect($redirectCode=302)
	{
		return new HttpRedirect($this->getUrl());
	}
	
}