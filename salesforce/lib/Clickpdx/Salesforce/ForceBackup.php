<?php

class ForceBackup implements Batchable {


    
    private static $IS_TEST = True;

    
    public ForceBackup(Integer theLimit){
        theQuery += ' LIMIT '+theLimit;
    }
    
    public ForceBackup(){}
    

 
		/*global Iterable<DupContactCollection>*/
		public function start(Database.BatchableContext bc) {
	    String contents = DuplicateContactInfo.loadStaticResourceAsString(TAB_STATIC_RESOURCE_NAME);
        String[] dupIds = contents.split('\n');
        
        return new CustomIterator(dupIds);
    }
    
    /*global void*/
    public function execute(Database.BatchableContext bc, List<DupContactCollection> scope){
        for(DupContactCollection coll : scope){
            try {
                if(IS_TEST && coll.hasPrimaryContact()){
                    coll.saveInfo();
                } else if(!IS_TEST && coll.hasPrimaryContact()){
                    coll.saveResult();
                    coll.doMerge();
                } // else throw new DupResolutionException('No primary contact found for '+coll.memberId);
            } catch(Exception e){
                coll.saveError(e);
            }
        }
        
        
			// $select = new ForceQuery($builder);
			/*
			foreach($force->execute($builder) as $results) {
			
			}


			Job::schedule($backup,500);
			
	
			// batch manager should execute sequential queries, processing the result of each batch through 
			// an executor
			// $this->addComment('mysqlQuery',$this->soqlManager->toMysqlInsertQuery());
			*/



			// Get the records from Salesforce.
			// $records = $manager->export();
			
			// Put the records into MySQL table.
			// $manager->import($records); 
    }
    
    

    
    
    
    /*global void*/
    public function finish(/* Database.BatchableContext*/ bc){
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
    


}