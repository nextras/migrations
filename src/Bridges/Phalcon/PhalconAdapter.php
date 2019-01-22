<?php

namespace Nextras\Migrations\Bridges\Phalcon;

use DateTime;
use Nextras\Migrations\ExecutionException;
use Nextras\Migrations\IDbal;
use Phalcon\Db\Adapter\Pdo;


class PhalconAdapter implements IDbal
{
    /** @var string Value from \Phalcon\Db\Adapter\Pdo\Mysql::$_type */
    const TYPE_MYSQL = 'mysql';

    /** @var string Value from \Phalcon\Db\Adapter\Pdo\Postgresql::$_type */
    const TYPE_PGSQL = 'pgsql';

	/** @var Pdo */
	private $conn;


	public function __construct(Pdo $connection)
	{
		$this->conn = $connection;
		$this->conn->connect();
	}


	public function query($sql)
	{
	    return $this->conn->fetchAll($sql);
	}


	public function exec($sql)
	{
	    $this->conn->execute($sql);
	    $this->conn->affectedRows();
	}


	public function escapeString($value)
	{
	    return $this->conn->escapeString($value);
	}


	public function escapeInt($value)
	{
		return (string)(int)$value;
	}


	public function escapeBool($value)
	{
        return (string)(int)$value;
	}


	public function escapeDateTime(DateTime $value)
	{
        return $this->conn->escapeString($this->formatDateTime($value));
	}


	public function escapeIdentifier($value)
	{
	    return $this->conn->escapeIdentifier($value);
	}

    private function formatDateTime(DateTime $value)
    {
        if (in_array($this->conn->getType(), [self::TYPE_MYSQL, self::TYPE_PGSQL], true)) {
            return $value->format('Y-m-d H:i:s');
        } else {
            throw new ExecutionException('Unsupported type of \Phalcon\Db\Adapter\Pdo driver.');
        }
	}
}
