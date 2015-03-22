<?php

return function () {
	$dbName = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->query('CREATE DATABASE ' . $dbName);
	$this->dbal->query('USE ' . $dbName);
};
