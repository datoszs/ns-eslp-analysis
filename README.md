Analýza rozhodnutí NS
=====================

1. Vstupní data nahrajte do složky `data`, na struktuře složek nezáleží. Jednotlivá rozhodnutí musí být v `.txt` souborech a jejich název (bez přípony) musí odpovídat spisové značce (včetně kapitalizace, tedy například `3_Nd_58_2001.txt`). Tato složka též obsahuje soubory `.xlsx` s metadaty od soudů (sloupec E spisová značka, F datum rozhodnutí, K publikace ve sbírce pod číslem) - tyto sloupce jsou potřeba nadále. Dále někde ve složce `data` je třeba umístit soubor `echr_cases.xlsx`, ze kterého se parsují spisové značky ESLP (tento soubor je vyloučen z přípravy metadat jednotlivých rozhodnutí)
2. Následně je potřeba spustit `php -d memory_limit=4096M prepare-metadata.php`, který z připraví metadata z `.xlsx` souborů do souboru `output/metadata.json`, který je nezbytný pro další parsování.
3. Následně je potřeba spustit `php -d memory_limit=4096M prepare-eslp-numbers.php`, který z dokumentu `echr_cases.xlsx` sestaví seznam spisových značek ESLP do souboru `output/eslp.csv`, který je nezbytný pro další parsování.
4. Následně je potřeba spustit `php -d memory_limit=4096M index.php`

Spuštění každého ze skriptů chvíli zabere, v případě přípravných skriptů jsou to minuty, u hlavního skriptu pak jde o cca hodiny, záleží na dostupné paměti, rychlosti procesoru a v případě hlavního skriptu též rychlosti pevného disku.

Ladící poznámky
---------------

Úpravou skriptu `index.php` lze proces data analýzy ladit.

`$limit` - maximum zpracovaných souborů rozhodnutí
`$final` - zda provádět finální analýzu, nebo analýzu rozdělovačů (liší se výstup)


Požadavky
---------

PHP alespoň ve verzi 7.1 nainstalované na příkazové řádce
Rozšíření PHP `php-zip`.
Dostatek paměti, alespoň 4 GB volné paměti, zpracování a udržení tak velkého množství výsledků vyžaduje tolik paměti.

Omezení
-------

- Ve spisových značkách ESLP extrahovaných z dodaného dokumentu se vyskytují ve sloupci `columns.appno` hodnoty jako `25-Feb`, `Jan-55`... Zatím ponecháno tak.
- Některé spisové značky se vyskytují vícekrát, například `22 Cdo 2579/2006`, která má více různých dokumentů a tudíž i ve výsledném csv se objevuje třikrát s různými výsledky. Pro sloupce zda bylo podáno se bere v potaz přítomnost hodnoty v libovolném záznamu a datum rozhodnutí se bere poslední hodnota dle času).
-  
 