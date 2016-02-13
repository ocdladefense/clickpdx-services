<?php

namespace Clickpdx\Salesforce;

class SfResult implements \IteratorAggregate, \ArrayAccess
{
	private $errorCode;
	
	private $errorMsg;
	
	private $done;
	
	/**
	 * member $records
	 *
	 * The actual records returned from the SOQL query.
	 */
	private $records;
	
	private $fields;
	
	public function __construct($apiResp)
	{
		$res = json_decode($apiResp->read(),true);
		$this->errorCode = $res[0]['errorCode'];
		$this->errorMsg = $res[0]['message'];
		$this->done = $res['done'];
		$this->records = $res['records'];
		$this->fields = array_keys($this->getFirst());
	}
	
	public function offsetSet($offset, $value) {
			if (is_null($offset)) {
					$this->records[] = $value;
			} else {
					$this->records[$offset] = $value;
			}
	}

	public function offsetExists($offset) {
			return isset($this->records[$offset]);
	}

	public function offsetUnset($offset) {
			unset($this->records[$offset]);
	}

	public function offsetGet($offset) {
			return isset($this->records[$offset]) ? $this->records[$offset] : null;
	}
	
	public function getFirst()
	{
		return $this->records[0];
	}
	
	public function count()
	{
		return \count($this->records);
	}
	
	public function getLast() {}
	
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
	
	public function __toString()
	{
		return "<p style='background-color:#eee;'>".implode(',',$this->fields)."</p>".implode('<br />',$this->map(function($record){
			return $record['Id'] . ': '.$record['Name'];
		}));
	}

}