<?php

namespace Clickpdx\Salesforce;


interface IBatchable {


	public function start($context);
	
	public function execute($context, $scope);
	
	public function finish($context);



}