<?php


use Clickpdx\Salesforce\IBatchable;
use Clickpdx\Salesforce\ForceSelect;
use Clickpdx\Salesforce\MysqlImporter;

namespace Clickpdx\Salesforce;





class ForceBackup implements IBatchable {


    
    private static $IS_TEST = True;

    

    
    private $api;
    
    private $soql;
    
    private $batchSize;


 		


    public function __construct(){}
 		
 		public function setApi($api) {
 			$this->api = $api;
 		}
 		
 		public function setSoql($soql) {
 			$this->soql = $soql;
 		}
 		
		/*global Iterable<DupContactCollection>*/
		// public function start(Database.BatchableContext bc) {
		public function start($context) {

			$select = new ForceSelect($this->api,$this->soql,$context->getBatchSize());

			$context->addMessage($select->getLog());

			return $select;
			
			// return $select;
			// print "<pre>".print_r($records[0],true)."</pre>";
			// print "<pre>".print_r($records[count($records)-1],true)."</pre>";
		
			// print $mysql->getQueries($records);
    }
    
    
    
    /*global void*/
    // public function execute(Database.BatchableContext bc, List<DupContactCollection> scope){
    public function execute($context, $coll) {
    
			$mysql = new MysqlImporter($this->soql);
			
			$mysql->import($coll);

			// batch manager should execute sequential queries, processing the result of each batch through 
			// an executor
			// $this->addComment('mysqlQuery',$this->soqlManager->toMysqlInsertQuery());


    }
    
    

    
    public function finish($bc){
    
    }
    
    /*global void
    public function finish( $bc){
        // Get the ID of the AsyncApexJob representing this batch job
        // from Database.BatchableContext.
        // Query the AsyncApexJob object to retrieve the current job's information.
        AsyncApexJob a = [SELECT Id, Status, NumberOfErrors, JobItemsProcessed,
                          TotalJobItems, CreatedBy.Email
                          FROM AsyncApexJob WHERE Id =
                          :bc.getJobId()];
        
        // Send an email to the Apex job's submitter notifying of job completion.
        Messaging.SingleEmailMessage mail = new Messaging.SingleEmailMessage();
        String[] toAddresses = new String[] {a.CreatedBy.Email};
        
        mail.setToAddresses(toAddresses);
        mail.setSubject('Apex Sharing Recalculation ' + a.Status);
        mail.setPlainTextBody
            ('The batch Apex job processed ' + a.TotalJobItems +
             ' batches with '+ a.NumberOfErrors + ' failures.');
        
        Messaging.sendEmail(new Messaging.SingleEmailMessage[] { mail });
    }
    */
    


}