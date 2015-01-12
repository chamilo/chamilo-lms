<?php

/*
 * This example requires the PHP mCrypt module. It compares
 * mCrypt DES encryption VS phpCrypt DES encryption. You change
 * the Encryption Cipher and Mode below to test any supported Ciphers.
 */

include(dirname(__FILE__)."/../phpCrypt.php");
use PHP_Crypt\PHP_Crypt as PHP_Crypt;
use PHP_Crypt\Cipher as Cipher;

$text = "This is my secret message.";
$key = "^mY@TEst~Key_012";


// MODIFY THE BELOW SETTINGS TO TEST PHPCRYPT AGAINST MCRYPT
$mcrypt_cipher = "rijndael-128";
$mcrypt_mode = "cbc";
$phpcrypt_cipher = PHP_Crypt::CIPHER_AES_128;
$phpcrypt_mode = PHP_Crypt::MODE_CBC;
// END MODIFYING


/****************************************************************
 * DO NOT EDIT BELOW THIS LINE
 ****************************************************************/

// MCRYPT SETUP
srand((double) microtime() * 1000000); //for sake of MCRYPT_RAND
$td = mcrypt_module_open($mcrypt_cipher, '',$mcrypt_mode, '');
$key = substr($key, 0, mcrypt_enc_get_key_size($td));
$iv_size = mcrypt_enc_get_iv_size($td);
$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

// PHPCRYPT SETUP
$phpcrypt = new PHP_Crypt($key, $phpcrypt_cipher, $phpcrypt_mode);


print "MCRYPT: $mcrypt_cipher - $mcrypt_mode\n";
print "PHPCRYPT: ".$phpcrypt->cipherName()." - ".$phpcrypt->modeName()."\n\n";


/**
 * ENCRYPT USING mCrypt
 * DECRYPT USING phpCrypt
 */

// MCRYPT: ENCRYPT
mcrypt_generic_init($td, $key, $iv);
$ts_start = microtime(true);
$encrypt = mcrypt_generic($td, $text);
$m_time = number_format((microtime(true) - $ts_start), 5);
mcrypt_generic_deinit($td);

// PHPCRYPT: DECRYPT
$phpcrypt->IV($iv);
$ts_start = microtime(true);
$decrypt = $phpcrypt->decrypt($encrypt);
$p_time = number_format((microtime(true) - $ts_start), 5);

// OUTPUT
print "MCRYPT ENCRYPTED (HEX):   ".bin2hex($encrypt)." (length=".strlen($encrypt).", time=$m_time)\n";
print "PHPCRYPT DECRYPTED:       $decrypt (length=".strlen($decrypt).", time=$p_time)\n";
print "PHPCRYPT DECRYPTED (HEX): ".bin2hex($decrypt)."\n";


print "\n\n";


/**
 * ENCRYPT USING phpCrypt
 * DECRYPT USING mCrypt
 */

// PHPCRYPT: ENCRYPT
$phpcrypt->IV($iv);
$ts_start = microtime(true);
$encrypt = $phpcrypt->encrypt($text);
$p_time = number_format((microtime(true) - $ts_start), 5);


// MCRYPT: DECRYPT
mcrypt_generic_init($td, $key, $iv);
$ts_start = microtime(true);
$decrypt = mdecrypt_generic($td, $encrypt);
$m_time = number_format((microtime(true) - $ts_start), 5);
mcrypt_generic_deinit($td);

// OUTPUT
print "PHPCRYPT ENCRYPTED (HEX): ".bin2hex($encrypt)." (length=".strlen($encrypt).", time=$p_time)\n";
print "MCRYPT DECRYPTED:         $decrypt (length=".strlen($decrypt).", time=$m_time)\n";
print "MCRYPT DECRYPTED (HEX):   ".bin2hex($decrypt)."\n";

// close mcrypt
mcrypt_module_close($td);
?>