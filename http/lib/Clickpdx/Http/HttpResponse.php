<?php
namespace Clickpdx\Http;

class HttpResponse extends HttpMessage implements IReadable
{	
	protected $status;
	
	public function __construct(){}
	
	public function addRespBody($str)
	{
		$this->body .= $str;
	}
	public function read()
	{
		return $this->body;
	}
	public function __toString()
	{
		return '<p /><b>Response body is:</b><br />'.$this->body;
		return parent::__toString();
	}
}