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
	
	private $cols = array();
	
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
	
	public function columns(array $cols)
	{
		$this->cols = $cols;
	}
	
	public function cols(array $cols)
	{
		$this->columns(is_array($cols)?$cols:func_get_args());
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
		$op = SoqlQueryBuilder::QUERY_OP_EQUALITY
	)
	{
		$parts = array(
			'colName' 	=> $colName,
			'op' 				=> $op,
			'colValue'	=> is_string($colValue) ?
				"'{$colValue}'" : $colValue
		);
		$this->conditions[] = implode(' ',$parts);
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
	
	public function compile()
	{
		return $this->query = implode(' ',array(
				'type'				=> $this->formatQueryType(),
				'colList'			=> $this->formatColumnList(),
				'table'				=> $this->formatTableName(),
				'conditions'	=> $this->formatConditions(),
				'options'			=> $this->formatOptions()
			)
		);
	}
	
	public function getCountQuery()
	{
		$countQuery = new self();
		$countQuery->table($this->table);
		$countQuery->cols('COUNT()');
		$countQuery->replaceConditions($this->getConditions());
		return $countQuery;
	}
	
	public function __toString()
	{
		$this->compile();
		return $this->query;
	}
	
}