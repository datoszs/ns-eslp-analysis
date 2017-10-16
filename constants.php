<?php declare(strict_types=1);

$outputDir = __DIR__ . '/output/'; // Where to put outputs (final, temporary)
$dataDir = __DIR__ . '/data/'; // Where directory with decisions is, processed recursively
$filePattern = '*.txt'; // What files to process are (others are ignored)
$metadataFilePattern = '*.xlsx'; // What files to process are in case of metadata
$eslpNumbersFilename = 'echr_cases.xlsx';

// Regex
define('REGEX_1_TO_15_WORDS', '\s+((\b\w+\b)\s+){1,15}'); // 1-15 Words
define('WHITESPACES', '\s+'); // Volné místo (mezery, taby, nový řádek, alespoň 1)

// Dividers between introduction and justification
// For given registry mark following dividors are used. Ends after first match!
$justificationDivider = [
    [
        'match' => '^.*? (Cdo|Cdon|ICdo|NSČR|Odo|Odon|Tdo) .*?$',
        'dividers' => [
            " 293/2013 ",
            " 404/2012 ",
            " 7/2009 ",
            " 295/2008 ",
            " 126/2008 ",
            " 296/2007 ",
            " 216/2006 ",
            " 59/2005 ",
            " 153/2004 ",
            " 120/2004 ",
            " 367/2000 ",
            " 30/2000 ",
            " 91/1998 ",
            " 247/1995 ",
            " 171/1993 ",
            " 24/1993 ",
            "jako" . WHITESPACES . "soud" . WHITESPACES . "dovolací",
            "jako" . WHITESPACES . "dovolací" . WHITESPACES . "soud",
            "jakožto" . WHITESPACES . "soud" . WHITESPACES . "dovolací",
            "jakožto" . WHITESPACES . "dovolací" . WHITESPACES . "soud",
            "dovolání" . WHITESPACES . "bylo" . WHITESPACES . "podáno" . WHITESPACES . "včas",
            "není" . WHITESPACES . "přípustné",
            "není" . WHITESPACES . "přípustným",
            "přípustné" . WHITESPACES . "není",
            "přípustným" . WHITESPACES . "není",
            "Dovolání" . WHITESPACES . "je" . WHITESPACES . "přípustné",
            "dovolání" . WHITESPACES . "je" . WHITESPACES . "přípustné",
            "důvodné" . WHITESPACES . "není",
            "není" . WHITESPACES . "důvodné",
            "je" . WHITESPACES . "důvodné",
            "není" . REGEX_1_TO_15_WORDS . "přípustné",
            "není" . REGEX_1_TO_15_WORDS . "přípustným",
            "přípustné" . REGEX_1_TO_15_WORDS . "není",
            "přípustným" . REGEX_1_TO_15_WORDS . "není",
            "Dovolání je" . REGEX_1_TO_15_WORDS . "přípustné",
            "dovolání je" . REGEX_1_TO_15_WORDS . "přípustné",
            "důvodné" . REGEX_1_TO_15_WORDS . "není",
            "Důvodné" . REGEX_1_TO_15_WORDS . "není",
            "je" . REGEX_1_TO_15_WORDS . "důvodné",
            "zjevně" . WHITESPACES . "neopodstatněn",
        ]
    ],
    [
        'match' => '.*',
        'dividers' => [
            "Nejvyšší" . WHITESPACES . "soud" . WHITESPACES . "jako",
            "Nejvyšší" . WHITESPACES . "soud" . WHITESPACES . "České" . WHITESPACES . "republiky" . WHITESPACES . "jako",
            "dospěl" . WHITESPACES . "k",
            "k" . WHITESPACES . "tomuto" . WHITESPACES . "závěru" . WHITESPACES . "dospěl",
            "Nejvyšší" . WHITESPACES . "soud" . WHITESPACES . "shledal",
            "Nejvyšší" . WHITESPACES . "soud" . WHITESPACES . "České" . WHITESPACES . "republiky" . WHITESPACES . "shledal",
            "dospěl" . REGEX_1_TO_15_WORDS . " k",
        ]
    ]
];