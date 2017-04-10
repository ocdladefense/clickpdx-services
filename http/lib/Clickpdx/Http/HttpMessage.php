<?php
namespace Clickpdx\Http;

class HttpMessage
{
	protected $uri;
	
	protected $headers = array();
	
	protected $body = null;
	
	protected $params = array();
	
	public function __construct(){}
	
	public function setBody($data)
	{
		$this->body=$data;
	}
	public function setUri($uri)
	{
		$this->uri=$uri;
	}
	public function getUri()
	{
		return $this->uri;
	}
	public function addParam($name,$value,$encode=false)
	{
		$this->params[$name]=array($value,$encode);
	}
	public function addHttpHeader($name,$value)
	{
		$this->headers[$name]=$value;
	}
	public function addParams($nameValuePairs)
	{
		$this->params=$nameValuePairs;
	}
	public function setParams($nameValuePairs)
	{
		$this->params=$nameValuePairs;
	}
	public function getParam($name)
	{
		return $this->params[$name][0];
	}
	public function getParams()
	{
		return $this->params;
	}
	protected function formatQueryString()
	{
		if(!\count($this->params)) return '';
		$qStr=array();
		foreach($this->params as $k=>$v)
		{
			$qStr[]=($k . "=" . ($v[1]?\urlencode($v[0]):$v[0]));
		}
		return "?".implode('&',$qStr);
	}
	protected function hasParams()
	{
		return (!count($this->params)?false:true);
	}
	protected function formatHttpHeaders()
	{
		$headers=array();
		foreach($this->headers as $name=>$value)
		{
			$headers[]="{$name}: {$value}";
		}
		return $headers;
	}
	public function getUrl()
	{
		return $this->uri .($this->hasParams()?$this->formatQueryString():'');
	}
	public function write()
	{
		$this->reqBody = $body;
	}
	public function __toString()
	{
		$p = '';
		foreach($this->getParams() as $k=>$data)
		{
			$p .= "{$k}={$data[0]} (encode:{$data[1]}).<br />";
		}
		$ret = array(
			'<b>Params are:</b><br /> '.$p,
			'<b>Request URL is:</b><br /> '.$this->uri.($this->hasParams()?$this->formatQueryString():'')
		);
		return implode('<br />',$ret);
	}
}