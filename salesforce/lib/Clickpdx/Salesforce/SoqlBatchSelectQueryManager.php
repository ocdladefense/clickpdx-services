<?php

namespace Clickpdx\Salesforce;

class SoqlBatchSelectQueryManager
{
	const FEATURE_NOT_READY = false;
	
	private $breakColumn;
	
	private $columns;
	
	private $table;

	// This should correspond to the LIMIT clause
	private $batchSize = 2000;
	
	private $soqlService;
	
	private $conditionField = 'LastModifiedDate';
	
	private $conditionValue;
	
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
	
	public function setConditionValue($date=null)
	{
		if(!empty($date))
		{
			$this->conditionValue = $date;
		}
	}
	
	public function setConditionField($fieldName)
	{
		$this->conditionField = $fieldName;
	}

	public function executeQuery($query)
	{
		// Once incremented the current batch will start at 1
		//	+ i.e., the first batch.
		$curBatch = 0;
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		// Initially the delimiter id should be the Integer, 0
		$lastId = 0;
		
		// $query = new SoqlQueryBuilder();
		// $query->table($this->table);
		// $query->cols($this->columns);
		// $query->orderBy($this->breakColumn);
		// $query->limit($this->batchSize);
		if(self::FEATURE_NOT_READY && !empty($this->conditionField))
		{
			// Test if this is a date or not
			// Basically test for the field type
			$query->dateCondition($this->conditionField,
					$this->conditionValue,
					SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
					
			// $query->where('Ocdla_Interaction_Line_Item_ID__c = null');
		}
		$this->soqlService->setEndpoint('queryAll');
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		$numRecordsToProcess = $this->soqlService->executeQuery($query)->count();
		$expectedNumBatches = $numRecordsToProcess / $this->batchSize;
		
		print "Total number of records that should be processed: ".$numRecordsToProcess;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.
		$results = new SfResult();
		do
		{
			// print "<br />Starting batch {$curBatch}...";
			if(++$curBatch != 1)
			{
				$query->condition('Ocdla_Auto_Number_Int__c',
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
				$query->condition($this->getBreakColumn(),
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
				// $query->condition('Ocdla_Auto_Number_Int__c',
					// $lastId,SoqlQueryBuilder::QUERY_OP_EQUALITY);
			}
			$queries[] = $q = $query;//@jbernal->compile();
			$results->add($sfResult = $this->soqlService->executeQuery($query));
			print "<br />Found ".$sfResult->count() . " records when delimiter id is: {$lastId}.";
			if($sfResult->hasError())
			{
				throw new \Exception($sfResult->getErrorMsg());
			}
			if(!$sfResult->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.  Query was: {$q}.");
			}
			$lastId = $sfResult->getLast()[$this->getBreakColumn()];
			if(empty($lastId))
			{
				// throw new \Exception("The given auto field for delimiting batches was empty or not called from this batch's SELECT query.");
			}
			// print "<br />Testing condition: ".$curBatch++*$this->batchSize ." < {$numRecordsToProcess}...";
		}
		while($curBatch*$this->batchSize < $numRecordsToProcess);
		$results->addComment($queries,'queries');
		return $results;
	}
	
	public function execute()
	{
		// Once incremented the current batch will start at 1
		//	+ i.e., the first batch.
		$curBatch = 0;
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		// Initially the delimiter id should be the Integer, 0
		$lastId = 0;
		
		$builder = new SoqlQueryBuilder();
		$builder->table($this->table);
		$builder->cols($this->columns);
		$builder->orderBy($this->breakColumn);
		$builder->limit($this->batchSize);
		if(!empty($this->conditionField))
		{
			// Test if this is a date or not
			// Basically test for the field type
			$builder->dateCondition($this->conditionField,
					$this->conditionValue,
					SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
					
			// $builder->where('Ocdla_Interaction_Line_Item_ID__c = null');
		}
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		$numRecordsToProcess = $this->soqlService->executeQuery($builder->getCountQuery()->compile())->count();
		$expectedNumBatches = $numRecordsToProcess / $this->batchSize;
		
		print "Total number of records that should be processed: ".$numRecordsToProcess;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.
		$results = new SfResult();
		do
		{
			// print "<br />Starting batch {$curBatch}...";
			if(++$curBatch != 1)
			{
				$builder->condition('Ocdla_Auto_Number_Int__c',
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
				$builder->condition($this->getBreakColumn(),
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
				// $builder->condition('Ocdla_Auto_Number_Int__c',
					// $lastId,SoqlQueryBuilder::QUERY_OP_EQUALITY);
			}
			$queries[] = $q = $builder->compile();
			$results->add($sfResult = $this->soqlService->executeQuery($q));
			print "<br />Found ".$sfResult->count() . " records when delimiter id is: {$lastId}.";
			if($sfResult->hasError())
			{
				throw new \Exception($sfResult->getErrorMsg());
			}
			if(!$sfResult->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.  Query was: {$q}.");
			}
			$lastId = $sfResult->getLast()[$this->getBreakColumn()];
			if(empty($lastId))
			{
				// throw new \Exception("The given auto field for delimiting batches was empty or not called from this batch's SELECT query.");
			}
			// print "<br />Testing condition: ".$curBatch++*$this->batchSize ." < {$numRecordsToProcess}...";
		}
		while($curBatch*$this->batchSize < $numRecordsToProcess);
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
	
	public function getBreakColumn($column)
	{
		return $this->breakColumn;
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