<?php declare(strict_types = 1);

return fn() => $this->dbal->exec('DROP DATABASE IF EXISTS ' . $this->dbal->escapeIdentifier($this->dbName));
