<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

class VCalendarTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider expandData
     */
    public function testExpand($input, $output) {

        $vcal = VObject\Reader::read($input);

        $vcal->expand(
            new \DateTime('2011-12-01'),
            new \DateTime('2011-12-31')
        );

        // This will normalize the output
        $output = VObject\Reader::read($output)->serialize();

        $this->assertEquals($output, $vcal->serialize());

    }

    public function expandData() {

        $tests = array();

        // No data
        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
END:VCALENDAR
';

        $output = $input;
        $tests[] = array($input,$output);


        // Simple events
        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla
SUMMARY:InExpand
DTSTART;VALUE=DATE:20111202
END:VEVENT
BEGIN:VEVENT
UID:bla2
SUMMARY:NotInExpand
DTSTART;VALUE=DATE:20120101
END:VEVENT
END:VCALENDAR
';

        $output = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla
SUMMARY:InExpand
DTSTART;VALUE=DATE:20111202
END:VEVENT
END:VCALENDAR
';

        $tests[] = array($input, $output);

        // Removing timezone info
        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VTIMEZONE
TZID:Europe/Paris
END:VTIMEZONE
BEGIN:VEVENT
UID:bla4
SUMMARY:RemoveTZ info
DTSTART;TZID=Europe/Paris:20111203T130102
END:VEVENT
END:VCALENDAR
';

        $output = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla4
SUMMARY:RemoveTZ info
DTSTART:20111203T120102Z
END:VEVENT
END:VCALENDAR
';

        $tests[] = array($input, $output);

        // Recurrence rule
        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111125T120000Z
DTEND:20111125T130000Z
RRULE:FREQ=WEEKLY
END:VEVENT
END:VCALENDAR
';

        $output = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111202T120000Z
DTEND:20111202T130000Z
RECURRENCE-ID:20111202T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111209T120000Z
DTEND:20111209T130000Z
RECURRENCE-ID:20111209T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111216T120000Z
DTEND:20111216T130000Z
RECURRENCE-ID:20111216T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111223T120000Z
DTEND:20111223T130000Z
RECURRENCE-ID:20111223T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule
DTSTART:20111230T120000Z
DTEND:20111230T130000Z
RECURRENCE-ID:20111230T120000Z
END:VEVENT
END:VCALENDAR
';

        $tests[] = array($input, $output);

        // Recurrence rule + override
        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule2
DTSTART:20111125T120000Z
DTEND:20111125T130000Z
RRULE:FREQ=WEEKLY
END:VEVENT
BEGIN:VEVENT
UID:bla6
RECURRENCE-ID:20111209T120000Z
DTSTART:20111209T140000Z
DTEND:20111209T150000Z
SUMMARY:Override!
END:VEVENT
END:VCALENDAR
';

        $output = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule2
DTSTART:20111202T120000Z
DTEND:20111202T130000Z
RECURRENCE-ID:20111202T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
RECURRENCE-ID:20111209T120000Z
DTSTART:20111209T140000Z
DTEND:20111209T150000Z
SUMMARY:Override!
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule2
DTSTART:20111216T120000Z
DTEND:20111216T130000Z
RECURRENCE-ID:20111216T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule2
DTSTART:20111223T120000Z
DTEND:20111223T130000Z
RECURRENCE-ID:20111223T120000Z
END:VEVENT
BEGIN:VEVENT
UID:bla6
SUMMARY:Testing RRule2
DTSTART:20111230T120000Z
DTEND:20111230T130000Z
RECURRENCE-ID:20111230T120000Z
END:VEVENT
END:VCALENDAR
';

        $tests[] = array($input, $output);
        return $tests;

    }

    /**
     * @expectedException LogicException
     */
    public function testBrokenEventExpand() {

        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
RRULE:FREQ=WEEKLY
DTSTART;VALUE=DATE:20111202
END:VEVENT
END:VCALENDAR
';
        $vcal = VObject\Reader::read($input);
        $vcal->expand(
            new \DateTime('2011-12-01'),
            new \DateTime('2011-12-31')
        );

    }

    function testGetDocumentType() {

        $vcard = new VCalendar();
        $vcard->VERSION = '2.0';
        $this->assertEquals(VCalendar::ICALENDAR20, $vcard->getDocumentType());

    }

    function testValidateCorrect() {

        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
PRODID:foo
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
DTSTAMP:20140122T233226Z
UID:foo
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(array(), $vcal->validate(), 'Got an error');

    }

    function testValidateNoVersion() {

        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
PRODID:foo
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateWrongVersion() {

        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:3.0
PRODID:foo
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateNoProdId() {

        $input = 'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateDoubleCalScale() {

        $input = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
CALSCALE:GREGORIAN
CALSCALE:GREGORIAN
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateDoubleMethod() {

        $input = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
METHOD:REQUEST
METHOD:REQUEST
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateTwoMasterEvents() {

        $input = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
METHOD:REQUEST
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(1, count($vcal->validate()));

    }

    function testValidateOneMasterEvent() {

        $input = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:foo
METHOD:REQUEST
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
END:VEVENT
BEGIN:VEVENT
DTSTART;VALUE=DATE:20111202
UID:foo
DTSTAMP:20140122T234434Z
RECURRENCE-ID;VALUE=DATE:20111202
END:VEVENT
END:VCALENDAR
';

        $vcal = VObject\Reader::read($input);
        $this->assertEquals(0, count($vcal->validate()));

    }
}
