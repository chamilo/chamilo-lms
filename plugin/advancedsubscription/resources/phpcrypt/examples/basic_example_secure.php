<?php
/*
 * Another simple example, only this time using the more secure CBC mode
 * instead of ECB mode. CBC mode requires an IV as demonstrated below.
 */

error_reporting (E_ALL | E_STRICT);

include(dirname(__FILE__)."/../phpCrypt.php");
use PHP_Crypt\PHP_Crypt as PHP_Crypt;
use PHP_Crypt\Cipher as Cipher;

$text = "This is my secret message.";
$key = "^mY@TEst~Key_012"; // the key will be truncated if it's too long

// when the Cipher and Mode are ommitted, phpCrypt uses AES-128 and CBC mode
$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CBC);

$iv = $crypt->createIV();
$encrypt = $crypt->encrypt($text);

$crypt->IV($iv);
$decrypt = $crypt->decrypt($encrypt);

print "CIPHER: ".$crypt->cipherName()."\n";
print "MODE: ".$crypt->modeName()."\n";
print "PLAIN TEXT: $text\n";
print "PLAIN TEXT HEX: ".bin2hex($text)."\n";
print "ENCRYPTED HEX: ".bin2hex($encrypt)."\n";
print "DECRYPTED: $decrypt\n";
print "DECRYPTED HEX: ".bin2hex($decrypt)."\n";
?>