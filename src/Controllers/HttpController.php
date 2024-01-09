<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Controllers;

use Nextras\Migrations\Engine;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\Printers;


class HttpController extends BaseController
{
	/** @var string */
	private $action;

	/** @var string */
	private $error;


	public function run(): void
	{
		$this->processArguments();
		$this->executeAction();
	}


	private function processArguments(): void
	{
		if (isset($_GET['action'])) {
			if ($_GET['action'] === 'run' || $_GET['action'] === 'css') {
				$this->action = $_GET['action'];
			} else {
				$this->action = 'error';
			}
		} else {
			$this->action = 'index';
		}

		if ($this->action === 'run') {
			if (isset($_GET['groups']) && is_array($_GET['groups'])) {
				foreach ($_GET['groups'] as $group) {
					if (is_string($group)) {
						if (isset($this->groups[$group])) {
							$this->groups[$group]->enabled = true;
						} else {
							$error = sprintf(
								"Unknown group '%s', the following groups are registered: '%s'",
								$group, implode('\', \'', array_keys($this->groups))
							);
							goto error;
						}
					} else {
						$error = 'Malformed groups parameter.';
						goto error;
					}
				}
			} else {
				$error = 'Missing or invalid groups parameter.';
				goto error;
			}

			if (!isset($_GET['mode'])) {
				$error = 'Missing mode parameter.';
				goto error;
			}

			switch ($_GET['mode']) {
				case '0': $this->mode = Engine\Runner::MODE_CONTINUE; break;
				case '1': $this->mode = Engine\Runner::MODE_RESET; break;
				case '2': $this->mode = Engine\Runner::MODE_INIT; break;
				default:
					$error = 'Unknown mode.';
					goto error;
			}
		}

		return;

		error:
		$this->action = 'error';
		$this->error = $error;
	}


	private function executeAction(): void
	{
		$method = 'action' . ucfirst($this->action);
		$this->$method();
	}


	private function actionIndex(): void
	{
		$combinations = $this->getGroupsCombinations();
		$this->printHeader();

		$modes = [
			0 => '<h2 class="continue">Continue</h2>',
			1 => '<h2 class="reset">Reset <small>All tables, views and data will be destroyed!</small></h2>',
			2 => '<h2 class="init">Init SQL</h2>',
		];

		echo "<h1>Migrations</h1>\n";
		foreach ($modes as $mode => $heading) {
			echo "<div class='mode mode-{$mode}'>";
			echo "$heading\n";
			echo "<ul>\n";
			foreach ($combinations as $combination) {
				$query = htmlspecialchars(http_build_query(['action' => 'run', 'groups' => $combination, 'mode' => $mode]));
				$text = htmlspecialchars(implode(' + ', $combination));
				$alert = $mode === 1 ? ' onclick="return confirm(\'Are you really sure?\')"' : '';
				echo "\t<li><a href=\"?$query\"{$alert}>Run $text</a>\n";
			}
			echo "</ul>";
			echo "</div>\n\n";
		}
	}


	private function actionRun(): void
	{
		$groups = $this->registerGroups();
		$groups = implode(' + ', $groups);

		$this->printHeader();
		echo "<h1>Migrations – $groups</h1>\n";
		echo "<div class=\"output\">";
		$this->runner->run($this->mode);
		echo "</div>\n";
	}


	private function actionCss(): void
	{
		header('Content-Type: text/css', true);
		readfile(__DIR__ . '/templates/main.css');
	}


	private function actionError(): void
	{
		$this->printHeader();
		echo "<h1>Migrations – error</h1>\n";
		echo "<div class=\"error-message\">" . nl2br(htmlspecialchars($this->error), false) . "</div>\n";
	}


    /**
     * @return list<list<string>>
     */
	private function getGroupsCombinations(): array
	{
		$groups = [];
		$index = 1;
		foreach ($this->groups as $group) {
			$groups[$index] = $group;
			$index = ($index << 1);
		}

		$combinations = [];
		for ($i = 1; true; $i++) {
			$combination = [];
			foreach ($groups as $key => $group) {
				if ($i & $key) {
					$combination[] = $group->name;
				}
			}

			if (empty($combination)) {
				break;
			}

			foreach ($combination as $groupName) {
				foreach ($this->groups[$groupName]->dependencies as $dependency) {
					if (!in_array($dependency, $combination)) continue 3;
				}
			}
			$combinations[] = $combination;
		}
		return $combinations;
	}


	private function printHeader(): void
	{
		readfile(__DIR__ . '/templates/header.phtml');
	}


	protected function createPrinter(): IPrinter
	{
		return new Printers\HtmlDump();
	}
}
