<?php

/**
 * @testCase
 * @dataProvider ../../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Phalcon\Cli\Dispatcher;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;


require __DIR__ . '/../../../bootstrap.php';


class PhalconTest extends TestCase
{
	/** @var \Phalcon\Cli\Console */
	private $console;


	protected function setUp()
	{
		parent::setUp();

		Environment::lock(__CLASS__, __DIR__ . '/../../../temp');

		$options = Environment::loadData();
		$driversConfig = parse_ini_file(__DIR__ . '/../../../drivers.ini', TRUE);
		$dbalOptions = $driversConfig[$options['driver']];

        // Using the CLI factory default services container
        $di = new \Phalcon\Di\FactoryDefault\Cli();

        // DI services
        $di->set(
            'config',
            new \Phalcon\Config([
                'migrationsDir' => __DIR__ . "/../../../fixtures/$options[driver]",
                'host' => $dbalOptions['host'],
                'username' => $dbalOptions['username'],
                'password' => $dbalOptions['password'],
                'dbname' => $dbalOptions['database'],
            ])
        );
        $di->set(
            'migrationsDir',
            function () {
                /** @var \Phalcon\Config $config */
                $config = $this->get('config');
                return $config->migrationsDir;
            }
        );
        $di->set(
            'phalconAdapter',
            function () {
                /** @var \Phalcon\Db\Adapter\Pdo $connection */
                $connection = $this->get('connection');
                return new \Nextras\Migrations\Bridges\Phalcon\PhalconAdapter($connection);
            }
        );

        if ($options['driver'] === 'mysql') {
            $di->set(
                'connection',
                function () {
                    /** @var \Phalcon\Config $config */
                    $config = $this->get('config');
                    return new \Phalcon\Db\Adapter\Pdo\Mysql([
                        'host' => $config->host,
                        'username' => $config->username,
                        'password' => $config->password,
                        'dbname' => $config->dbname,
                        'dialectClass' => new \Phalcon\Db\Dialect\Mysql(),
                    ]);
                }
            );
            $di->set(
                'driver',
                function () {
                    /** @var \Nextras\Migrations\Bridges\Phalcon\PhalconAdapter $phalconAdapter */
                    $phalconAdapter = $this->get('phalconAdapter');
                    return new \Nextras\Migrations\Drivers\MySqlDriver($phalconAdapter);
                }
            );
        } else {
            $di->set(
                'connection',
                function () {
                    /** @var \Phalcon\Config $config */
                    $config = $this->get('config');
                    return new \Phalcon\Db\Adapter\Pdo\Postgresql([
                        'host' => $config->host,
                        'username' => $config->username,
                        'password' => $config->password,
                        'dbname' => $config->dbname,
                        'dialectClass' => new \Phalcon\Db\Dialect\Mysql(),
                    ]);
                }
            );
            $di->set(
                'driver',
                function () {
                    /** @var \Nextras\Migrations\Bridges\Phalcon\PhalconAdapter $phalconAdapter */
                    $phalconAdapter = $this->get('phalconAdapter');
                    return new \Nextras\Migrations\Drivers\PgSqlDriver($phalconAdapter);
                }
            );
        }

        // Configure Task Namespace
        /** @var Dispatcher $dispatcher */
        $dispatcher = $di->get('dispatcher');
        $dispatcher->setDefaultNamespace('Nextras\\Migrations\\Bridges\\Phalcon');

        // Create a console application
        $this->console = new \Phalcon\Cli\Console();
        $this->console->setDI($di);
	}


	public function testMigrationsReset()
	{
        Assert::noError(function () {
            $arguments = [];
            $arguments['task'] = 'migrations';
            $arguments['action'] = 'main';
            $arguments['params'] = ['reset'];
            $this->console->handle($arguments);
        });
	}


	public function testMigrationsContinue()
	{
        Assert::noError(function () {
            $arguments['task'] = 'migrations';
            $arguments['action'] = 'main';
            $arguments['params'] = ['continue'];

            $this->console->handle($arguments);
        });
	}
}


(new PhalconTest)->run();
