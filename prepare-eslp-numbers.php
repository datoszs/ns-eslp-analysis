<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';

$output = prepareESLPNumbers();

file_put_contents($outputDir . 'eslp.csv', implode("\n", $output));

