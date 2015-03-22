<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Drivers;

use Nette;
use Nextras\Migrations\Dbal\NetteAdapter;


/**
 * @deprecated
 */
class PgSqlNetteDbDriver extends PgSqlDriver
{
	public function __construct(Nette\Database\Context $context, $tableName, $schema = 'public')
	{
		parent::__construct(new NetteAdapter($context->getConnection()), $tableName, $schema);
	}
}
