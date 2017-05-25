<?php

namespace JeroenDesloovere\VCard\tests;

// required to load
require_once __DIR__ . '/../vendor/autoload.php';

/*
 * This file is part of the VCard PHP Class from Jeroen Desloovere.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use JeroenDesloovere\VCard\VCard;

/**
 * This class will test our VCard PHP Class which can generate VCards.
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 */
class VCardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VCard
     */
    protected $vcard = null;

    /**
     * Data provider for testEmail()
     *
     * @return array
     */
    public function emailDataProvider()
    {
        return array(
            array(array('john@doe.com')),
            array(array('john@doe.com', 'WORK' => 'john@work.com')),
            array(array('WORK' => 'john@work.com', 'HOME' => 'john@home.com')),
            array(array('PREF;WORK' => 'john@work.com', 'HOME' => 'john@home.com')),
        );
    }

    /**
     * Set up before class
     *
     * @return void
     */
    public function setUp()
    {
        // set timezone
        date_default_timezone_set('Europe/Brussels');

        $this->vcard = new VCard();

        $this->firstName = 'Jeroen';
        $this->lastName = 'Desloovere';
        $this->additional = '&';
        $this->prefix = 'Mister';
        $this->suffix = 'Junior';
        
        $this->emailAddress1 = '';
        $this->emailAddress2 = '';

        $this->firstName2 = 'Ali';
        $this->lastName2 = 'ÖZSÜT';

        $this->firstName3 = 'Garçon';
        $this->lastName3 = 'Jéroèn';
    }

    /**
     * Tear down after class
     */
    public function tearDown()
    {
        $this->vcard = null;
    }

    public function testAddAddress()
    {
        $this->assertEquals($this->vcard, $this->vcard->addAddress());
    }

    public function testAddBirthday()
    {
        $this->assertEquals($this->vcard, $this->vcard->addBirthday(''));
    }

    public function testAddCompany()
    {
        $this->assertEquals($this->vcard, $this->vcard->addCompany(''));
    }

    public function testAddCategories()
    {
        $this->assertEquals($this->vcard, $this->vcard->addCategories([]));
    }

    public function testAddEmail()
    {
        $this->assertEquals($this->vcard, $this->vcard->addEmail($this->emailAddress1));
        $this->assertEquals($this->vcard, $this->vcard->addEmail($this->emailAddress2));
        $this->assertEquals(2, count($this->vcard->getProperties()));
    }

    public function testAddJobTitle()
    {
        $this->assertEquals($this->vcard, $this->vcard->addJobtitle(''));
    }

    public function testAddRole()
    {
        $this->assertEquals($this->vcard, $this->vcard->addRole(''));
    }

    public function testAddName()
    {
        $this->assertEquals($this->vcard, $this->vcard->addName(''));
    }

    public function testAddNote()
    {
        $this->assertEquals($this->vcard, $this->vcard->addNote(''));
    }

    public function testAddPhoneNumber()
    {
        $this->assertEquals($this->vcard, $this->vcard->addPhoneNumber(''));
        $this->assertEquals($this->vcard, $this->vcard->addPhoneNumber(''));
        $this->assertEquals(2, count($this->vcard->getProperties()));
    }

    public function testAddPhotoWithJpgPhoto()
    {
        $return = $this->vcard->addPhoto(__DIR__ . '/image.jpg', true);

        $this->assertEquals($this->vcard, $return);
    }

    public function testAddLogoWithJpgImage()
    {
        $return = $this->vcard->addLogo(__DIR__ . '/image.jpg', true);

        $this->assertEquals($this->vcard, $return);
    }

    public function testAddLogoWithJpgImageNoInclude()
    {
        $return = $this->vcard->addLogo(__DIR__ . '/image.jpg', false);

        $this->assertEquals($this->vcard, $return);
    }

    public function testAddUrl()
    {
        $this->assertEquals($this->vcard, $this->vcard->addUrl('1'));
        $this->assertEquals($this->vcard, $this->vcard->addUrl('2'));
        $this->assertEquals(2, count($this->vcard->getProperties()));
    }

    /**
     * Test adding photo with no value
     *
     * @expectedException JeroenDesloovere\VCard\VCardMediaException
     * @expectedExceptionMessage Nothing returned from URL.
     */
    public function testAddPhotoWithNoValue()
    {
        $this->vcard->addPhoto(__DIR__ . '/emptyfile', true);
    }

    /**
     * Test adding logo with no value
     *
     * @expectedException JeroenDesloovere\VCard\VCardMediaException
     * @t@github.com:jeroendesloovere/vcard.gitexpectedExceptionMessage Nothing returned from URL.
     */
    public function testAddLogoWithNoValue()
    {
        $this->vcard->addLogo(__DIR__ . '/emptyfile', true);
    }

    /**
     * Test adding photo with no photo
     *
     * @expectedException JeroenDesloovere\VCard\VCardMediaException
     * @expectedExceptionMessage Returned data aren't an image.
     */
    public function testAddPhotoWithNoPhoto()
    {
        $this->vcard->addPhoto(__DIR__ . '/wrongfile', true);
    }

    /**
     * Test adding logo with no image
     *
     * @expectedException JeroenDesloovere\VCard\VCardMediaException
     * @expectedExceptionMessage Returned data aren't an image.
     */
    public function testAddLogoWithNoImage()
    {
        $this->vcard->addLogo(__DIR__ . '/wrongfile', true);
    }

    /**
     * Test charset
     */
    public function testCharset()
    {
        $charset = 'ISO-8859-1';
        $this->vcard->setCharset($charset);
        $this->assertEquals($charset, $this->vcard->getCharset());
    }

    /**
     * Test Email
     *
     * @dataProvider emailDataProvider $emails
     */
    public function testEmail($emails = array())
    {
        foreach ($emails as $key => $email) {
            if (is_string($key)) {
                $this->vcard->addEmail($email, $key);
            } else {
                $this->vcard->addEmail($email);
            }
        }

        foreach ($emails as $key => $email) {
            if (is_string($key)) {
                $this->assertContains('EMAIL;INTERNET;' . $key . ':' . $email, $this->vcard->getOutput());
            } else {
                $this->assertContains('EMAIL;INTERNET:' . $email, $this->vcard->getOutput());
            }
        }
    }

    /**
     * Test first name and last name
     */
    public function testFirstNameAndLastName()
    {
        $this->vcard->addName(
            $this->lastName,
            $this->firstName
        );

        $this->assertEquals('jeroen-desloovere', $this->vcard->getFilename());
    }

    /**
     * Test full blown name
     */
    public function testFullBlownName()
    {
        $this->vcard->addName(
            $this->lastName,
            $this->firstName,
            $this->additional,
            $this->prefix,
            $this->suffix
        );

        $this->assertEquals('mister-jeroen-desloovere-junior', $this->vcard->getFilename());
    }

    /**
     * Test multiple birthdays
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleBirthdays()
    {
        $this->assertEquals($this->vcard, $this->vcard->addBirthday('1'));
        $this->assertEquals($this->vcard, $this->vcard->addBirthday('2'));
    }

    /**
     * Test multiple categories
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleCategories()
    {
        $this->assertEquals($this->vcard, $this->vcard->addCategories(['1']));
        $this->assertEquals($this->vcard, $this->vcard->addCategories(['2']));
    }

    /**
     * Test multiple companies
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleCompanies()
    {
        $this->assertEquals($this->vcard, $this->vcard->addCompany('1'));
        $this->assertEquals($this->vcard, $this->vcard->addCompany('2'));
    }

    /**
     * Test multiple job titles
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleJobtitles()
    {
        $this->assertEquals($this->vcard, $this->vcard->addJobtitle('1'));
        $this->assertEquals($this->vcard, $this->vcard->addJobtitle('2'));
    }

    /**
     * Test multiple roles
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleRoles()
    {
        $this->assertEquals($this->vcard, $this->vcard->addRole('1'));
        $this->assertEquals($this->vcard, $this->vcard->addRole('2'));
    }

    /**
     * Test multiple names
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleNames()
    {
        $this->assertEquals($this->vcard, $this->vcard->addName('1'));
        $this->assertEquals($this->vcard, $this->vcard->addName('2'));
    }

    /**
     * Test multiple notes
     *
     * @expectedException JeroenDesloovere\VCard\Exception
     */
    public function testMultipleNotes()
    {
        $this->assertEquals($this->vcard, $this->vcard->addNote('1'));
        $this->assertEquals($this->vcard, $this->vcard->addNote('2'));
    }

    /**
     * Test special first name and last name
     */
    public function testSpecialFirstNameAndLastName()
    {
        $this->vcard->addName(
            $this->lastName2,
            $this->firstName2
        );

        $this->assertEquals('ali-ozsut', $this->vcard->getFilename());
    }

    /**
     * Test special first name and last name
     */
    public function testSpecialFirstNameAndLastName2()
    {
        $this->vcard->addName(
            $this->lastName3,
            $this->firstName3
        );

        $this->assertEquals('garcon-jeroen', $this->vcard->getFilename());
    }
}
