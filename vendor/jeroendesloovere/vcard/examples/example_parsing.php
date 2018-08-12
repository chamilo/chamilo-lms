<?php

/**
 * VCardParser test - can parse bundled VCF file into CSV
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/VCardParser.php';

// load VCardParser classes
use JeroenDesloovere\VCard\VCardParser;

$pathToVCardExample = __DIR__ . '/assets/contacts.vcf';
$parser = VCardParser::parseFromFile($pathToVCardExample);

foreach($parser as $vcard) {
    $lastname = $vcard->lastname;
    $firstname = $vcard->firstname;
    $birthday = $vcard->birthday->format('Y-m-d');
    
    printf("\"%s\",\"%s\",\"%s\"", $lastname, $firstname, $birthday);
    
    echo PHP_EOL;
}
