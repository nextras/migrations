<?php

return function() {
	$this->dbal->exec('DROP DATABASE IF EXISTS ' . $this->dbal->escapeIdentifier($this->dbName));
};
