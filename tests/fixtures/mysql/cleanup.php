<?php

return function() {
	$this->dbal->query('DROP DATABASE IF EXISTS ' . $this->dbal->escapeIdentifier($this->dbName));
};
