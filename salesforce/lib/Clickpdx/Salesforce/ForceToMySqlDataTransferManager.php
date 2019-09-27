<?php



namespace Clickpdx\Salesforce;

use Clickpdx\ResourceLoader;
use Clickpdx\Salesforce\RestApiAuthenticationException;
use Clickpdx\Salesforce\RestApiInvalidUrlException;

class ForceToMySqlDataTransferManager
{

	private $forceObjectName;
	
	private $soqlManager;
	
	private $mysqlTable;
	
	private $comments = array();
	
	private function prepareApiService()
	{
		$forceApi = ResourceLoader::getResource('forceApi');
		$forceApi->setAuthenticationService(ResourceLoader::getResource('sfOauth'));
		return $forceApi;
	}
	
	private function prepareApiServiceDebug()
	{
		$oauth = ResourceLoader::getResource('sfOauth');
		$forceApi = ResourceLoader::getResource('forceApi',true);
		print $oauth;
		$forceApi->setAuthenticationService($oauth);
		return $forceApi;
	}
	
	// throws a InvalidConnectionException
	// throws a SettingNotFoundException
	public function __construct(
		$forceObjectName,
		$conditionField=null,
		$conditionValue=null
	)
	{
		$this->forceObjectName = $forceObjectName;
		$soqlService = $this->prepareApiService();
		$settingPrefix = 'force.import.object.'.strtolower($forceObjectName);
		$this->mysqlTable = \setting($settingPrefix.'.mysqlTableName');
		$this->soqlManager = new SoqlBatchSelectQueryManager($soqlService);
		$this->soqlManager->setTable($forceObjectName);
		$this->soqlManager->setColumns(\setting($settingPrefix.'.fields'));
		$this->soqlManager->setBreakColumn(\setting($settingPrefix.'.breakField'));
		$this->soqlManager->setConditionField($conditionField);
		$this->soqlManager->setConditionValue($conditionValue);
		$this->soqlManager->setKey(\setting($settingPrefix.'.key'));
		$this->addComment('mysqlQuery',$this->soqlManager->toMysqlInsertQuery());
	}



	public function addComment($key,$data)
	{
		$this->comments[$key] = $data;
	}
	
	public function getComments()
	{
		return '<p style="width:600px;overflow:scroll;">'.implode('<br />',$this->comments).'</p>';
	}



	public function export($query)
	{
		if($query)
		{
			return $this->soqlManager->executeQuery($query);
		}
		return $this->soqlManager->execute();
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
		// print $this->soqlManager->toMysqlInsertQuery();
		$lockStmt = 'LOCK TABLES '.$this->mysqlTable.' WRITE';
		\get_connection()->exec($lockStmt);

		foreach($result as $record)
		{
			if(++$counter > \setting('force.import.maxInsertRecords',4000)) break;
			unset($record['attributes']);
			//print "<br />{$record['Id']}";
			\db_query($this->soqlManager->toMysqlInsertQuery(),$record,'pdo',false);
		}
		\get_connection()->exec('UNLOCK TABLES');
	}
}