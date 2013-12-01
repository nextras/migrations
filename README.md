Migrations
==========

Migrations for Nette Framework Database (and others db layers).

Forked [Clevis\Migrations](https://github.com/clevis/migration), enhanced and updated for Nette Database.

Implemented drivers for:
- Nette Database **MySQL**
- Nette Database **PostgreSQL**

Workflow
--------
```php
// prepare driver
$connection = new Nette\Database\Connection('mysql:dbname=testdb', 'root', 'root');
$context = new Nette\Database\Context($connection);
$driver = new Nextras\Migrations\Drivers\MySqlNetteDbDriver($context, 'migrations');

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
