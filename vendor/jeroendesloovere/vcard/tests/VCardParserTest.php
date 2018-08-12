<?php

namespace JeroenDesloovere\VCard\tests;

use JeroenDesloovere\VCard\VCard;
use JeroenDesloovere\VCard\VCardParser;

/**
 * Unit tests for our VCard parser.
 */
class VCardParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException OutOfBoundsException
     */
    public function testOutOfRangeException()
    {
        $parser = new VCardParser('');
        $parser->getCardAtIndex(2);
    }

    public function testSimpleVcard()
    {
        $vcard = new VCard();
        $vcard->addName("Desloovere", "Jeroen");
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->firstname, "Jeroen");
        $this->assertEquals($parser->getCardAtIndex(0)->lastname, "Desloovere");
        $this->assertEquals($parser->getCardAtIndex(0)->fullname, "Jeroen Desloovere");
    }

    public function testBDay()
    {
        $vcard = new VCard();
        $vcard->addBirthday('31-12-2015');
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->birthday->format('Y-m-d'), '2015-12-31');
    }

    public function testAddress()
    {
        $vcard = new VCard();
        $vcard->addAddress(
            "Lorem Corp.",
            "(extended info)",
            "54th Ipsum Street",
            "PHPsville",
            "Guacamole",
            "01158",
            "Gitland",
            'WORK;POSTAL'
        );
        $vcard->addAddress(
            "Jeroen Desloovere",
            "(extended info, again)",
            "25th Some Address",
            "Townsville",
            "Area 51",
            "045784",
            "Europe (is a country, right?)",
            'WORK;PERSONAL'
        );
        $vcard->addAddress(
            "Georges Desloovere",
            "(extended info, again, again)",
            "26th Some Address",
            "Townsville-South",
            "Area 51B",
            "04554",
            "Europe (no, it isn't)",
            'WORK;PERSONAL'
        );
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->address['WORK;POSTAL'][0], (object) array(
            'name' => "Lorem Corp.",
            'extended' => "(extended info)",
            'street' => "54th Ipsum Street",
            'city' => "PHPsville",
            'region' => "Guacamole",
            'zip' => "01158",
            'country' => "Gitland",
        ));
        $this->assertEquals($parser->getCardAtIndex(0)->address['WORK;PERSONAL'][0], (object) array(
            'name' => "Jeroen Desloovere",
            'extended' => "(extended info, again)",
            'street' => "25th Some Address",
            'city' => "Townsville",
            'region' => "Area 51",
            'zip' => "045784",
            'country' => "Europe (is a country, right?)",
        ));
        $this->assertEquals($parser->getCardAtIndex(0)->address['WORK;PERSONAL'][1], (object) array(
            'name' => "Georges Desloovere",
            'extended' => "(extended info, again, again)",
            'street' => "26th Some Address",
            'city' => "Townsville-South",
            'region' => "Area 51B",
            'zip' => "04554",
            'country' => "Europe (no, it isn't)",
        ));
    }

    public function testPhone()
    {
        $vcard = new VCard();
        $vcard->addPhoneNumber('0984456123');
        $vcard->addPhoneNumber('2015123487', 'WORK');
        $vcard->addPhoneNumber('4875446578', 'WORK');
        $vcard->addPhoneNumber('9875445464', 'PREF;WORK;VOICE');
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->phone['default'][0], '0984456123');
        $this->assertEquals($parser->getCardAtIndex(0)->phone['WORK'][0], '2015123487');
        $this->assertEquals($parser->getCardAtIndex(0)->phone['WORK'][1], '4875446578');
        $this->assertEquals($parser->getCardAtIndex(0)->phone['PREF;WORK;VOICE'][0], '9875445464');
    }

    public function testEmail()
    {
        $vcard = new VCard();
        $vcard->addEmail('some@email.com');
        $vcard->addEmail('site@corp.net', 'WORK');
        $vcard->addEmail('site.corp@corp.net', 'WORK');
        $vcard->addEmail('support@info.info', 'PREF;WORK');
        $parser = new VCardParser($vcard->buildVCard());
        // The VCard class uses a default type of "INTERNET", so we do not test
        // against the "default" key.
        $this->assertEquals($parser->getCardAtIndex(0)->email['INTERNET'][0], 'some@email.com');
        $this->assertEquals($parser->getCardAtIndex(0)->email['INTERNET;WORK'][0], 'site@corp.net');
        $this->assertEquals($parser->getCardAtIndex(0)->email['INTERNET;WORK'][1], 'site.corp@corp.net');
        $this->assertEquals($parser->getCardAtIndex(0)->email['INTERNET;PREF;WORK'][0], 'support@info.info');
    }

    public function testOrganization()
    {
        $vcard = new VCard();
        $vcard->addCompany('Lorem Corp.');
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->organization, 'Lorem Corp.');
    }

    public function testUrl()
    {
        $vcard = new VCard();
        $vcard->addUrl('http://www.jeroendesloovere.be');
        $vcard->addUrl('http://home.example.com', 'HOME');
        $vcard->addUrl('http://work1.example.com', 'PREF;WORK');
        $vcard->addUrl('http://work2.example.com', 'PREF;WORK');
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->url['default'][0], 'http://www.jeroendesloovere.be');
        $this->assertEquals($parser->getCardAtIndex(0)->url['HOME'][0], 'http://home.example.com');
        $this->assertEquals($parser->getCardAtIndex(0)->url['PREF;WORK'][0], 'http://work1.example.com');
        $this->assertEquals($parser->getCardAtIndex(0)->url['PREF;WORK'][1], 'http://work2.example.com');
    }

    public function testNote()
    {
        $vcard = new VCard();
        $vcard->addNote('This is a testnote');
        $parser = new VCardParser($vcard->buildVCard());

        $vcardMultiline = new VCard();
        $vcardMultiline->addNote("This is a multiline note\nNew line content!\r\nLine 2");
        $parserMultiline = new VCardParser($vcardMultiline->buildVCard());

        $this->assertEquals($parser->getCardAtIndex(0)->note, 'This is a testnote');
        $this->assertEquals(nl2br($parserMultiline->getCardAtIndex(0)->note), nl2br("This is a multiline note" . PHP_EOL . "New line content!" . PHP_EOL . "Line 2"));
    }

    public function testCategories()
    {
        $vcard = new VCard();
        $vcard->addCategories([
            'Category 1',
            'cat-2',
            'another long category!'
        ]);
        $parser = new VCardParser($vcard->buildVCard());

        $this->assertEquals($parser->getCardAtIndex(0)->categories[0], 'Category 1');
        $this->assertEquals($parser->getCardAtIndex(0)->categories[1], 'cat-2');
        $this->assertEquals($parser->getCardAtIndex(0)->categories[2], 'another long category!');
    }

    public function testTitle()
    {
        $vcard = new VCard();
        $vcard->addJobtitle('Ninja');
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->title, 'Ninja');
    }

    public function testLogo()
    {
        $image = __DIR__ . '/image.jpg';
        $imageUrl = 'https://raw.githubusercontent.com/jeroendesloovere/vcard/master/tests/image.jpg';

        $vcard = new VCard();
        $vcard->addLogo($image, true);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->rawLogo, file_get_contents($image));

        $vcard = new VCard();
        $vcard->addLogo($image, false);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->logo, __DIR__ . '/image.jpg');

        $vcard = new VCard();
        $vcard->addLogo($imageUrl, false);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->logo, $imageUrl);
    }

    public function testPhoto()
    {
        $image = __DIR__ . '/image.jpg';
        $imageUrl = 'https://raw.githubusercontent.com/jeroendesloovere/vcard/master/tests/image.jpg';

        $vcard = new VCard();
        $vcard->addPhoto($image, true);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->rawPhoto, file_get_contents($image));

        $vcard = new VCard();
        $vcard->addPhoto($image, false);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->photo, __DIR__ . '/image.jpg');

        $vcard = new VCard();
        $vcard->addPhoto($imageUrl, false);
        $parser = new VCardParser($vcard->buildVCard());
        $this->assertEquals($parser->getCardAtIndex(0)->photo, $imageUrl);
    }

    public function testVcardDB()
    {
        $db = '';
        $vcard = new VCard();
        $vcard->addName("Desloovere", "Jeroen");
        $db .= $vcard->buildVCard();

        $vcard = new VCard();
        $vcard->addName("Lorem", "Ipsum");
        $db .= $vcard->buildVCard();

        $parser = new VCardParser($db);
        $this->assertEquals($parser->getCardAtIndex(0)->fullname, "Jeroen Desloovere");
        $this->assertEquals($parser->getCardAtIndex(1)->fullname, "Ipsum Lorem");
    }

    public function testIteration()
    {
        // Prepare a VCard DB.
        $db = '';
        $vcard = new VCard();
        $vcard->addName("Desloovere", "Jeroen");
        $db .= $vcard->buildVCard();

        $vcard = new VCard();
        $vcard->addName("Lorem", "Ipsum");
        $db .= $vcard->buildVCard();

        $parser = new VCardParser($db);
        foreach ($parser as $i => $card) {
            $this->assertEquals($card->fullname, $i == 0 ? "Jeroen Desloovere" : "Ipsum Lorem");
        }
    }

    public function testFromFile()
    {
        $parser = VCardParser::parseFromFile(__DIR__ . '/example.vcf');
        // Use this opportunity to test fetching all cards directly.
        $cards = $parser->getCards();
        $this->assertEquals($cards[0]->firstname, "Jeroen");
        $this->assertEquals($cards[0]->lastname, "Desloovere");
        $this->assertEquals($cards[0]->fullname, "Jeroen Desloovere");
        // Check the parsing of grouped items as well, which are present in the
        // example file.
        $this->assertEquals($cards[0]->url['default'][0], 'http://www.jeroendesloovere.be');
        $this->assertEquals($cards[0]->email['INTERNET'][0], 'site@example.com');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFileNotFound()
    {
        $parser = VCardParser::parseFromFile(__DIR__ . '/does-not-exist.vcf');
    }
}
