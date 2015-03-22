Migrations
==========

[![Build Status](https://travis-ci.org/nextras/migrations.svg?branch=master)](https://travis-ci.org/nextras/migrations)
[![Downloads this Month](https://img.shields.io/packagist/dm/nextras/migrations.svg?style=flat)](https://packagist.org/packages/nextras/migrations)
[![Stable version](http://img.shields.io/packagist/v/nextras/migrations.svg?style=flat)](https://packagist.org/packages/nextras/migrations)

Migrations for Nette Framework Database (and others db layers).

Forked [Clevis\Migrations](https://github.com/clevis/migration), enhanced and updated for Nette Database.

Implemented drivers for:
- Nette Database **MySQL**
- Nette Database **PostgreSQL**

Installation
------------
Add to your composer.json:

```
"require-dev": {
	"nextras/migrations": "@dev"
}
```

Workflow
--------
```php
// prepare driver
$connection = new Nette\Database\Connection('mysql:dbname=testdb', 'root', 'root');
$dbal = new Nextras\Migrations\Dbal\NetteAdapter($connection);

$driver = new Nextras\Migrations\Drivers\MySqlDriver($dbal, 'migrations');
// PostgreSQL driver with optional schema
$driver = new Nextras\Migrations\Drivers\PgSqlDriver($dbal, 'migrations', 'customSchema');

// create controller
// choose http or cli controller
$controller = new Nextras\Migrations\Controllers\HttpController($driver);

// add groups of migration files & their dependencies on other groups
$controller->addGroup('structures', __DIR__ . '/structures');
$controller->addGroup('data', __DIR__ . '/data', ['structures']);

// add supported extension
$controller->addExtension('sql', new Nextras\Migrations\Extensions\NetteDbSql($context));

// run controller
$controller->run();
```
