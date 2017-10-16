<?php declare(strict_types=1);

use Nette\Utils\Json;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';

$metadata = prepareMetadata();

file_put_contents($outputDir . 'metadata.json', Json::encode($metadata, Json::PRETTY));

