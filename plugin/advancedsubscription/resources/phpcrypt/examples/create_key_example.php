<?php
/*
 * This is an example of how to use PHP_Crypt::createKey() to generate a random
 * key. PHP_Crypt::createKey() is a helper function to generate a random key.
 * You do not have to use createKey(), it was added to phpCrypt as a convenience,
 * you may create your own key as shown in other examples.
 *
 * There are 4 constants used with PHP_Crypt::createKey()
 *
 * PHP_Crypt::RAND - uses PHP's mt_rand() to generate an IV.
 * This is the default setting when no IV method is specified.
 *
 * PHP_Crypt::RAND_DEV_RAND - uses the Unix /dev/random random number generator.
 * More secure than PHP_Crypt::IV_RAND. Not available in Windows.
 *
 * PHP_Crypt::RAND_DEV_URAND - uses the Unix /dev/urandom random number generator.
 * More secure than PHP_Crypt::IV_RAND. Not Available for Windows.
 *
 * PHP_Crypt::RAND_WIN_COM - uses the PHP com_dotnet extension and is available only
 * on Windows. Requires the Microsoft CAPICOM SDK to be installed. View the README
 * file included with phpCrypt for more information
 */

error_reporting (E_ALL | E_STRICT);

include(dirname(__FILE__)."/../phpCrypt.php");
use PHP_Crypt\PHP_Crypt as PHP_Crypt;


/*
 * The example below uses Linux/Unix /dev/urandom to create a random
 * string of bytes. Because AES-128 uses a 128 bit (16 byte) key, we
 * request 16 bytes in the second parameter. Please read above for the
 * different constants used to create a key.
 *
 * WE CAN USE THE FOLLOWING METHODS OF CREATING AN KEY:
 * $key = PHP_Crypt::createKey(PHP_Crypt::RAND); // The default, uses PHP's mt_rand()
 * $key = PHP_Crypt::createKey(PHP_Crypt::RAND_DEV_RAND); // unix only, uses /dev/random
 * $key = PHP_Crypt::createKey(PHP_Crypt::RAND_DEV_URAND);// unix only, uses /dev/urandom
 * $key = PHP_Crypt::createKey(PHP_Crypt::RAND_WIN_COM);  // Windows only, uses the com_dotnet extension
 */
$key = PHP_Crypt::createKey(PHP_Crypt::RAND_DEV_URAND, 16);
$text = "This is my secret message.";

// now create the phpCrypt object and set the cipher to AES-128, with CTR mode
$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CTR);

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