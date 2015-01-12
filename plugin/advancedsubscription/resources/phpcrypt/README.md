phpCrypt - A PHP Encryption Library API
=====================================================

WHAT IS PHPCRYPT?
-----------------

phpCrypt is an encryption library written in PHP. It aims to implement
all major encryption ciphers, modes, padding methods, and other tools
used for encryption and decryption. phpCrypt does not rely on mCrypt,
PHP extentions, or PEAR libraries.

It currently support many widely used ciphers. In addition it
supports many popular encryption modes. There are also tools to implement
the different padding schemes, as well as multiple methods of creating an
Initialization Vector (IV) for modes that require one.

phpCrypt is developed by Ryan Gilfether <http://www.gilfether.com/phpcrypt>

phpCrypt is always in development. If you have any questions, found a bug,
or have a feature request, you can contact Ryan at
<http://www.gilfether.com/contact-ryan>

WHAT DOES IT WORK ON?
---------------------

phpCrypt version 0.x works with PHP 5.3 or later. It will run on any
32 or 64 bit operating system that has PHP available for it.

SUPPORTED ENCRYPTION CIPHERS, MODES, PADDING
--------------------------------------------

The list of supported encryption ciphers and modes is continually growing,
each new version of phpCrypt will add new ciphers or modes. The current list
of supported ciphers and modes are listed below:

Ciphers:

	3Way, AES, ARC4 (RC4), Blowfish, CAST-128, CAST-256, DES, Triple DES,
	Enigma, RC2, Rijndael, SimpleXOR, Skipjack, Vigenere

Modes:

	CBC, CFB, CTR, ECB, NCFB, NOFB, OFB, PCBC

Padding:

	ANSI X.923, ISO 10126, PKCS7, ISO/IEC 7816-4, Zero (NULL Byte)

DOCUMENTATION
-------------

This README file serves as the documentation. The phpCrypt website also
lists all the constants you need to select ciphers,	modes, padding, and
IV methods, which is availabe at http://www.gilfether.com/phpcrypt. In addition,
phpCrypt comes with an `examples` directory which has sample code to help get
you started.

phpCrypt is easy to use. An example of encrypting a string using AES-128
with CBC mode is demonstrated below. Also note that if you are new to encryption
using the AES-128 cipher with CBC mode is a simple yet secure way of getting
started.

	<?php
	include_once("/path/to/phpcrypt/phpCrypt.php");
	use PHP_Crypt\PHP_Crypt as PHP_Crypt;

	$data = "This is my secret message.";
	$key  = "MySecretKey01234";
	$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CBC);

	$iv = $crypt->createIV();
	$encrypt = $crypt->encrypt($data);

	$crypt->IV($iv);
	$decrypt = $crypt->decrypt($encrypt);
	?>

BYTE PADDING
------------

Some modes require a block of data to be padded if it's shorter than than
required block size of the cipher. For example DES encryption works on
8 byte blocks of data. If you a have a block of 6 bytes, then it
may need to be padded 2 bytes before it can be encrypted. Some modes don't
require the data to be padded.

By default phpCrypt uses NULL byte padding when necessary, keeping it
compatible with mCrypt.

phpCrypt has other padding methods available as well. You can specify
the padding you wish to use in two ways. The easiest method is to declare it
in the constructor like so:

	$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CBC, PHP_Crypt::PAD_PKCS7);

Optionally, you can also call the phpCrypt::padding() method to set the padding:

	$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CBC);
	$crypt->padding(PHP_Crypt::PAD_PKCS7);

In the event the padding is set for a mode that does not require padding, the
padding method is ignored. You can get a full list of padding methods available
at http://www.gilfether.com/phpcrypt.

You always have the option of padding the data yourself before sending it
through phpCrypt. In this case you do not need to worry about the phpCrypt
padding methods.

NOTE: NULL byte padding is not stripped off during decryption. This is left for
you to do. phpCrypt can not determine whether a null byte is part of the
original data or was added as padding.

CREATING INITIALIZATION VECTORS
-------------------------------

By default phpCrypt will use the PHP mt_rand() to generate random data used
to create the IV. This method is supported on all operating systems, however
there are more secure ways to generate random data depending on your
operating system

**LINUX & UNIX**

On Unix based systems, phpCrypt supports reading from `/dev/random` and
`/dev/urandom`. This can be done by passing one of the following constants
to phpCrypt::createIV():

	$iv = $crypt->createIV(PHP_Crypt::RAND_DEV_RAND);
	or
	$iv = $crypt->createIV(PHP_Crypt::RAND_DEV_URAND);

**MICROSOFT WINDOWS**

On Windows systems, you have the option to use the random number generator
found in the Microsoft CAPICOM SDK which is more secure. Before this will
work you must install the Microsoft CAPICOM SDK and enable the PHP `com_dotnet`
extension:

- Download CAPICOM from Microsoft at http://www.microsoft.com/en-us/download/details.aspx?id=25281
- Double click the MSI file you downloaded and follow the install directions
- Open a command prompt and register the DLL: `regsvr32 C:\Program Files\PATH TO\CAPICOM SDK\Lib\X86\capicom.dll`
- Now edit php.ini to enable the com_dotnet extension: `extension=php_com_dotnet.dll`
- If you are running PHP as an Apache module, restart Apache.

To use the Windows random number generator in CAPICOM you would call createIV() like so:

	$iv = $crypt->createIV(PHP_Crypt::RAND_WIN_COM);

**SUPPLYING YOUR OWN IV**

You have the option of creating an IV yourself without using phpCrypt::createIV().
If you wish to create your own IV or use one that was given to you for decryption,
set the IV using phpCrypt::IV() method:

	$crypt->IV($your_custom_iv);

The IV length must be equal to the block size used by the cipher. If the IV is not
the correct length phpCrypt will issue a PHP Warning and fail.

Not all modes require an IV. In the event the IV is set for a mode that does not
require an IV, the IV is ignored. You can get a full list of IV constants and a
list of modes that require an IV at http://www.gilfether.com/phpcrypt

CREATING A KEY
--------------

phpCrypt includes a helper function to create a string of random bytes to use as
a key. This can be used in place of setting your own key as shown in the examples
above. The PHP_Crypt::RAND constants are the same as the ones used for the createIV()
function. The second parameter indicates the number of random bytes to create.

	<?php
	$key = PHP_Crypt::createKey(PHP_Crypt::RAND, 16);
	$text = "This is my secret message."
	$crypt = new PHP_Crypt($key, PHP_Crypt::CIPHER_AES_128, PHP_Crypt::MODE_CBC);
	?>

See the examples directory for example code demonstrating the createKey() function.

The key length must be the size (in bytes) required by the cipher. phpCrypt does not
pad the key, it will issue a PHP warning and fail if the key size is too small.
Cipher key lengths are listed at http://www.gilfether.com/phpcrypt


FULL LIST OF CONSTANTS
----------------------

For the full list of constants available for Ciphers, Modes, Padding, and IV creation,
visit the phpCrypt website: http://www.gilfether.com/phpcrypt


GPL STUFF
---------

This file is part of phpCrypt

phpCrypt is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Please read the GPL file included in this distribution for the full license.
