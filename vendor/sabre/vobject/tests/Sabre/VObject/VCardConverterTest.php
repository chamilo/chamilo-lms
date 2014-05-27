<?php

namespace Sabre\VObject;

class VCardConverterTest extends \PHPUnit_Framework_TestCase {

    function testConvert30to40() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
ITEM1.TEL:+1 444 555 666
ITEM1.X-ABLABEL:CustomLabel
PHOTO;ENCODING=b;TYPE=JPEG,HOME:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
X-ABShowAs:COMPANY
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
ITEM1.TEL:+1 444 555 666
ITEM1.X-ABLABEL:CustomLabel
PHOTO;TYPE=HOME:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
KIND:org
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD40);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }

    function testConvert40to40() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif;base64,Zm9v
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD40);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }

    function testConvert21to40() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:2.1
N:Family;Johnson
FN:Johnson Family
TEL;HOME;VOICE:555-12345-345
ADR;HOME:;;100 Street Lane;Saubel Beach;ON;H0H0H0
LABEL;HOME;ENCODING=QUOTED-PRINTABLE:100 Street Lane=0D=0ASaubel Beach,
 ON H0H0H0
REV:20110731T040251Z
UID:12345678
END:VCARD
IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
N:Family;Johnson;;;
FN:Johnson Family
TEL;TYPE=HOME,VOICE:555-12345-345
ADR;TYPE=HOME:;;100 Street Lane;Saubel Beach;ON;H0H0H0;
REV:20110731T040251Z
UID:12345678
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD40);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }

    function testConvert30to30() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
PRODID:foo
FN;CHARSET=UTF-8:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;X-PARAM=FOO;ENCODING=b;TYPE=PNG:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD30);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }

    function testConvert40to30() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:4.0
PRODID:foo
FN:Steve
TEL;PREF=1;TYPE=HOME:+1 555 666 777
PHOTO:data:image/jpeg;base64,Zm9v
PHOTO:data:image/gif,foo
PHOTO;X-PARAM=FOO:data:image/png;base64,Zm9v
PHOTO:http://example.org/foo.png
KIND:org
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
FN:Steve
TEL;TYPE=PREF,HOME:+1 555 666 777
PHOTO;ENCODING=b;TYPE=JPEG:Zm9v
PHOTO;ENCODING=b;TYPE=GIF:Zm9v
PHOTO;ENCODING=b;TYPE=PNG;X-PARAM=FOO:Zm9v
PHOTO;VALUE=URI:http://example.org/foo.png
X-ABSHOWAS:COMPANY
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD30);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }

    function testConvertGroupCard() {

        $version = Version::VERSION;

        $input = <<<IN
BEGIN:VCARD
VERSION:3.0
PRODID:foo
X-ADDRESSBOOKSERVER-KIND:GROUP
END:VCARD

IN;

        $output = <<<OUT
BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
KIND:group
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD40);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

        $input = $output;
        $output = <<<OUT
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject {$version}//EN
X-ADDRESSBOOKSERVER-KIND:GROUP
END:VCARD

OUT;

        $vcard = \Sabre\VObject\Reader::read($input);
        $vcard = $vcard->convert(\Sabre\VObject\Document::VCARD30);

        $this->assertEquals(
            $output,
            str_replace("\r", "", $vcard->serialize())
        );

    }
}
