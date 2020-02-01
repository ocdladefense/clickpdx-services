<?php



use Clickpdx\Salesforce\Log;

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
class BatchContext {


	private $log;
	
	private $scope;
	
	
	
	
	public function __construct($scope) {
		$this->log = new Log();
		$this->scope= $scope;
	}
	
	
	public function getBatchSize() {
		return $this->scope;
	}
	
	
	public function addMessage($msg) {
		$this->log->addMessage($msg);
	}
	
	
	public function getLog() {
		return $this->log;
	}

}