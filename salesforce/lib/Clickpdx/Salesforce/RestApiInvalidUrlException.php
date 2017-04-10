<?php

namespace Clickpdx\Salesforce;


class RestApiInvalidUrlException extends \Exception
{
	public function __construct($msg)
	{
		parent::__construct($msg);
	}
}