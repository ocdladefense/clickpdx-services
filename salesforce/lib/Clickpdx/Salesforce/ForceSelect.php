<?php

namespace Clickpdx\Salesforce;

class ForceSelect implements IteratorAggregator
{
	const MAX_NUM_BATCHES = INF;
	
	const FEATURE_NOT_READY = false;
	
	const MAX_BATCH_SIZE = 100;
	
	private $breakColumn;
	
	private $columns;
	
	private $table;
	
	private $soqlService;
	
	private $conditionField = 'LastModifiedDate';
	
	private $conditionValue;
	
	private $keys = array();
	
	const MySQLTablePrefix = 'force';
	
	
	
	
	
	public function __construct($builder) {
		$this->builder = $builder;
	}


	// public function executeQuery($query)




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
		

		

		
		
		if(!$this->soqlService->hasInstanceUrl())
		{
			$this->soqlService->authorize();
		}
		
		
		$count = $this->soqlService->executeQuery($this->builder->getCountQuery()->compile())->count();
		
		if($count === 0) return $results;
		
		
		

		
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
				break;//throw new \Exception('Match number of export batches exceeded: batch '.$curBatch);
			}
			// print "<br />Starting batch {$curBatch}...";
			if($curBatch != 1)
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

		} while($curBatch*$batchSize < $numRecordsToProcess);
		
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


}