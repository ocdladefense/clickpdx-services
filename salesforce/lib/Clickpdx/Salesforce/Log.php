<?php





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
class Log {


	private $messages = array();
	
	
	public function addMessage($msg) {
		if(is_array($msg)) {
			$this->messages = array_merge($this->messages,$msg);
		}
		else $this->messages []= $msg;
	}
	
	
	
	public function getMessages() {
		return $this->messages;
	}
	
	
	
	public function __toString() {
		$str = "";
		foreach($this->messages as $msg) {
			$str .= ("<br />".$msg);
		}
		
		return $str;
	}

}