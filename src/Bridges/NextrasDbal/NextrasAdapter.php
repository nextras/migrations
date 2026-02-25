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
use Nextras\Dbal\Result\Row;
use Nextras\Migrations\IDbal;


class NextrasAdapter implements IDbal
{
	private int $version;


	public function __construct(
		private Connection $conn,
	)
	{
		$this->conn->connect();

		$this->version = method_exists($conn->getDriver(), 'convertBoolToSql') ? 2 : 5;
	}


	public function query(string $sql): array
	{
		return array_map(
			fn(Row $row) => $row->toArray(),
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
		return $this->conn->getDriver()->convertStringToSql($value);
	}


	public function escapeInt(int $value): string
	{
		return (string) (int) $value;
	}


	public function escapeBool(bool $value): string
	{
		return match (true) {
			$this->version >= 5 => $this->conn->getPlatform()->formatBool($value),
			default => $this->conn->getDriver()->convertBoolToSql($value),
		};
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		return match (true) {
			$this->version >= 5 => $this->conn->getPlatform()->formatDateTime($value),
			default => $this->conn->getDriver()->convertDateTimeToSql($value),
		};
	}


	public function escapeIdentifier(string $value): string
	{
		return match (true) {
			$this->version >= 5 => $this->conn->getPlatform()->formatIdentifier($value),
			default => $this->conn->getDriver()->convertIdentifierToSql($value),
		};
	}
}
