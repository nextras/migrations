<?php

namespace Nextras\Migrations\Bridges\Phalcon;

use Nextras\Migrations\Controllers\ConsoleController;
use Nextras\Migrations\Extensions\SqlHandler;
use Nextras\Migrations\IDriver;
use Phalcon\Cli\Task;
use Phalcon\Di\FactoryDefault\Cli;

class MigrationsTask extends Task
{
    /** @var string|null */
    private $migrationsDir;

    /** @var IDriver|null */
    private $driver;

    /** @var Cli|null */
    private $di;

    const ACTION_CREATE = 'create';
    const ACTION_CREATE_ABBR = 'cr';
    const ACTION_RESET = 'reset';
    const ACTION_RESET_ABBR = 're';
    const ACTION_CONTINUE = 'continue';
    const ACTION_CONTINUE_ABBR = 'co';

    const GROUP_BASIC_DATA = 'basic-data';
    const GROUP_BASIC_DATA_ABBR = 'b';
    const GROUP_DUMMY_DATA = 'dummy-data';
    const GROUP_DUMMY_DATA_ABBR = 'd';
    const GROUP_STRUCTURES = 'structures';
    const GROUP_STRUCTURES_ABBR = 's';

    /** @var array */
    private static $groups = [
        self::GROUP_BASIC_DATA_ABBR => self::GROUP_BASIC_DATA,
        self::GROUP_DUMMY_DATA_ABBR => self::GROUP_DUMMY_DATA,
        self::GROUP_STRUCTURES_ABBR => self::GROUP_STRUCTURES,
    ];

    private function getContainer()
    {
        if ($this->di === null) {
            $this->di = $this->getDI();
        }

        return $this->di;
    }

    /**
     * @return string
     */
    private function getMigrationsDir()
    {
        if ($this->migrationsDir === null) {
            $this->migrationsDir = $this->getContainer()->get('config')->migrationsDir;
        }

        return $this->migrationsDir;
    }

    /**
     * @return IDriver
     */
    private function getDriver()
    {
        if ($this->driver === null) {
            $this->driver = $this->getContainer()->get('driver');
        }
        return $this->driver;
    }

    /**
     * @param array $params
     */
    public function mainAction($params)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            $params = $this->handleParams($params);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }

        $controller = new ConsoleController($this->getDriver());

        $controller->addGroup('structures', $this->getMigrationsDir() . '/structures');
        $controller->addGroup('basic-data', $this->getMigrationsDir() . '/basic-data', ['structures']);
        $controller->addGroup('dummy-data', $this->getMigrationsDir() . '/dummy-data', ['basic-data']);
        $controller->addExtension('sql', new SqlHandler($this->getDriver()));

        // override parameters
        $_SERVER['argv'] = array_merge([$_SERVER['argv'][0]], $params);

        $controller->run();
    }

    /**
     * @param array $params
     * @return array
     * @throws \InvalidArgumentException
     */
    private function handleParams($params)
    {
        if (count($params) !== 1) {
            throw new \InvalidArgumentException('Invalid number of params.');
        }

        $param_parts = explode(':', $params[0]);
        $count = count($param_parts);

        // continue or reset
        if ($count > 0 && $count < 3) {
            $production = false;
            if (isset($param_parts[1])) {
                if ($param_parts[1] === 'production') {
                    $production = true;
                } else {
                    throw new \InvalidArgumentException('Invalid params.');
                }
            }

            return $this->runMigrations($param_parts[0], $production);
        } elseif ($count === 3) {
            // create
            if (!in_array($param_parts[0], [self::ACTION_CREATE, self::ACTION_CREATE_ABBR], true)) {
                throw new \InvalidArgumentException('Invalid params.'); // 3 arguments can have only create action
            }

            $this->createMigration($param_parts[1], $param_parts[2]);
        }

        throw new \InvalidArgumentException('Invalid params.');
    }

    /**
     * @param string $message
     */
    private function error($message)
    {
        echo 'ERROR: ' . $message . PHP_EOL;
        $this->printUsage();
        exit(1);
    }

    /**
     * @param string $action
     * @param bool $production
     * @return array
     * @throws \InvalidArgumentException
     */
    private function runMigrations($action, $production)
    {
        if (!in_array($action, [self::ACTION_CONTINUE, self::ACTION_CONTINUE_ABBR, self::ACTION_RESET, self::ACTION_RESET_ABBR], true)) {
            throw new \InvalidArgumentException('Invalid action.');
        }

        $return = [self::GROUP_STRUCTURES, self::GROUP_BASIC_DATA];

        if ($production === false) {
            $return[] = self::GROUP_DUMMY_DATA;
        }

        if (in_array($action, [self::ACTION_RESET, self::ACTION_RESET_ABBR], true)) {
            $return[] = '--reset';
        }

        return $return;
    }

    /**
     * @param string $group
     * @param string $label
     * @throws \InvalidArgumentException
     */
    private function createMigration($group, $label)
    {
        if (!in_array($group, array_merge(array_keys(self::$groups), array_values(self::$groups)), true)) {
            throw new \InvalidArgumentException('Invalid group.');
        }

        // replace group abbreviation for group name
        if (array_key_exists($group, self::$groups)) {
            $group = self::$groups[$group];
        }

        $dir = $this->getMigrationsDir() . '/' . $group;
        $name = date('Y-m-d-His-') . preg_replace('/[[:^print:]]/', '', $label) . '.sql';
        @mkdir($dir, 0777, true);
        touch("$dir/$name");

        exit;
    }

    private function printUsage()
    {
        $scriptFile = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : 'app/cli.php';

        echo '------------------------------' . PHP_EOL;
        echo 'Usage:    php ' . $scriptFile . ' Nextras\\\\Migrations\\\\Bridges\\\\Phalcon\\\\Migrations main <action>[:<group>:<label>][:production]' . PHP_EOL;
        echo PHP_EOL;
        echo 'Example:  php ' . $scriptFile . ' Nextras\\\\Migrations\\\\Bridges\\\\Phalcon\\\\Migrations main create:dummy-data:users' . PHP_EOL;
        echo 'Example:  php ' . $scriptFile . ' Nextras\\\\Migrations\\\\Bridges\\\\Phalcon\\\\Migrations main cr:d:users' . PHP_EOL;
        echo 'Example:  php ' . $scriptFile . ' Nextras\\\\Migrations\\\\Bridges\\\\Phalcon\\\\Migrations main reset' . PHP_EOL;
        echo 'Example:  php ' . $scriptFile . ' Nextras\\\\Migrations\\\\Bridges\\\\Phalcon\\\\Migrations main co:production' . PHP_EOL;
        echo PHP_EOL;
        echo 'Actions:' . PHP_EOL;
        echo '    create' . PHP_EOL;
        echo '        Can be aliased as "cr".' . PHP_EOL;
        echo '        Creates empty sql file named YYYY-MM-DD-HHMMSS-label.sql.' . PHP_EOL;
        echo '        E.g. 2015-03-16-170342-users.sql.' . PHP_EOL;
        echo '        <label> is mandatory for "create" action.' . PHP_EOL;
        echo '    continue' . PHP_EOL;
        echo '        Can be aliased as "co".' . PHP_EOL;
        echo '        Migrates not migrated sql files only.' . PHP_EOL;
        echo '        Optional flag "production" (if present all dummy-data files are skipped).' . PHP_EOL;
        echo '    reset' . PHP_EOL;
        echo '        Can be aliased as "re".' . PHP_EOL;
        echo '        Drop whole database and then migrates all sql files.' . PHP_EOL;
        echo '        Optional flag "production" (if present all dummy-data files are skipped).' . PHP_EOL;
        echo PHP_EOL;
        echo 'Groups:' . PHP_EOL;
        echo '    basic-data' . PHP_EOL;
        echo '        Can be aliased as "b".' . PHP_EOL;
        echo '        Data for both development and production.' . PHP_EOL;
        echo '    dummy-data' . PHP_EOL;
        echo '        Can be aliased as "d".' . PHP_EOL;
        echo '        Data for development on localhost.' . PHP_EOL;
        echo '    structures' . PHP_EOL;
        echo '        Can be aliased as "s".' . PHP_EOL;
        echo '        Creates, alter tables, etc.' . PHP_EOL;
        echo PHP_EOL;
        echo 'Label:' . PHP_EOL;
        echo '    For "create" action only. Should be some brief name for sql file contents.' . PHP_EOL;
        echo PHP_EOL;
        echo 'Production:' . PHP_EOL;
        echo '    For "continue" and "reset" actions only.' . PHP_EOL;
        echo '    If present all dummy-data files are skipped.' . PHP_EOL;
        echo '------------------------------' . PHP_EOL;
    }
}
