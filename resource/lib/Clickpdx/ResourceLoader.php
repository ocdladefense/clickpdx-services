<?php

namespace Clickpdx;


class ResourceLoader
{
	private static $loaders = array();
	
	private static $resources = array();
	
	public static function addLoader($type,$callable)
	{
		self::$loaders[$type]=$callable;
	}
	
	public static function getResource($rName)
	{
		$info = self::getResourceInfo($rName);
		$loader = self::$loaders[$info['type']];
		if(is_callable($loader))
		{
			return call_user_func($loader,$info);
		}
		else
		{
			throw new \Exception("No valid loader could be found for the resource {$rName}.");
		}
	}
	public static function initResources()
	{
		global $resources;
		self::$resources=$resources;
	}
	public static function getResourceInfo($rName)
	{		
		return self::$resources[$rName];
	}
	public static function getInfo()
	{
		return print_r(self::$loaders,true);
	}
}