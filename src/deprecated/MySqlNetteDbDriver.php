<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nette;
use Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter;


/**
 * @deprecated
 */
class MySqlNetteDbDriver extends MySqlDriver
{
	public function __construct(Nette\Database\Context $context, $tableName)
	{
		trigger_error(sprintf('Class %s is deprecated, use class MySqlDriver instead.', __CLASS__), E_USER_DEPRECATED);
		parent::__construct(new NetteAdapter($context->getConnection()), $tableName);
	}

}
