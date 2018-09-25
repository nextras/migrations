<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\Dibi;

use DateTime;
use dibi;
use LogicException;
use Nextras\Migrations\IDbal;


class DibiAdapter implements IDbal
{
	/** @var IDbal */
	private $innerAdapter;


	public function __construct($conn)
	{
		if (version_compare(dibi::VERSION, '3.0.0', '>=')) {
			$this->innerAdapter = new Dibi3Adapter($conn);

		} elseif (version_compare(dibi::VERSION, '2.0.0', '>=')) {
			$this->innerAdapter = new Dibi2Adapter($conn);

		} else {
			throw new LogicException('Unsupported dibi version');
		}
	}


	public function query($sql)
	{
		return $this->innerAdapter->query($sql);
	}


	public function exec($sql)
	{
		return $this->innerAdapter->exec($sql);
	}


	public function escapeString($value)
	{
		return $this->innerAdapter->escapeString($value);
	}


	public function escapeInt($value)
	{
		return $this->innerAdapter->escapeInt($value);
	}


	public function escapeBool($value)
	{
		return $this->innerAdapter->escapeBool($value);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->innerAdapter->escapeDateTime($value);
	}


	public function escapeIdentifier($value)
	{
		return $this->innerAdapter->escapeIdentifier($value);
	}

}
