<?php


use Clickpdx\Salesforce\IBatchable;
use Clickpdx\Salesforce\BatchContext;

namespace Clickpdx\Salesforce;


/**
 * @class BatchJob
 *
 *  Iterate through collections of records.  BatchJob iterates through collections of 
 *   records. Each collection consists of at most <$scope> number of records.
 *   Each collection is then passed to the execute() method of the related batch object.
 *
 *  Examples of Batches include querying API records from Salesforce.  Those queries are generally 
 *  limited to, for example, 1000 records and need to be processed in batches.
 */
class BatchJob {


	private $context;

	public function __construct(IBatchable $job, $scope) {
		$this->context = new BatchContext($scope);

		$batches = $job->start($this->context);
		
		foreach($batches as $coll) {
			$job->execute($this->context,$coll);
		}
		
		$this->context->addMessage($batches->getLog());
		
		$job->finish($this->context);

	}
	
	public function getJobInfo() {
		return $this->context;
	}

}