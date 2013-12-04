<?php

require __DIR__ . '/../vendor/autoload.php';

$connection = new Nette\Database\Connection('mysql:dbname=testdb', 'root', 'root');
$context = new Nette\Database\Context($connection);

$driver = new Nextras\Migrations\Drivers\MySqlNetteDbDriver($context, 'migrations');

$controller = new Nextras\Migrations\Controllers\HttpController($driver);
$controller->addGroup('structures', __DIR__ . '/structures');
$controller->addGroup('data', __DIR__ . '/data', ['structures']);
$controller->addExtension('sql', new Nextras\Migrations\Extensions\NetteDbSql($context));

$controller->run();
