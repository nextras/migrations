<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\Dibi;

use DateTimeInterface;
use dibi;
use Dibi\Connection;
use LogicException;
use Nextras\Migrations\IDbal;


class DibiAdapter implements IDbal
{
	/** @var IDbal */
	private $innerAdapter;


	public function __construct(Connection $conn)
	{
		if (version_compare(dibi::VERSION, '3.0.0', '>=')) {
			$this->innerAdapter = new Dibi3Adapter($conn);

		} else {
			throw new LogicException('Unsupported dibi version');
		}
	}


	public function query(string $sql): array
	{
		return $this->innerAdapter->query($sql);
	}


	public function exec(string $sql): int
	{
		return $this->innerAdapter->exec($sql);
	}


	public function escapeString(string $value): string
	{
		return $this->innerAdapter->escapeString($value);
	}


	public function escapeInt(int $value): string
	{
		return $this->innerAdapter->escapeInt($value);
	}


	public function escapeBool(bool $value): string
	{
		return $this->innerAdapter->escapeBool($value);
	}


	public function escapeDateTime(DateTimeInterface $value): string
	{
		return $this->innerAdapter->escapeDateTime($value);
	}


	public function escapeIdentifier(string $value): string
	{
		return $this->innerAdapter->escapeIdentifier($value);
	}

}
