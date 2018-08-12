# VCard PHP library
[![Latest Stable Version](http://img.shields.io/packagist/v/jeroendesloovere/vcard.svg)](https://packagist.org/packages/jeroendesloovere/vcard)
[![License](http://img.shields.io/badge/license-MIT-lightgrey.svg)](https://github.com/jeroendesloovere/vcard/blob/master/LICENSE)
[![Build Status](https://travis-ci.org/jeroendesloovere/vcard.svg?branch=master)](https://travis-ci.org/jeroendesloovere/vcard)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jeroendesloovere/vcard/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jeroendesloovere/vcard/?branch=master)

This VCard PHP library can generate a vCard with some data. When using an iOS device < iOS 8 it will export as a .ics file because iOS devices don't support the default .vcf files.

**NOTE**: We are working on a complete new version to work with vCard version 4.0, with extreme good code quality. [Check out the new version](https://github.com/jeroendesloovere/vcard/tree/2.0.0-dev)

## Usage

### Installation

```bash
composer require jeroendesloovere/vcard
```
> This will install the latest version of vcard with [Composer](https://getcomposer.org)

### Example

``` php
use JeroenDesloovere\VCard\VCard;

// define vcard
$vcard = new VCard();

// define variables
$lastname = 'Desloovere';
$firstname = 'Jeroen';
$additional = '';
$prefix = '';
$suffix = '';

// add personal data
$vcard->addName($lastname, $firstname, $additional, $prefix, $suffix);

// add work data
$vcard->addCompany('Siesqo');
$vcard->addJobtitle('Web Developer');
$vcard->addRole('Data Protection Officer');
$vcard->addEmail('info@jeroendesloovere.be');
$vcard->addPhoneNumber(1234121212, 'PREF;WORK');
$vcard->addPhoneNumber(123456789, 'WORK');
$vcard->addAddress(null, null, 'street', 'worktown', null, 'workpostcode', 'Belgium');
$vcard->addLabel('street, worktown, workpostcode Belgium');
$vcard->addURL('http://www.jeroendesloovere.be');

$vcard->addPhoto(__DIR__ . '/landscape.jpeg');

// return vcard as a string
//return $vcard->getOutput();

// return vcard as a download
return $vcard->download();

// save vcard on disk
//$vcard->setSavePath('/path/to/directory');
//$vcard->save();

```

> [View all examples](/examples/example.php) or check [the VCard class](/src/VCard.php).

### Parsing examples

The parser can either get passed a VCard string, like so:

```php
// load VCardParser classes
use JeroenDesloovere\VCard\VCardParser;

$parser = new VCardParser($vcardString);
echo $parser->getCardAtIndex(0)->fullname; // Prints the full name.
```

Or by using a factory method with a file name:

```php
$parser = VCardParser::parseFromFile('path/to/file.vcf');
echo $parser->getCardAtIndex(0)->fullname; // Prints the full name.
```
> [View the parsing example](/examples/example_parsing.php) or check the [the VCardParser class](/src/VCardParser.php) class.

**Support for frameworks**

I've created a Symfony Bundle: [VCard Bundle](https://github.com/jeroendesloovere/vcard-bundle)

Usage in for example: Laravel
```php
return Response::make(
    $this->vcard->getOutput(),
    200,
    $this->vcard->getHeaders(true)
);
```

## Documentation

The class is well documented inline. If you use a decent IDE you'll see that each method is documented with PHPDoc.

## Contributing

Contributions are **welcome** and will be fully **credited**.

### Pull Requests

> To add or update code

- **Coding Syntax** - Please keep the code syntax consistent with the rest of the package.
- **Add unit tests!** - Your patch won't be accepted if it doesn't have tests.
- **Document any change in behavior** - Make sure the README and any other relevant documentation are kept up-to-date.
- **Consider our release cycle** - We try to follow [semver](http://semver.org/). Randomly breaking public APIs is not an option.
- **Create topic branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.
- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

### Issues

> For bug reporting or code discussions.

More info on how to work with GitHub on help.github.com.

## Credits

- [Jeroen Desloovere](https://github.com/jeroendesloovere)
- [All Contributors](https://github.com/jeroendesloovere/vcard/contributors)

## License

The module is licensed under [MIT](./LICENSE.md). In short, this license allows you to do everything as long as the copyright statement stays present.
