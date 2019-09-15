<?php
namespace Clickpdx\Http;

class HttpPostRequest extends HttpRequest
{
	public $h;
	
	public function read()
	{
		return $this->reqBody;
	}
	public function __construct($url,$params=array())
	{
		parent::__construct($url,$params);
	}	
	public function write()
	{
		$this->h = curl_init($this->uri);
		curl_setopt($this->h, CURLOPT_HEADER, false);
		curl_setopt($this->h, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->h, CURLOPT_POST, true);
		curl_setopt($this->h, CURLOPT_POSTFIELDS, $this->formatPostFields());
		return curl_exec($this->h);
	}	

	
	public function formatPostFields()
	{
		if(!\count($this->params)) return '';
		$qStr=array();
		foreach($this->params as $k=>$v)
		{
			$qStr[]=($k . "=" . ($v[1]?\urlencode($v[0]):$v[0]));
		}
		return implode('&',$qStr);
	}
	public function __toString()
	{
		return parent::__toString();
	}
}