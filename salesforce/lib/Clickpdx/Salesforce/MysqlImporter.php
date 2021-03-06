<?php


  
namespace Clickpdx\Salesforce;

// use Clickpdx\ResourceLoader;
// use Clickpdx\Salesforce\RestApiAuthenticationException;
// use Clickpdx\Salesforce\RestApiInvalidUrlException;



class MysqlImporter
{

	const MAX_IMPORT_RECORDS = 20000;
	
	
	private $table;

	private $query;
	
	private $columns = array();
	
	private $keys = array();



	// throws a InvalidConnectionException
	// throws a SettingNotFoundException
	public function __construct($query)
	{
		$this->query = $query;
		
		$this->table = "force_".strtolower($query->getTable());
		
		$this->columns = $query->getColumnList();
	}






	/**
	 * 
	 * See:
	 * http://docs.doctrine-project.org/projects/
	 *	+ doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html
	 * 	+ for more information
	 */
	public function import(SfResult $result)
	{
		$counter = 0;

		$lockStmt = 'LOCK TABLES '.$this->table.' WRITE';

		\get_connection()->exec($lockStmt);

		foreach($result as $record)
		{
			if(++$counter > self::MAX_IMPORT_RECORDS) break;
			unset($record['attributes']);

			\db_query($this->toMysqlInsertQuery(),$record,'pdo',false);
		}
		
		\get_connection()->exec('UNLOCK TABLES');
	}



	public function getQueries(SfResult $result) {
		$counter = 0;
		
		foreach($result as $record)
		{
			if(++$counter == 5) break;
			print "<p>{$this->toMysqlInsertQuery()}</p>";
		}
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
		$clean = array_diff($this->columns,$this->keys);

		$ret = array_map(function($colName){
			return $colName . '=VALUES('.$colName.')';
		},$clean);
		
		return implode(',',$ret);
	}



	public function toMysqlInsertQuery()
	{
		return 'INSERT INTO ' . $this->table .'(' .$this->getInsertPart() .')' .' VALUES('.$this->getValueBindings().') ON DUPLICATE KEY UPDATE '. $this->getUpdatePart();
	}
	
	
	
	public function __toString(){
		return implode("<br />", array(
			"Table" => $this->table,
			"Columns" => $this->getInsertPart()
		));
	}
	
	
}