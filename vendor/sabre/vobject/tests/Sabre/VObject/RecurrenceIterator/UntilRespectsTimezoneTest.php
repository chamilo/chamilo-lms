<?php
namespace Sabre\VObject\RecurrenceIterator;

use Sabre\VObject\RecurrenceIterator;
use Sabre\VObject\Reader;

class RespectsTimezoneTest extends \PHPUnit_Framework_TestCase {

    public function testUntilBeginHasTimezone() {

		$filepath = realpath(__DIR__ . "/.");
		$event = Reader::read(file_get_contents($filepath . "/UntilRespectsTimezoneTest.ics"));

		$ri = new RecurrenceIterator($event, "10621-1440@ccbchurch.com");
		$this->assertEquals("America/New_York", $ri->until->getTimezone()->getName());
	}

	public function testUntilEndingInZIsUtc()
	{
		$ics_data = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.9//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:America/Chicago
BEGIN:DAYLIGHT
TZOFFSETFROM:-0600
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
DTSTART:20070311T020000
TZNAME:CDT
TZOFFSETTO:-0500
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0500
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
DTSTART:20071104T020000
TZNAME:CST
TZOFFSETTO:-0600
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20131216T214410Z
UID:D33B6D78-A214-4752-8659-9EE718D5AB8D
RRULE:FREQ=WEEKLY;INTERVAL=1;UNTIL=20131119T065959Z
DTEND;TZID=America/Chicago:20130923T203000
TRANSP:OPAQUE
SUMMARY:Test Financial Peace
DTSTART;TZID=America/Chicago:20130923T183000
DTSTAMP:20131216T215922Z
SEQUENCE:28
END:VEVENT
END:VCALENDAR
ICS;
		$event = Reader::read($ics_data);
		$ri = new RecurrenceIterator($event, "D33B6D78-A214-4752-8659-9EE718D5AB8D");
		$this->assertEquals("UTC", $ri->until->getTimezone()->getName());
	}
}

