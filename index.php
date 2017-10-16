<?php declare(strict_types=1);

use League\Csv\Writer;
use Nette\Utils\Finder;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/functions.php';


$startTime = microtime(true);

// Find all files for processing
$filesIterator = Finder::findFiles($filePattern)->from($dataDir);
$totalFiles = $filesIterator->count();

// Process files
/**
 * @var string $filePath
 * @var SplFileInfo $file
 */
$limit = 1000000;
$processed = 0;
$final = true;
$output = [];

// Load prepared data or fail
if ($final) {
    if (!file_exists($outputDir . 'metadata.json') || !file_exists($outputDir . 'eslp.csv')) {
        printf("Missing output/metadata.json or output/eslp.csv. Have you run preparation? See README.md.\n");
        exit(1);
    }
    try {
        $metadata = Json::decode(file_get_contents($outputDir . 'metadata.json'), Json::FORCE_ARRAY);
    } catch (JsonException $exception) {
        printf("Malformed output/metadata.json: ");
        printf($exception->getMessage());
        print_r($exception);
        printf("\n");
        exit(2);
    }
    $keysGroups = getKeysGroups();
}
foreach ($filesIterator->getIterator() as $filePath => $file) {
    echo $filePath . PHP_EOL;
    $processed++;
    // Load file
    $registryMark = parseRegistryMark($filePath);
    $dividers = getDividers($registryMark);
    $content = loadFile($filePath);

    // Get general statistics
    $charLength = Strings::length($content); // count characters, not bytes!

    // Find justification divider
    $temp = findJustificationDivider($content, $dividers);
    list($foundDivider, $foundPosition) = $temp ?? [null, null];

    // Prepare output
    if ($final) {
        $core = [
            $registryMark,
            null, // Kategorie klíče
            null, // Nalezený klíč
            null, // Pozice klíče v rámci dokumentu
            null, // Okolí klíče
            null, // Výskyt klíčů
            $foundPosition ? round($foundPosition / $charLength, 6) : null, // Procentuální pozice rozdělovače
            getLastDecisionYear($metadata, $registryMark), // Rok vydání rozhodnutí
            getPublishedStatus($metadata, $registryMark), // Publikováno
            str_replace($dataDir, '', $filePath), // Soubor
            getKrakenLink($registryMark) // Odkaz na webu
        ];

        foreach ($keysGroups as $group => $regexs)
        {
            foreach ($regexs as $regex) {
                $matches = [];
                preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE);
                $hasAnyKeys = false;
                $keysLocation = [0, 0];

                if (count($matches[1]) > 0) {
                    // Determine key occurrence and precount length in characters
                    $hasAnyKeys = true;
                    foreach ($matches[1] as &$match) {
                        $match[2] = transformByteOffsetToPosition($content, $match[1]);
                        if ($foundPosition) {
                            if ($match[2] >= $foundPosition) {
                                ++$keysLocation[1];
                            } else {
                                ++$keysLocation[0];
                            }
                        }
                    }
                    unset($match);
                    $keysOccurence = null;
                    if (!$foundPosition) {
                        $keysOccurence = 'nerozlišeno';
                    } elseif ($keysLocation[0] > 0 && $keysLocation[1] > 0) {
                        $keysOccurence = 'odůvodnění i narace';
                    } elseif ($keysLocation[0] > 0) {
                        $keysOccurence = 'jen narace';
                    } elseif ($keysLocation[1] > 0) {
                        $keysOccurence = 'jen odůvodnění';
                    }


                    foreach ($matches[1] as $match) {
                        $row = $core;
                        $row[1] = $group;
                        $row[2] = $match[0];
                        $row[3] = round($match[2] / $charLength, 6); // TODO length
                        $row[4] = fixWrongEscaping(mb_substr($content, max(0, $start = $match[2] - 200), $match[2] - $start));
                        $row[4] .= fixWrongEscaping('[[[' . $row[2] . ']]]');
                        $row[4] .= fixWrongEscaping(mb_substr($content, $match[2] + mb_strlen($row[2]), 200));
                        $row[5] = $keysOccurence;
                        $output[] = $row;
                    }
                }
            }
        }
    } else {
        $output[] = [
            $registryMark,
            ($foundDivider && $foundPosition) ? 'Ano' : 'Ne',
            $foundDivider,
            $foundPosition,
            $charLength,
            $foundPosition ? round($foundPosition / $charLength, 2) : null,
            str_replace($dataDir, '', $filePath),
        ];
    }


    if ($limit === 0) {
        break;
    }
    $limit--;
}

// Output the code with proper header
$csv = Writer::createFromFileObject(new SplTempFileObject());
if ($final) {
    $csv->insertOne(
        [
            'Spisová značka',                                                   // Spisová značka rozhodnutí splňující filtrovací podmínky (Typ: string)
            'Kategorie klíče',                                                  // Kategorie klíče v odůvodnění (nebo obecně nebylo-li odůvodnění rozlišeno), podle které bylo rozhodnutí vybráno (Typ: kategorie s hodnotami <ESLP|evropský soud|sp.zn.>)
            'Nalezený klíč',                                                    // Konkrétní nalezený klíč, tak jak se vyskytoval v textu (Typ: string)
            'Pozice klíče v rámci odůvodnění',                                  // V kolika procentech délky textu rozhodnutí se "klíč" v odůvodnění (případně jakýkoliv "klíč" kdekoliv v textu, nebylo-li odůvodnění rozlišeno) nacházel. (Typ: number)
            'Okolí klíče',                                                      // Extrakt textu v okolí "klíče" v odůvodnění (případně jakéhokoliv "klíče" kdekoliv v textu, nebylo-li odůvodnění rozlišeno), cca 200 znaků před a 200 po. Nalezený klíč je obalen do [[[]]] (Typ: string)
            'Výskyt klíčů',                                                     // Příznak zda se "klíče" (jakékoliv) objevily jen v odůvodnění nebo i v naraci, případně zda to vůbec bylo rozlišeno (Typ: kategorie s hodnotami <jen odůvodnění|jen narace|odůvodnění i narace|nerozlišeno>)
            'Procentuální pozice rozdělovače',                                  // Pokud se podařilo rozlišit naraci a odůvodnění, v kolika procentech délky textu rozhodnutí se nacházel rozdělovač narace a odůvodnění, jinak prázdné (Typ: number)
            'Rok vydání rozhodnutí',                                            // Rok vydání rozhodnutí (Typ: integer, prázdné pokud není nalezeno, nebo se nepodařilo určit)
            'Publikováno',                                                      // Info, zda rozhodnutí bylo publikované, informace z metadat (Typ: kategorie s hodnotami <ano|ne|> prázdná hodnota pokud nebylo v metadatech nalezeno)
            'Soubor',                                                           // Odkaz na text rozhodnutí vedoucí buď do dokumentu ve složce, kam si rozhodnutí rozbalíš, nebo na web soudu (TEXT)
            'Odkaz'                                                             // Odkaz na text rozhodnutí na webu
        ]
    );
} else {
    $csv->insertOne(
        [
            'Spisová značka',
            'Nalezeno rozdělení narace a odůvodnění',
            'Rozdělovač',
            'Pozice rozdělovače',
            'Délka dokumentu',
            'Výskyt',
            'Soubor'
        ]
    );
}

$csv->insertAll($output);
file_put_contents($outputDir . 'final.csv', $csv->__toString());
printf("Processed %d documents\n", $processed);

printf("DONE in %s seconds\n", round( microtime(true) - $startTime));