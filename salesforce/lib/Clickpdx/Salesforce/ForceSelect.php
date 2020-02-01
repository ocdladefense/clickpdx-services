<?php

namespace Clickpdx\Salesforce;



use Clickpdx\SalesforceRestApiService;




/**
 * Soql Batch Query Manager
 *
 * Make a connection to Salesforce and query records for
 *  the given query.
 *
 * Loop through the records in batches of 1000 (as of 1/30/2020.)
 */
 
class ForceSelect implements \Iterator // extends SalesforceRestApiService // implements IteratorAggregator
{

	const MAX_NUM_BATCHES = INF;
	
	
	const FEATURE_NOT_READY = false;
	
	
	const MAX_BATCH_SIZE = 1000;
	
	
	const FIRST_BATCH_ID = 1;
	
	
	// Reference to the query builder.
	private $query;


	private $api;

	
	private $batchSize;


	private $totalRecords;
	
		
	private $numBatches;
	
	// Start at first batch,
	//  advance through batches with each iteration.
	private $curBatch = 0;
	
	
	private $running = 0;
	
	
	private $lastId = 0;
	
	
	private $logs = array();
	
	
	private function log($msg) {
		$this->logs []= $msg;
	}
	
	
	public function getLog() {
		return $this->logs;
	}
	
	
	public function __construct($api,$query,$batchSize){
		$this->api = $api;
		$this->query = $query;
		$this->batchSize = $batchSize;
		
		$this->countQuery = $query->getCountQuery()->compile();
		$this->log("Count Query is: {$this->countQuery}");
		

		$this->totalRecords = $this->api->executeQuery($this->countQuery)->aggregateCount();
		$this->log("Number of records to process is: {$this->totalRecords}");
		
		$this->numBatches = $this->totalRecords / $this->batchSize;
		$this->log("Number of expected batches: {$this->numBatches}");
	}

	

	
	public function next() {
		return $this->curBatch++;
	}
	
	public function rewind() {
		$this->curBatch = 0;
	}
	
	public function valid() {
		return $this->key() < $this->numBatches;
	}
	
	public function key() {
		return $this->curBatch;
	}



	
	/*
	 * Make each batch accessible via PHP's iterable interface.
	 *  This way we can loop through the 
	 */
	public function current() {
		$this->log("Starting batch ".($this->curBatch +1));
		return $this->export();
	}
	


	/*
	 * @method export
	 *
	 * Return the results of one "batch" of records retrieved from Salesforce.
	 *
	 */
	public function export()
	{
		$this->log("Executing batch ".$this->curBatch+1);
		$results = new SfResult();
		
		
		// A list of queries to be displayed on the template.
		$queries = array();

		
		$builder = $this->query;

		// If there are no records then return an empty result object.
		if($this->totalRecords == 0) return $results;		
		


		if($this->curBatch != 0)
		{
			$builder->condition($builder->getBreakColumn(),
				$this->lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
		}
		
		$queries[] = $q = $builder->compile();
		
		
		$this->log("Query: {$q}");
		
		$results->add($res = $this->api->executeQuery($q));
		
		$this->log("Found ".$res->count() . " records when delimiter id is: {$this->lastId}.");
		
		if($res->hasError())
		{
			throw new \Exception($res->getErrorMsg());
		}
		if(false && !$res->count()>0)
		{
			throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$this->curBatch}.  Query was: {$q}.");
		}
		$this->running += $res->count();
		
		$this->lastId = $res->getLast()[$builder->getBreakColumn()];


		$results->addComment($queries,'queries');
		
		return $results;
	}






}