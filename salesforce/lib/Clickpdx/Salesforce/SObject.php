<?php

namespace Clickpdx\Salesforce;

class SObject// extends SfResult
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
	
	public function getFields()
	{
		return $this->json['fields'];
	}
	
	
	
	public function __toString()
	{
		return '<pre>'.print_r($this->json,true).'</pre>';
	}
	
	
	
	private function loadField($fieldApiName)
	{
		$valid = array_filter($this->json['fields'],function($item) use($fieldApiName){
			return $item['name'] == $fieldApiName;
		});
		// print "<pre>". print_r($valid,true)."</pre>";exit;
		 	
		return array_pop($valid);//['picklistValues'];
	}



	/**
	 * @method doApiSchemaRequest
	 *
	 * @description
	 *		Utility function to execute the schema API call.
	 *
	 * @param $forceApi
	 *		The connection object on which to call getObjectInfo();
	 *
	 * @return
	 *		Clickpdx\Salesforce\sfResult - A Salesforce result object.
	 */
	private function doApiSchemaRequest($forceApi)
	{
		$sfResult = $forceApi->getObjectInfo($forceObjectName);
		return $sfResult;
	}
	
	/**
	 * @method setRequest
	 *
	 * @description
	 *		Set the request object used to forward requests for this object's info.
	 *
	 * @param ApiRequest
	 *
	 * @return void
	 */
	public function setRequest($forceApi)
	{
		$this->api = $forceApi;
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
	
	
	public function getField($fieldApiName)
	{
		return new SField($this->loadField($fieldApiName));
	}
	
	
	
}