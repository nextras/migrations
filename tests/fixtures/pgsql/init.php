<?php

return function () {
	$schema = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->query('CREATE SCHEMA ' . $schema);
	$this->dbal->query('SET search_path = ' . $schema);
};
