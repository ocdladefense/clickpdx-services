<?php

namespace Clickpdx\Salesforce;


class RestApiAuthenticationException extends \Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);
	}
}