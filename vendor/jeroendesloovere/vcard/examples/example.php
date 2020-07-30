<?php

/**
 * VCard generator test - can save to file or output as a download
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/VCard.php';

use JeroenDesloovere\VCard\VCard;

// define vcard
$vcard = new VCard();

// define variables
$firstname = 'Jeroen';
$lastname = 'Desloovere';
$additional = '';
$prefix = '';
$suffix = '';

// add personal data
$vcard->addName($lastname, $firstname, $additional, $prefix, $suffix);

// add work data
$vcard->addCompany('Siesqo');
$vcard->addJobtitle('Web Developer');
$vcard->addEmail('info@jeroendesloovere.be');
$vcard->addPhoneNumber(1234121212, 'PREF;WORK');
$vcard->addPhoneNumber(123456789, 'WORK');
$vcard->addAddress(null, null, 'street', 'worktown', null, 'workpostcode', 'Belgium');
$vcard->addURL('http://www.jeroendesloovere.be');

$vcard->addPhoto(__DIR__ . '/assets/landscape.jpeg');
//$vcard->addPhoto('https://raw.githubusercontent.com/jeroendesloovere/vcard/master/tests/image.jpg');

// return vcard as a string
//return $vcard->getOutput();

// return vcard as a download
return $vcard->download();

// echo message
echo 'A personal vCard is saved in this folder: ' . __DIR__;

// or

// save the card in file in the current folder
// return $vcard->save();

// echo message
// echo 'A personal vCard is saved in this folder: ' . __DIR__;
