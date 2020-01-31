<?php

namespace Clickpdx\Salesforce;

class SfResult implements \IteratorAggregate, \ArrayAccess
{
	protected $errorCode;
	
	protected $errorMsg;
	
	protected $done;
	
	/**
	 * member $records
	 *
	 * The actual records returned from the SOQL query.
	 */
	protected $records = array();
	
	protected $fields = array();
	
	protected $res;
	
	protected $totalSize;
	
	protected $comments = array();
	
	public function __construct($apiResp)
	{
		if(isset($apiResp))
		{
			$this->res = $res = json_decode($apiResp->read(),true);
		
			if(isset($res[0])) {
				$this->errorCode = $res[0]['errorCode'];
				$this->errorMsg = $res[0]['message'];
			}
		
			$this->done = $res['done'];
			$this->records = $res['records'];
			$this->totalSize = $res['totalSize'];
			$this->fields = array_keys($this->getFirst());
		}
	}
	
	public function add(SfResult $result)
	{
		// $this->res = $res = json_decode($apiResp->read(),true);
		/*
		$this->errorCode = $res[0]['errorCode'];
		$this->errorMsg = $res[0]['message'];
		$this->done = $res['done'];
		*/
		$this->records 			= array_merge($this->records,$result->fetchAll());
		$this->totalSize 		= $this->count() + $result->count();
		// $this->fields 			= array_keys($this->getFirst());
	}
	
	public function getJson()
	{
		return $this->res;
	}
	
	public function offsetSet($offset, $value)
	{
			if (is_null($offset)) {
					$this->records[] = $value;
			} else {
					$this->records[$offset] = $value;
			}
	}

	public function offsetExists($offset)
	{
			return isset($this->records[$offset]);
	}

	public function offsetUnset($offset)
	{
			unset($this->records[$offset]);
	}

	public function offsetGet($offset)
	{
			return isset($this->records[$offset]) ? $this->records[$offset] : null;
	}
	
	public function getFirst()
	{
		return $this->records[0];
	}
	
	public function count()
	{
		return count($this->records);//isset($this->totalSize) ? $this->totalSize : 0;
	}
	
	public function getLast()
	{
		return $this->records[count($this->records)-1];
	}
	
	public function fetchAll()
	{
		return $this->records;
	}
	
	public function hasError()
	{
		return !empty($this->errorCode);
	}
	
	public function getErrorMsg()
	{
		return $this->errorMsg;
	}
	
	public function getErrorCode()
	{
		return $this->errorCode;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->records);
	}
	
	public function map(callable $fn)
	{
		$result = array();

		foreach ($this as $item)
		{
			$result[] = $fn($item);
		}

		return $result;
	}
	
	public function addComment($data,$tag=null)
	{
		$this->comments[(empty($tag)?null:$tag)] = $data;
	}
	
	public function getComment($tag)
	{
		return $this->comments[$tag];
	}
	
	public function getComments()
	{
		return $this->comments;
	}
	
	public function __toString()
	{
		return \entity_toString($this->res);
		return "<p style='background-color:#eee;'>".implode(',',$this->fields)."</p>".implode('<br />',$this->map(function($record){
			return $record['Id'] . ': '.$record['Name'];
		}));
	}

}