<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/inc/TestPrinter.php';
require __DIR__ . '/inc/IntegrationTestCase.php';

date_default_timezone_set('Europe/Prague');
Tester\Environment::setup();
