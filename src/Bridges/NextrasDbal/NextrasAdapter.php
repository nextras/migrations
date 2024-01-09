<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NextrasDbal;

use DateTimeInterface;
use Nextras\Dbal\Connection;
use Nextras\Dbal\Drivers\IDriver;
use Nextras\Dbal\Result\Row;
use Nextras\Migrations\IDbal;


class NextrasAdapter implements IDbal
{
	/** @var Connection */
	private $conn;

	/** @var int */
	private $version;


	public function __construct(Connection $connection)
	{
		$this->conn = $connection;
		$this->conn->connect();

		if (method_exists($connection->getDriver(), 'convertToSql')) {
			$this->version = 1;

		} elseif (method_exists($connection->getDriver(), 'convertBoolToSql')) {
			$this->version = 2;

		} else {
			$this->version = 5;
		}
	}


	public function query(string $sql): array
	{
		return array_map(
			function (Row $row) { return $row->toArray(); },
			iterator_to_array($this->conn->query('%raw', $sql))
		);
	}


	public function exec(string $sql): int
	{
		$this->conn->query('%raw', $sql);
		return $this->conn->getAffectedRows();
	}


	public function escapeString(string $value): string
	{
		if ($this->version >= 2) {
			return $this->conn->getDriver()->convertStringToSql($value);
		} else {
			return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_STRING);
		}
	}


	public function escapeInt(int $value): string
	{
		return (string) (int) $value;
	}


	public function escapeBool(bool $value): string
	{
		if ($this->version >= 5) {
			return $this->conn->getPlatform()->formatBool($value);
		} elseif ($this->version >= 2) {
			return $this->conn->getDriver()->convertBoolToSql($value);
		} else {
			return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_BOOL);
		}
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		if ($this->version >= 5) {
			return $this->conn->getPlatform()->formatDateTime($value);
		} elseif ($this->version >= 2) {
			return $this->conn->getDriver()->convertDateTimeToSql($value);
		} else {
			return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_DATETIME);
		}
	}


	public function escapeIdentifier(string $value): string
	{
		if ($this->version >= 5) {
			return $this->conn->getPlatform()->formatIdentifier($value);
		} elseif ($this->version >= 2) {
			return $this->conn->getDriver()->convertIdentifierToSql($value);
		} else {
			return $this->conn->getDriver()->convertToSql($value, IDriver::TYPE_IDENTIFIER);
		}
	}
}
