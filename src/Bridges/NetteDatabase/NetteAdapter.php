<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NetteDatabase;

use DateTimeInterface;
use Nette;
use Nextras\Migrations\IDbal;
use PDO;


class NetteAdapter implements IDbal
{
	public function __construct(
		private Nette\Database\Connection $conn,
	)
	{
	}


	public function query(string $sql): array
	{
		return array_map(
			fn($row) => (array) $row,
			$this->conn->fetchAll($sql)
		);
	}


	public function exec(string $sql): int
	{
		return $this->conn->query($sql)->getRowCount();
	}


	public function escapeString(string $value): string
	{
		return $this->conn->quote($value, PDO::PARAM_STR);
	}


	public function escapeInt(int $value): string
	{
		return $this->conn->quote((string) $value, PDO::PARAM_INT);
	}


	public function escapeBool(bool $value): string
	{
		return $this->escapeString((string) (int) $value);
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		return $this->conn->getSupplementalDriver()->formatDateTime($value);
	}


	public function escapeIdentifier(string $value): string
	{
		return $this->conn->getSupplementalDriver()->delimite($value);
	}
}
