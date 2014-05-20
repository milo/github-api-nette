<?php

if (version_compare(Nette\Framework::VERSION, '2.1', '<')) {
	require __DIR__ . '/Nette-2.0.php';
} elseif (version_compare(Nette\Framework::VERSION, '2.2', '<')) {
	require __DIR__ . '/Nette-2.1.php';
} else {
	require __DIR__ . '/Nette-2.2.php';
}
