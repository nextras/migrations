<?php
require __DIR__ . '/../vendor/autoload.php';

$dibi = new DibiConnection(array(
	'database' => 'test',
	'username' => 'root',
	'password' => 'toor',
));

$controller = new Nextras\Migrations\Controllers\HttpController($dibi);
$controller->addGroup('structures', __DIR__ . '/structures');
$controller->addGroup('data', __DIR__ . '/data', ['structures']);
$controller->addExtension('sql', new Nextras\Migrations\Extensions\Sql($dibi));

$controller->run();
