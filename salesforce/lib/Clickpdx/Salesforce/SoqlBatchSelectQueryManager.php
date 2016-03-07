<?php

namespace Clickpdx\Salesforce;

class SoqlBatchSelectQueryManager
{
	
	private $breakColumn;
	
	private $columns;
	
	private $table;

	private $batchSize = 2000;
	
	private $soqlService;
	
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
	
	public function execute()
	{
		$curBatch = 1;
		
		// A list of queries to be displayed on the template.
		$queries = array();
	
		
		$query = new SoqlQueryBuilder();
		$query->table($this->table);
		$query->cols($this->columns);
		$query->orderBy($this->breakColumn);
		$query->limit($this->batchSize);
		// print $query;
	
		$numRecordsToProcess = $this->soqlService->executeQuery($query->getCountQuery()->compile())->count();
		$expectedNumBatches = $numRecordsToProcess/$batchSize;
		// print $countQuery;
	
		// Instantiate an empty result set.
		//	+ We'll use this as a container for SOQL Contact results,
		//	+ adding records to it, as necessary.
		$results = new SfResult();
		do
		{
			// print "<br />Starting batch {$curBatch}...";
			if($curBatch != 1)
			{
				$query->where('Ocdla_Auto_Number_Int__c',
					$lastId,SoqlQueryBuilder::QUERY_OP_GREATER_THAN);
			}
			$queries[] = $q = $query->compile();
			$results->add($sfResult = $this->soqlService->executeQuery($q));
			if(!$sfResult->count()>0)
			{
				throw new \Exception("Expected to complete {$expectedNumBatches} batches but only completed {$curBatch}.");
			}
			$lastId = $sfResult->getLast()['Ocdla_Auto_Number_Int__c'];
			// print "<br />Testing condition: ".$curBatch++*$this->batchSize ." < {$numRecordsToProcess}...";
		}
		while($curBatch++*$this->batchSize < $numRecordsToProcess);
		$results->addComment($queries,'queries');
		return $results;
	}

	public function toMysqlInsertQuery()
	{
		return 'INSERT IGNORE INTO force_contact(LastModifiedDate,Ocdla_Auto_Number_Int__c,Id,AccountId,Title,FirstName,LastName,MailingStreet,MailingCity,MailingState,MailingPostalCode,OrderApi__Work_Phone__c,OrderApi__Work_Email__c,Fax) VALUES(:LastModifiedDate,:Ocdla_Auto_Number_Int__c,:Id,:AccountId,:Title,:FirstName,:LastName,:MailingStreet,:MailingCity,:MailingState,:MailingPostalCode,:OrderApi__Work_Phone__c,:OrderApi__Work_Email__c,:Fax)';
	}


}