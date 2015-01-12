<?php
/*
 * This example show the simplest way to use PHPCrypt. By default
 * PHPCrypt uses AES-128, ECB mode, and Null byte padding.
 * Using ECB mode is not the most secure mode, and should only be
 * used if simplicity is preferred over security. If security is
 * of importance, please choose one of the more secure modes such
 * as CBC or CTR
 */

error_reporting (E_ALL | E_STRICT);

include(dirname(__FILE__)."/../phpCrypt.php");
use PHP_Crypt\PHP_Crypt as PHP_Crypt;
use PHP_Crypt\Cipher as Cipher;

$text = "This is my secret message.";
$key = "^mY@TEst~Key_012"; // the key will be truncated if it's too long

// when the Cipher and Mode are ommitted, phpCrypt uses AES-128 and ECB mode
$crypt = new PHP_Crypt($key);

$encrypt = $crypt->encrypt($text);
$decrypt = $crypt->decrypt($encrypt);

print "CIPHER: ".$crypt->cipherName()."\n";
print "MODE: ".$crypt->modeName()."\n";
print "PLAIN TEXT: $text\n";
print "PLAIN TEXT HEX: ".bin2hex($text)."\n";
print "ENCRYPTED HEX: ".bin2hex($encrypt)."\n";
print "DECRYPTED: $decrypt\n";
print "DECRYPTED HEX: ".bin2hex($decrypt)."\n";
?>