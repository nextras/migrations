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
	private $conn;


	public function __construct(Connection $connection)
	{
		$this->conn = $connection;
		$this->conn->connect();
	}


	public function query($sql)
	{
		return array_map(
			function (Row $row) { return $row->toArray(); },
			iterator_to_array($this->conn->query('%raw', $sql))
		);
	}


	public function exec($sql)
	{
		$this->conn->query('%raw', $sql);
		return $this->conn->getAffectedRows();
	}


	public function escapeString($value)
	{
		return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_STRING);
	}


	public function escapeInt($value)
	{
		return (int) $value;
	}


	public function escapeBool($value)
	{
		return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_BOOL);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_DATETIME);
	}


	public function escapeIdentifier($value)
	{
		return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_IDENTIFIER);
	}

}
