<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NetteDatabase;

use DateTime;
use Nette\Database\Connection;
use Nette\Database\IRow;
use Nextras\Migrations\IDbal;
use PDO;


class NetteAdapter implements IDbal
{
	/** @var Connection */
	private $ndb;


	public function __construct(Connection $ndb)
	{
		$this->ndb = $ndb;
	}


	public function query($sql)
	{
		$result = $this->ndb->query($sql);
		if ($result->getColumnCount() > 0) {
			return array_map(
				function (IRow $row) { return (array) $row; },
				$result->fetchAll()
			);
		}
	}


	public function escapeString($value)
	{
		return $this->ndb->quote($value, PDO::PARAM_STR);
	}


	public function escapeInt($value)
	{
		return $this->ndb->quote($value, PDO::PARAM_INT);
	}


	public function escapeBool($value)
	{
		return $this->ndb->getSupplementalDriver()->formatBool($value);
	}


	public function escapeDateTime(DateTime $value)
	{
		return $this->ndb->getSupplementalDriver()->formatDateTime($value);
	}


	public function escapeIdentifier($value)
	{
		return $this->ndb->getSupplementalDriver()->delimite($value);
	}

}
