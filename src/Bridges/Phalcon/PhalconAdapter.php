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

    /**
     * @param Pdo $connection
     */
	public function __construct(Pdo $connection)
	{
		$this->conn = $connection;
		$this->conn->connect();
	}

    /**
     * @param  string $sql
     * @return array list of rows represented by assoc. arrays
     */
	public function query($sql)
	{
	    return $this->conn->fetchAll($sql);
	}

    /**
     * @param  string $sql
     * @return int number of affected rows
     */
	public function exec($sql)
	{
	    $this->conn->execute($sql);
	    return $this->conn->affectedRows();
	}


    /**
     * @param  string $value
     * @return string escaped string wrapped in quotes
     */
    public function escapeString($value)
	{
	    return $this->conn->escapeString($value);
	}

    /**
     * @param  int $value
     * @return string
     */
	public function escapeInt($value)
	{
		return (string)(int)$value;
	}


    /**
     * @param  bool $value
     * @return string
     * @throws ExecutionException
     */
	public function escapeBool($value)
	{
        if ($this->conn->getType() === self::TYPE_MYSQL) {
            return (string)(int)$value;
        }

        if ($this->conn->getType() === self::TYPE_PGSQL) {
            return (bool)$value ? 'TRUE' : 'FALSE';
        }

        throw new ExecutionException('Unsupported type of \Phalcon\Db\Adapter\Pdo driver.');
	}

    /**
     * @param  DateTime $value
     * @return string
     * @throws ExecutionException
     */
	public function escapeDateTime(DateTime $value)
	{
        return $this->conn->escapeString($this->formatDateTime($value));
	}

    /**
     * @param  string $value
     * @return string
     * @throws ExecutionException
     */
	public function escapeIdentifier($value)
	{
        if ($this->conn->getType() === self::TYPE_MYSQL) {
            return '`' . $value . '`';
        }

        if ($this->conn->getType() === self::TYPE_PGSQL) {
            return '"' . $value . '"';
        }

        throw new ExecutionException('Unsupported type of \Phalcon\Db\Adapter\Pdo driver.');
	}

    /**
     * @param DateTime $value
     * @return string
     * @throws ExecutionException
     */
    private function formatDateTime(DateTime $value)
    {
        if (in_array($this->conn->getType(), [self::TYPE_MYSQL, self::TYPE_PGSQL], true)) {
            return $value->format('Y-m-d H:i:s');
        }

        throw new ExecutionException('Unsupported type of \Phalcon\Db\Adapter\Pdo driver.');
	}
}
