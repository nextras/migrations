<?php

return function () {
	$schema = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->exec('CREATE SCHEMA ' . $schema);
	$this->dbal->exec('SET search_path = ' . $schema);
};
