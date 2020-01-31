<?php

namespace Clickpdx\Salesforce;

class SoqlQueryBuilder
{
	private $query;
	
	private $comments;
	
	private $table;
	
	private $conditions = array();
	
	private $queryType;
	
	private $options = array();
	
	private $newOptions = array();
	
	private $cols = array();
	
	private $key = null;
	
	private $breakColumn = null;
	
	const QUERY_TYPE_SELECT = 'SELECT';
	
	const QUERY_OP_EQUALITY = '=';
	
	const QUERY_OP_GREATER_THAN = '>';
	
	const QUERY_OP_LESS_THAN = '<';
	
	public function __construct($type = SoqlQueryBuilder::QUERY_TYPE_SELECT)
	{
		$this->queryType = $type;
	}
	
	public function orderBy($colName,$dir = 'ASC')
	{
		$parts = array("ORDER BY",$colName,$dir);
		$this->options[] = implode(' ',$parts);
	}
	
	public function limit($limitNum)
	{
		$parts = array("LIMIT",$limitNum);
		$this->options[] = implode(' ',$parts);
	}
	
	public function addOption($key,$value) {
		$this->newOptions[$key] = $value;
	}
	
	public function columns(array $cols)
	{
		$this->cols = $cols;
	}
	
	public function cols($cols)
	{
		$this->columns(is_array($cols)?$cols:func_get_args());
	}
	
	public function getBreakColumn() {
		return $this->breakColumn;
	}
	
	public function setBreakColumn($col) {
		$this->breakColumn = $col;
	}
	
	public function setKey($key) {
		$this->key = $key;
	}
	
	public function table($tableName)
	{
		$this->table = $tableName;
	}
	
	public function formatTableName()
	{
		return implode(' ',array("FROM",$this->table));
	}

	
	public function where(
		$colName,
		$colValue,
		$op = SoqlQueryBuilder::QUERY_OP_EQUALITY
	)
	{
		if(count(func_get_args())==1)
		{
			$this->conditions = array($colName);
			return;
		}
		$parts = array(
			'colName' 	=> $colName,
			'op' 				=> $op,
			'colValue'	=> is_string($colValue) ?
				"'{$colValue}'" : $colValue
		);
		$this->conditions = array(implode(' ',$parts));
	}
	

	public function getQueryType()
	{
		return $this->queryType;
	}
	
	public function formatQueryType()
	{
		return $this->queryType;
	}
	
	public function getColumnList()
	{
		return $this->cols;
	}
	
	private function formatColumnList()
	{
		return implode(', ',$this->cols);
	}
	
	public function getConditions()
	{
		return $this->conditions;
	}
	
	public function condition(
		$colName,
		$colValue,
		$op = SoqlQueryBuilder::QUERY_OP_EQUALITY,
		$alias = null
	)
	{
		$parts = array(
			'colName' 	=> $colName,
			'op' 				=> $op,
			'colValue'	=> is_string($colValue) ?
				"'{$colValue}'" : $colValue
		);
		if($alias) {
			$this->conditions[$alias] = implode(' ',$parts);
		} else {
			$this->conditions[] = implode(' ',$parts);
		}
	}
	
	public function dateCondition(
		$colName,
		$colValue,
		$op = SoqlQueryBuilder::QUERY_OP_EQUALITY
	)
	{
		$parts = array(
			'colName' 	=> $colName,
			'op' 				=> $op,
			'colValue'	=> $colValue
		);
		$this->conditions[] = implode(' ',$parts);
	}
	
	public function replaceConditions(array $conditions)
	{
		$this->conditions = $conditions;
	}
	
	private function formatConditions()
	{
		return count($this->conditions) ?
			"WHERE ".implode(' AND ',$this->conditions) :
			 '';
	}
	
	public function getOptions()
	{
		return $this->options;
	}
	
	private function formatOptions()
	{
		return implode(' ',$this->options);
	}
	
	private function formatNewOptions(){
		$str = [];

		if(!empty($this->newOptions["ORDER BY"])) {
			$str []= "ORDER BY ".$this->newOptions["ORDER BY"];
		}
		
		if(!empty($this->newOptions["LIMIT"])) {
			$str []= "LIMIT ".$this->newOptions["LIMIT"];
		}
		
		return implode(" ",$str);
	}
	
	
	public function compile()
	{
		return $this->query = implode(' ',array(
				'type'				=> $this->formatQueryType(),
				'colList'			=> $this->formatColumnList(),
				'table'				=> $this->formatTableName(),
				'conditions'	=> $this->formatConditions(),
				'options'			=> $this->formatOptions(),
				'newOptions'	=> $this->formatNewOptions()
			)
		);
	}
	
	public function getCountQuery()
	{
		$countQuery = new self();
		$countQuery->table($this->table);
		$countQuery->cols('COUNT()');
		$countQuery->replaceConditions($this->getConditions());
		
		// var_dump($countQuery);
		
		return $countQuery;
	}
	
	
	public static function fromSettings($object) {
			$builder = new SoqlQueryBuilder();
			
			$pfx = 'force.import.object.'.strtolower($object);
			
			$builder->table(\setting($pfx.'.mysqlTableName'));
			$builder->cols(\setting($pfx.'.fields'));
			$builder->orderBy(\setting($pfx.'.breakField'));
			// $builder->setKey(\setting($pfx.'.key'));
			// $builder->limit($batchSize);
			/* if(!empty($this->conditionField))
			{
				// Test if this is a date or not
				// Basically test for the field type
				$builder->dateCondition($this->conditionField,
						$this->conditionValue,
						SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
					
				// $builder->where('Ocdla_Interaction_Line_Item_ID__c = null');
			}
			*/
			
			return $builder;
	}
	
	
	public function __toString()
	{
		$this->compile();
		return $this->query;
	}
	
}