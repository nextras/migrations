<?php declare(strict_types=1);

namespace Nextras\Migrations\Engine;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\PlanException;


class Plan
{

	/** @var NULL|string */
	private $planFile;


	/**
	 * @param NULL|string $planFile
	 */
	public function __construct($planFile)
	{
		$this->planFile = $planFile;
	}


	private function getEntries(): array
	{
		if ($this->planFile === NULL || !file_exists($this->planFile)) {
			return [];
		}

		$raw = file_get_contents($this->planFile);
		$lines = preg_split("~\r?\n\r?~", $raw, -1, PREG_SPLIT_NO_EMPTY);
		return array_map(function(string $line) {
			return explode("\t", $line);
		}, $lines);
	}


	public function append(Group $group, string $name): void
	{
		$entries = $this->getEntries();
		$lastEntry = array_shift($entries);

		$ord = 0;
		if ($lastEntry !== NULL) {
			$ord = 1 + (int) $lastEntry[0];
		}

		file_put_contents($this->planFile, "$ord\t{$group->name}\t$name\n", FILE_APPEND);
	}


	public function validate(): void
	{
		$expectedOrd = 0;
		foreach ($this->getEntries() as $entry) {
			if ($expectedOrd !== (int) $entry[0]) {
				$fmtEntry = $entry[1] . ': ' . $entry[2];
				throw new PlanException("Execution plan violation: expected ord $expectedOrd, got $entry[0] ($fmtEntry).");
			}
			$expectedOrd++;
		}
	}

}
