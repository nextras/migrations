<?php declare(strict_types = 1);

return function () {
	$file = $this->getSqliteFile();
	@unlink($file);
	@unlink($file . '.lock');
};
