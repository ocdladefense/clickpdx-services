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
	public function __construct($forceObjectName)
	{
		$this->forceObjectName = $forceObjectName;
		$soqlService = $this->prepareApiService();
		$settingPrefix = 'force.import.object.'.strtolower($forceObjectName);
		$this->mysqlTable = \setting($settingPrefix.'.mysqlTableName');
		$this->soqlManager = new SoqlBatchSelectQueryManager($soqlService);
		$this->soqlManager->setTable($forceObjectName);
		$this->soqlManager->setColumns(\setting($settingPrefix.'.fields'));
		$this->soqlManager->setBreakColumn(\setting($settingPrefix.'.breakField'));
	}

	public function export()
	{
		return $this->soqlManager->execute();
	}
	
	public function import(SfResult $result)
	{
		$counter = 0;
		\get_connection()->query('LOCK TABLES '.$this->mysqlTable.' WRITE');
		// $mysql = \db_query('LOCK TABLES '.$this->mysqlTable.' WRITE','pdo',true);

		foreach($result as $record)
		{
			// if(++$counter>10) break;
			unset($record['attributes']);
			//print "<br />{$record['Id']}";
			\db_query($this->soqlManager->toMysqlInsertQuery(),$record,'pdo',false);
		}

		\get_connection()->query('UNLOCK TABLES');
		
	}
}