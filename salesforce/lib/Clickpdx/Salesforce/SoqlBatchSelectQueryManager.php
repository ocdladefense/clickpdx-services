<?php

namespace Clickpdx\Salesforce;

class SoqlBatchSelectQueryManager
{
	const MAX_NUM_BATCHES = INF;
	
	const FEATURE_NOT_READY = false;
	
	const MAX_BATCH_SIZE = 100;
	
	const FIRST_BATCH_ID = 1;
	
	private $breakColumn;
	
	private $columns;
	
	private $table;
	
	private $soqlService;
	
	private $conditionField;
	
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
	
	public function setConditionValue($value=null)
	{
		if(!empty($value))
		{
			$this->conditionValue = $value;
		}
	}
	
	public function setConditionField($fieldName)
	{
		if(!empty($fieldName))
		{
			$this->conditionField = $fieldName;
		}
	}

	public function executeQuery($query)
	{
		// Once incremented the current batch will start at 1
		//	+ i.e., the first batch.
		$curBatch = 0;
		
		// Max number of records to include in a batch.
		$batchSize = \setting('force.import.maxBatchSize',self::MAX_BATCH_SIZE);
		
		// How many batches should be processed?
		$maxBatches = \setting('force.import.maxBatches', self::MAX_NUM_BATCHES);
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		// Initially the delimiter id should be the Integer, 0
		$lastId = 0;
		
		// $query = new SoqlQueryBuilder();
		// $query->table($this->table);
		// $query->cols($this->columns);
		// $query->orderBy($this->breakColumn);
		// $query->limit(self::MAX_BATCH_SIZE);
		if(self::FEATURE_NOT_READY && !empty($this->conditionField))
		{
			// Test if this is a date or not
			// Basically test for the field type
			$query->dateCondition($this->conditionField,
					$this->conditionValue,
					SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
					
			// $query->where('Ocdla_Interaction_Line_Item_ID__c = null');
		}
		$this->soqlService->setEndpoint('query');
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		$numRecordsToProcess = $this->soqlService->executeQuery($query)->count();
		$expectedNumBatches = $numRecordsToProcess / $batchSize;
		
		print "Total number of records that should be processed: ".$numRecordsToProcess;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.
		$results = new SfResult();
		$runningCount = 0;
		do
		{
			// print "<br />Starting batch {$curBatch}...";
			if(++$curBatch != self::FIRST_BATCH_ID)
			{
				//$query->condition('Ocdla_Auto_Number_Int__c',
					//$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
				$query->condition($this->getBreakColumn(),
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
			}
			
			$queries[] = $q = $query;//@jbernal->compile();
			
			$results->add($sfResult = $this->soqlService->executeQuery($query));
			
			print "<br />Found ".$sfResult->count() . " records when delimiter id is: {$lastId}.";
			
			if($sfResult->hasError())
			{
				throw new \Exception($sfResult->getErrorMsg());
			}
			if(false && !$sfResult->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.  Query was: {$q}.");
			}
			
			$lastId = $sfResult->getLast()[$this->getBreakColumn()];
			$runningCount += $sfResult->count();

		} while($runningCount < $numRecordsToProcess);
		
		// while($curBatch*$batchSize < $numRecordsToProcess);
		
		
		$results->addComment($queries,'queries');
		
		return $results;
	}




	public function execute()
	{
		$results = new SfResult();
		
		// Once incremented the current batch will start at 1
		//	+ i.e., the first batch.
		$curBatch = 0;
		
		// Max number of records to include in a batch.
		$batchSize = \setting('force.import.maxBatchSize',self::MAX_BATCH_SIZE);
		
		// How many batches should be processed?
		$maxBatches = \setting('force.import.maxBatches', self::MAX_NUM_BATCHES);
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		// Initially the delimiter id should be the Integer, 0
		$lastId = 0;
		

		
		$builder = new SoqlQueryBuilder();
		$builder->table($this->table);
		$builder->cols($this->columns);
		$builder->orderBy($this->breakColumn);
		$builder->limit($batchSize);
		
		if(!empty($this->conditionField))
		{
			// Test if this is a date or not
			// Basically test for the field type
			print "Condition field is not empty.<br />";
			$builder->dateCondition($this->conditionField,
					$this->conditionValue,
					SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
					
			// $builder->where('Ocdla_Interaction_Line_Item_ID__c = null');
		}
		
		
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		
		$countQuery = $builder->getCountQuery()->compile();
		print "Count Query is: {$countQuery}<br />";
		

		$numRecordsToProcess = $this->soqlService->executeQuery($countQuery)->count();

		
		
		if($numRecordsToProcess == 0) return $results;

		
		$expectedNumBatches = $numRecordsToProcess / $batchSize;
		
		print "<br />Expected number of batches is: ".$expectedNumBatches;
		print "<br />Total number of records that should be processed: ".$numRecordsToProcess;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.

		do
		{
			++$curBatch;
			if($curBatch > $maxBatches)
			{
				throw new \Exception('Max number of export batches exceeded: batch '.$curBatch);
			}

			if($curBatch != 1)
			{
				$builder->condition($this->getBreakColumn(),
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
			}
			
			$queries[] = $q = $builder->compile();
			$results->add($res = $this->soqlService->executeQuery($q));
			
			print "<br />Found ".$res->count() . " records when delimiter id is: {$lastId}.";
			
			if($res->hasError())
			{
				throw new \Exception($res->getErrorMsg());
			}
			if(false && !$res->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.  Query was: {$q}.");
			}
			$runningCount += $res->count();
			
			$lastId = $res->getLast()[$this->getBreakColumn()];

		} while($runningCount < $numRecordsToProcess);



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