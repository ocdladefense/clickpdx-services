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
 
class SoqlBatchSelectQueryManager // extends SalesforceRestApiService
{

	const MAX_NUM_BATCHES = INF;
	
	
	const FEATURE_NOT_READY = false;
	
	
	const MAX_BATCH_SIZE = 1000;
	
	
	const FIRST_BATCH_ID = 1;
	
	
	// Reference to the query builder.
	private $query;

	private $api;
	
	private $batchSize;
	
	
	
	
	public function __construct($api,$query,$batchSize){
		$this->api = $api;
		$this->query = $query;
		$this->batchSize = $batchSize;
	}


	public function export()
	{
	
		$results = new SfResult();
		
		// Once incremented the current batch will start at 1
		//	+ i.e., the first batch.
		$curBatch = 0;
		

		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		// Initially the delimiter id should be the Integer, 0
		$lastId = 0;
		
		
		$builder = $this->query;


		
		$countQuery = $builder->getCountQuery()->compile();
		print "Count Query is: {$countQuery}<br />";
		

		$count = $this->api->executeQuery($countQuery)->aggregateCount();
		print "Number of records to process is: {$count}<br />";
		
		
		if($count == 0) return $results;

		
		$bexp = $count / $this->batchSize;
		
		print "<br />Expected number of batches is: ".$bexp;
		print "<br />Total number of records that should be processed: ".$count;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.

		do
		{


			if(++$curBatch != 1)
			{
				print $builder->getBreakColumn()."<br />";
				$builder->condition($builder->getBreakColumn(),
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN,'delimiter');
			}
			
			$queries[] = $q = $builder->compile();
			
			print "<br />Query: {$q}<br />";
			
			$results->add($res = $this->api->executeQuery($q));
			
			print "<br />Found ".$res->count() . " records when delimiter id is: {$lastId}.";
			
			if($res->hasError())
			{
				throw new \Exception($res->getErrorMsg());
			}
			if(false && !$res->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.  Query was: {$q}.");
			}
			$running += $res->count();
			
			$lastId = $res->getLast()[$builder->getBreakColumn()];

		} while($running < $count);



		$results->addComment($queries,'queries');
		
		return $results;
	}






}