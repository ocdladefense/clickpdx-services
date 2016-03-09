<?php

namespace Clickpdx\Salesforce;

class SoqlBatchSelectQueryManager
{
	
	private $breakColumn;
	
	private $columns;
	
	private $table;

	private $batchSize = 2000;
	
	private $soqlService;
	
	private $updatedAfterDate;
	
	private $keys = array();
	
	const MySQLTablePrefix = 'force';
	
	public function __construct($soqlService,$table=null,$columns=array(),$breakColumn=null)
	{
		$this->soqlService = $soqlService;
	}
	
	public function setTable($table)
	{
		$this->table = $table;
		return $this;
	}
	
	public function setColumns($columns)
	{
		$this->columns = is_array($columns) ? $columns : func_get_args();
		return $this;
	}
	
	public function setBreakColumn($column)
	{
		$this->breakColumn = $column;
		return $this;
	}
	
	public function setUpdatedAfterDate($date=null)
	{
		if(!empty($date))
		{
			$this->updatedAfterDate = $date.'T00:00:00Z';
		}
	}
	
	public function execute()
	{
		$curBatch = 1;
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		
		$query = new SoqlQueryBuilder();
		$query->table($this->table);
		$query->cols($this->columns);
		$query->orderBy($this->breakColumn);
		$query->limit($this->batchSize);
		if(!empty($this->updatedAfterDate))
		{
			$query->dateCondition('LastModifiedDate',
					$this->updatedAfterDate,
					SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
		}
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		$numRecordsToProcess = $this->soqlService->executeQuery($query->getCountQuery()->compile())->count();
		$expectedNumBatches = $numRecordsToProcess/$batchSize;

	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.
		$results = new SfResult();
		do
		{
			// print "<br />Starting batch {$curBatch}...";
			if($curBatch != 1)
			{
				$query->condition('Ocdla_Auto_Number_Int__c',
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
			}
			$queries[] = $q = $query->compile();
			$results->add($sfResult = $this->soqlService->executeQuery($q));
			if(!$sfResult->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.");
			}
			$lastId = $sfResult->getLast()['Ocdla_Auto_Number_Int__c'];
			// print "<br />Testing condition: ".$curBatch++*$this->batchSize ." < {$numRecordsToProcess}...";
		}
		while($curBatch++*$this->batchSize < $numRecordsToProcess);
		$results->addComment($queries,'queries');
		return $results;
	}

	public function setKeys($colNames)
	{
		$this->keys = $colNames;
	}
	
	public function setKey($colName)
	{
		$this->keys = array($colName);
	}

	private function getTable()
	{
		return self::MySQLTablePrefix .'_'.strtolower($this->table);
	}

	private function getValueBindings()
	{
		$ret = array_map(function($colName){
			return ':'.$colName;
		},$this->columns);
		return implode(',',$ret);
	}

	private function getInsertPart()
	{
		return implode(',',$this->columns);
	}
	
	private function getUpdatePart()
	{
		$tmp = array_diff($this->columns,$this->keys);
		$ret = array_map(function($colName){
			return $colName . '=VALUES('.$colName.')';
		},$tmp);
		return implode(',',$ret);
	}

	public function toMysqlInsertQuery()
	{
		return 'INSERT INTO ' . $this->getTable() .'(' .$this->getInsertPart() .')' .' VALUES('.$this->getValueBindings().') ON DUPLICATE KEY UPDATE '. $this->getUpdatePart();
	}
	


}