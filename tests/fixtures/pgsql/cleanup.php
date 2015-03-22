<?php

return function() {
	$schema = $this->dbal->escapeIdentifier($this->dbName);
	$this->dbal->query("DROP SCHEMA IF EXISTS {$schema} CASCADE");
};
