<?php declare(strict_types=1);

use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

require_once __DIR__ . '/constants.php';

function parseRegistryMark(string $filePath):string
{
    $fileName = basename($filePath);
    $fileName = str_replace(['.txt', '_'], ['', ' '], $fileName);
    $dash = strrpos($fileName, ' ');
    if ($dash !== -1) {
        $fileName[$dash] = '/';
    }
    return $fileName;
}

function getDividers(string $registryMark): array
{
    global $justificationDivider;

    foreach ($justificationDivider as list('match' => $match, 'dividers' => $dividers)) {
        if (preg_match('/' . $match . '/Asu', $registryMark)) {
            return $dividers;
        }
    }
    throw new InvalidArgumentException("For registry mark [$registryMark] no justification divider group was matched! Invalid configuration");
}

function loadFile(string $filePath): string
{
    $content = FileSystem::read($filePath);
    return iconv('WINDOWS-1250', 'UTF-8//IGNORE', $content);
}

function findJustificationDivider(string $content, array $dividers): ?array
{
    foreach ($dividers as $divider) {
        $found = preg_match('#'. $divider . '#iu', $content, $matches, PREG_OFFSET_CAPTURE);
        if ($found) {
            return [$matches[0][0], transformByteOffsetToPosition($content, $matches[0][1])];
        }
    }
    return null;
}

function transformByteOffsetToPosition(string $content, int $byteOffset): int
{
    return Strings::length(mb_strcut($content, 0, $byteOffset));
}

function getKeysGroups()
{
    global $outputDir;
    $content = file_get_contents($outputDir . 'eslp.csv');
    return [
        'sp.zn.' => transformESLPNumberToRegex($content),
        'Evropský soud' => ['#\b(Evropský soud|Evropského soudu|Evropskému soudu|Evropský soud|Evropském soudu|Evropským soudem)\b#ui'],
        'ESLP' => ['#\b(ESLP)\b#ui'],
    ];
}

function transformESLPNumberToRegex(string &$content) // reference for performance
{
    $signatures = array_map(function (string $item) { return preg_quote($item, '#'); }, explode("\n", $content));
    $output = [];
    $chunked = array_chunk($signatures, 1000, false);
    foreach ($chunked as $chunk) {
        $output[] = sprintf('#\b(%s)\b#ui', implode('|', $chunk));
    }
    return $output;
}

function prepareESLPNumbers()
{
    global $dataDir, $eslpNumbersFilename;
    $filesIterator = Finder::findFiles($eslpNumbersFilename)
        ->from($dataDir)
        ->getIterator();
    $numbers = [];
    foreach ($filesIterator as $filePath => $file) {
        $reader = PHPExcel_IOFactory::load($filePath);
        $sheets = $reader->getAllSheets();
        /**
         * @var int $sheetNumber
         * @var PHPExcel_Worksheet $sheet
         */
        foreach ($sheets as $sheetNumber => $sheet) {
            /** @var PHPExcel_Worksheet_Row $row */
            foreach ($sheet->getRowIterator(2) as $row) {
                $rowIterator = $row->getCellIterator();
                $h = $rowIterator->seek('H')->current()->getCalculatedValue();
                if ($h) {
                    array_push($numbers, ...explode(';', $h));
                }
            }
        }
    }
    return array_unique($numbers);
}

function prepareMetadata()
{
    global $dataDir, $metadataFilePattern, $eslpNumbersFilename;
    $filesIterator = Finder::findFiles($metadataFilePattern)
        ->from($dataDir)
        ->getIterator();
    $metadata = [];
    foreach ($filesIterator as $filePath => $file) {
        if (basename($filePath) === $eslpNumbersFilename) {
            continue;
        }
        $reader = PHPExcel_IOFactory::load($filePath);
        $sheets = $reader->getAllSheets();
        /**
         * @var int $sheetNumber
         * @var PHPExcel_Worksheet $sheet
         */
        foreach ($sheets as $sheetNumber => $sheet) {
            /** @var PHPExcel_Worksheet_Row $row */
            foreach ($sheet->getRowIterator(2) as $row) {
                $rowIterator = $row->getCellIterator();
                $e = $rowIterator->seek('E')->current()->getCalculatedValue();
                $f = $rowIterator->seek('F')->current()->getCalculatedValue();
                $k = $rowIterator->seek('K')->current()->getCalculatedValue();
                if (!isset($metadata[$e])) {
                    $metadata[$e] = [];
                }
                $metadata[$e][] = [
                    'decision_date' => $f,
                    'decision_year' => $f ? extractYear($f) : null,
                    'published' => $k === '' ? null : $k
                ];
            }
        }
    }
    return $metadata;
}

function extractYear(string $input): ?int
{
    $temp = explode('.', $input);
    if (count($temp) === 3) {
        return (int) $temp[2];
    }
    return null;
}

function getLastDecisionYear(array &$metadata, string $signature): ?int // reference for performance
{
    if (isset($metadata[$signature])) {
        $temp = array_column($metadata[$signature], 'decision_year');
        rsort($temp);
        if (count($temp) > 0) {
            return $temp[0];
        }
    }
    return null;
}
function getPublishedStatus(array &$metadata, string $signature): ?string // reference for performance
{
    if (isset($metadata[$signature])) {
        return count(array_filter(array_column($metadata[$signature], 'published'))) !== 0 ? 'Ano' : 'Ne';
    }
    return null;
}

function fixWrongEscaping(string $content): string
{
    return stripslashes($content); // content appears to have quoted " and that causes problem in CSV
}

function getKrakenLink(string $registrySign): string
{
    return 'http://kraken.slv.cz/' . str_replace(' ', '', $registrySign);
}