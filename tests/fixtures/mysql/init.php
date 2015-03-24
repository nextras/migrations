<?php

return function () {
	$dbName = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->exec('CREATE DATABASE ' . $dbName);
	$this->dbal->exec('USE ' . $dbName);
};
