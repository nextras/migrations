<?php declare(strict_types = 1);

return function() {
	$schema = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->exec("DROP SCHEMA IF EXISTS {$schema} CASCADE");
};
