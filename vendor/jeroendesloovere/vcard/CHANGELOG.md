1.2.2 (2015-11-30)
--
Improvements:
* Probably a fix for UTF-8 in outlook 2010

1.2.1 (2015-07-23)
--
Improvements:
* You can now set the charset by using $vcard->setCharset('ISO-8859-1');

1.2.0 (2015-06-01)
--
Improvements:
* You can now add some properties multiple times: email, address, phone number and url

1.1.11 (2015-05-21)
--
Improvements:
* addMedia updated to check if correct file type.

1.1.10 (2015-05-20)
--
Improvements:
* Multiple mailaddresses allowed.
* Chaining integrated to add functions.

1.1.9 (2015-04-21)
--
Improvements:
* getHeaders() is now separate function. So frameworks can use this.
* Fix for iOS 8 to return vcard without calendar wrapper.

1.1.8 (2015-03-09)
--
Bugfixes:
* Fixes $include/$exclude, #27
* Fixes special characters by using external transliterator class.

1.1.7 (2015-03-05)
--
Improvements:
* Images should per default be included in our vcard.

Bugfixes:
* Fix for the ->get() which didn't return anything.

1.1.6 (2015-02-24)
--
Improvements:
* Add line folding, check #16
* Refactored some functions.
* PSR-2-code-styling applied.
* PHPCS applied.

Bugfixes:
* Fix fetching PHOTO elements.

1.1.5 (2015-01-30)
--
Bugfixes:
* Updated the deprecated MIME detection, check #16

1.1.4 (2015-01-22)
--
Improvements:
* PHPUnit Tests added
* Exception is now a separate class.
* Renamed the variables $firstName and $lastName

Bugfixes:
* Filename: Fixed double underscores when no "additional" field was given.

1.1.3 (2015-01-22)
--
Bugfixes:
* Name: Double space when no "additional" field is given. Fixes #8
