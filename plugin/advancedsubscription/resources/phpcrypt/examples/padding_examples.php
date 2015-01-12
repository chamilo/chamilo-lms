<?php
/*
 * Demonstrate how to use different padding schemes. By default phpCrypt
 * uses Zero Padding (NULL padding) when a padding method is not
 * explicitly set.
 *
 * In the example below, we are using DES encryption with ECB mode. DES
 * encryption encrypts and decrypts 64 bit (8 bytes) blocks of data. The
 * Sample string below is 26 bytes long. It needs to be padded to 32 bytes
 * for it to be encrypted by DES. There are different methods of padding a
 * string which are demonstrated below
 *
 * Note that the string is not always padded, some modes handle encryption
 * in such a way that padding the string is not necessary. ECB mode is one of the
 * modes that requires the string to be padded before encryption. Padding is
 * ignored when using a Mode that does not require it.
 */

error_reporting (E_ALL | E_STRICT);

include(dirname(__FILE__)."/../phpCrypt.php");
use PHP_Crypt\PHP_Crypt as PHP_Crypt;

$text = "This is my secret message.";
$key = "^mY@TEst";

/*
 * Cipher: DES
 * Mode: ECB
 */

/*
 * Padding is required by some modes to force the data being encrypted to be the
 * correct length expected by the cipher. Not all modes require padding. In the
 * case where a padding type is set but not required by the mode, the padding
 * parameter is ignored.
 *
 * By default phpCrypt uses PHP_Crypt::PAD_ZERO (zero padding). Notice how the
 * PHP_Crypt::PAD_ZERO is ommitted from the constructor call below.
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB);
 *
 * Note that NULL padding (PHP_CRYPT::PAD_ZERO) does not remove the null bytes
 * from the end of the decrypted data. This is standard practice and mimcs how
 * mcrypt and other encryption libraries handle NULL padding. This is because
 * an encryption library does not know if the null bytes are legitimate or were
 * added as padding. This is left for the programmer to determine. All other
 * Padding Types will remove the extra padding during decryption.
 *
 * Below we will use PKCS7 padding for the example:
 */
$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_PKCS7);


/*
 * Other examples of setting the Padding Type is demonstrated below
 *
 * // pad using ZERO PADDING (NULL PADDING)
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_ZERO);
 *
 * // pad using ANSI X.923
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_ANSI_X923);
 *
 * // pad using ISO 10126
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_ISO_10126);
 *
 * // pad using PKCS7
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_PKCS7);
 *
 * // pad using ISO 7816.4
 * $crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_DES, PHP_Crypt::MODE_ECB, PHP_Crypt::PAD_ISO_7816_4);
 *
 * // ALTERNATIVLEY YOU CAN ALSO SET THE PADDING AFTER CALLING THE CONSTRUCTOR:
 * $crypt->padding(PHP_Crypt::PAD_ANSI_X923);
 */


//$iv = $crypt->createIV(); // ECB Mode does not require an IV
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