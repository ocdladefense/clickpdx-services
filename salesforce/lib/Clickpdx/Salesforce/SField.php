<?php

namespace Clickpdx\Salesforce;

class SField// extends SfResult
{
	/**
	 * @var String forceObjectName
	 *
	 * @description 
	 *		The API name of the object to be retrieved.
	 */
	private $json;


	/**
	 * @constructor
	 *
	 */	
	public function __construct($json)
	{
		$this->json = $json;
		// $this->forceObjectName = $forceObjectName;
	}



	public function __toString()
	{
		return 'Field name: '.$this->json['name'];
	}



	public function getPicklistValuesAll()
	{
		return $this->json['picklistValues'];
	}



	public function getPicklistValues()
	{
		return array_filter($this->json['picklistValues'],function($item) use($fieldApiName){
			return $item['active'] == 1;
		});
	}


	
	public function objectInfo()
	{
		$json = $this->doApiSchemaRequest($this->forceObjectName);

		if(!count($json))
		{
			throw new \Exception('Error: returned JSON was empty');
		}
		return $json;
	}

	
	
	
}