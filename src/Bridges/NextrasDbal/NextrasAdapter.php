<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NextrasDbal;

use DateTime;
use Nextras\Dbal\Connection;
use Nextras\Dbal\Drivers\IDriver;
use Nextras\Dbal\Result\Row;
use Nextras\Migrations\IDbal;


class NextrasAdapter implements IDbal
{
	/** @var Connection */
	private $connection;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->connection->connect();
	}


	public function query($sql)
	{
		$result = $this->connection->query('%raw', $sql);
		if ($result !== NULL) {
			return array_map(
				function (Row $row) { return $row->toArray(); },
				iterator_to_array($result)
			);
		}
	}


	public function escapeString($value)
	{
		return $this->connection->getDriver()->convertToSql($value, IDriver::TYPE_STRING);
	}


	public function escapeInt($value)
	{
		return (int) $value;
	}


	public function escapeBool($value)
	{
		return $this->connection->getDriver()->convertToSql($value, IDriver::TYPE_BOOL);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->connection->getDriver()->convertToSql($value, IDriver::TYPE_DATETIME);
	}


	public function escapeIdentifier($value)
	{
		return $this->connection->getDriver()->convertToSql($value, IDriver::TYPE_IDENTIFIER);
	}

}
