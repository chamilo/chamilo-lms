ChangeLog
=========

3.2.2 (2014-05-07)
------------------
* Minor tweak in unittests to make it run on PHP 5.5.12. Json-prettifying
  slightly changed which caused the test to fail.


3.2.1 (2014-05-03)
------------------
* Minor tweak to make the unittests run with the latest hhvm on travis.
* Updated timezone definitions.
* Updated copyright links to point to http://sabre.io/


3.2.0 (2014-04-02)
------------------

* Now hhvm compatible!
* The validator can now detect a _lot_ more problems. Many rules for both
  iCalendar and vCard were added.
* Added: bin/generate_vcards, a utility to generate random vcards for testing
  purposes. Patches are welcome to add more data.
* Updated: Windows timezone mapping to latest version from unicode.org
* Changed: The timezone maps are now loaded in from external files, in
  lib/Sabre/VObject/timezonedata.
* Added: Fixing badly encoded URL's from google contacts vcards.
* Fixed: Issue #68. Couldn't decode properties ending in a colon.
* Fixed: Issue #72. RecurrenceIterator should respect timezone in the UNTIL
  clause.
* Fixed: Issue #67. BYMONTH limit on DAILY recurrences.
* Fixed: Issue #26. Return a more descriptive error when coming across broken
  BYDAY rules.
* Fixed: Issue #28. Incorrect timezone detection for some timezones.
* Fixed: Issue #70. Casting a parameter with a null value to string would fail.
* Added: Support for rfc6715 and rfc6474.
* Added: Support for DateTime objects in the VCard DATE-AND-OR-TIME property.
* Added: UUIDUtil, for easily creating unique identifiers.
* Fixed: Issue #83. Creating new VALUE=DATE objects using php's DateTime.
* Fixed: Issue #86. Don't go into an infinite loop when php errors are
  disabled and an invalid file is read.

3.1.4 (2014-03-30)
------------------
* Fixed: Issue #87: Several compatibility fixes related to timezone handling
  changes in PHP 5.5.10.


3.1.3 (2013-10-02)
------------------

* Fixed: Support from properties from draft-daboo-valarm-extensions-04. Issue
  #56.
* Fixed: Issue #54. Parsing a stream of multiple vcards separated by more than
  one newline. Thanks @Vedmak for the patch.
* Fixed: Serializing vcard 2.1 parameters with no name caused a literal '1' to
  be inserted.
* Added: VCardConverter removed properties that are no longer supported in vCard
  4.0.
* Added: vCards with a minimum number of values (such as N), but don't have that
  many, are now automatically padded with empty components.
* Added: The vCard validator now also checks for a minimum number of components,
  and has the ability to repair these.
* Added: Some support for vCard 2.1 in the VCard converter, to upgrade to vCard
  3.0 or 4.0.
* Fixed: Issue 60 Use Document::$componentMap when instantiating the top-level
  VCalendar and VCard components.
* Fixed: Issue 62: Parsing iCalendar parameters with no value.
* Added: --forgiving option to vobject utility.
* Fixed: Compound properties such as ADR were not correctly split up in vCard
  2.1 quoted printable-encoded properties.
* Fixed: Issue 64: Encoding of binary properties of converted vCards. Thanks
  @DominikTo for the patch.


3.1.2 (2013-08-13)
------------------

* Fixed: Setting correct property group on VCard conversion


3.1.1 (2013-08-02)
------------------

* Fixed: Issue #53. A regression in RecurrenceIterator.


3.1.0 (2013-07-27)
------------------

* Added: bad-ass new cli debugging utility (in bin/vobject).
* Added: jCal and jCard parser.
* Fixed: URI properties should not escape ; and ,.
* Fixed: VCard 4 documents now correctly use URI as a default value-type for
  PHOTO and others. BINARY no longer exists in vCard 4.
* Added: Utility to convert between 2.1, 3.0 and 4.0 vCards.
* Added: You can now add() multiple parameters to a property in one call.
* Added: Parameter::has() for easily checking if a parameter value exists.
* Added: VCard::preferred() to find a preferred email, phone number, etc for a
  contact.
* Changed: All $duration properties are now public.
* Added: A few validators for iCalendar documents.
* Fixed: Issue #50. RecurrenceIterator gives incorrect result when exception
  events are out of order in the iCalendar file.
* Fixed: Issue #48. Overridden events in the recurrence iterator that were past
  the UNTIL date were ignored.
* Added: getDuration for DURATION values such as TRIGGER. Thanks to
  @SimonSimCity.
* Fixed: Issue #52. vCard 2.1 parameters with no name may lose values if there's
  more than 1. Thanks to @Vedmak.


3.0.0 (2013-06-21)
------------------

* Fixed: includes.php file was still broken. Our tool to generate it had some
  bugs.


3.0.0-beta4 (2013-06-21)
------------------------

* Fixed: includes.php was no longer up to date.


3.0.0-beta3 (2013-06-17)
------------------------

* Added: OPTION_FORGIVING now also allows slashes in property names.
* Fixed: DateTimeParser no longer fails on dates with years < 1000 & > 4999
* Fixed: Issue 36: Workaround for the recurrenceiterator and caldav events with
  a missing base event.
* Fixed: jCard encoding of TIME properties.
* Fixed: jCal encoding of REQUEST-STATUS, GEO and PERIOD values.


3.0.0-beta2 (2013-06-10)
------------------------

* Fixed: Corrected includes.php file.
* Fixed: vCard date-time parser supported extended-format dates as well.
* Changed: Properties have been moved to an ICalendar or VCard directory.
* Fixed: Couldn't parse vCard 3 extended format dates and times.
* Fixed: Couldn't export jCard DATE values correctly.
* Fixed: Recursive loop in ICalendar\DateTime property.


3.0.0-beta1 (2013-06-07)
------------------------

* Added: jsonSerialize() for creating jCal and jCard documents.
* Added: helper method to parse vCard dates and times.
* Added: Specialized classes for FLOAT, LANGUAGE-TAG, TIME, TIMESTAMP,
  DATE-AND-OR-TIME, CAL-ADDRESS, UNKNOWN and UTC-OFFSET properties.
* Removed: CommaSeparatedText property. Now included into Text.
* Fixed: Multiple parameters with the same name are now correctly encoded.
* Fixed: Parameter values containing a comma are now enclosed in double-quotes.
* Fixed: Iterating parameter values should now fully work as expected.
* Fixed: Support for vCard 2.1 nameless parameters.
* Changed: $valueMap, $componentMap and $propertyMap now all use fully-qualified
  class names, so they are actually overridable.
* Fixed: Updating DATE-TIME to DATE values now behaves like expected.


3.0.0-alpha4 (2013-05-31)
-------------------------

* Added: It's now possible to send parser options to the splitter classes.
* Added: A few tweaks to improve component and property creation.


3.0.0-alpha3 (2013-05-13)
-------------------------

* Changed: propertyMap, valueMap and componentMap are now static properties.
* Changed: Component::remove() will throw an exception when trying to a node
  that's not a child of said component.
* Added: Splitter objects are now faster, line numbers are accurately reported
  and use less memory.
* Added: MimeDir parser can now continue parsing with the same stream buffer.
* Fixed: vobjectvalidate.php is operational again.
* Fixed: \r is properly stripped in text values.
* Fixed: QUOTED-PRINTABLE is now correctly encoded as well as encoded, for
  vCards 2.1.
* Fixed: Parser assumes vCard 2.1, if no version was supplied.


3.0.0-alpha2 (2013-05-22)
-------------------------

* Fixed: vCard URL properties were referencing a non-existant class.


3.0.0-alpha1 (2013-05-21)
-------------------------

* Fixed: Now correctly dealing with escaping of properties. This solves the
  problem with double-backslashes where they don't belong.
* Added: Easy support for properties with more than one value, using setParts
  and getParts.
* Added: Support for broken 2.1 vCards produced by microsoft.
* Added: Automatically decoding quoted-printable values.
* Added: Automatically decoding base64 values.
* Added: Decoding RFC6868 parameter values (uses ^ as an escape character).
* Added: Fancy new MimeDir parser that can also parse streams.
* Added: Automatically mapping many, many properties to a property-class with
  specialized API's.
* Added: remove() method for easily removing properties and sub-components
  components.
* Changed: Components, Properties and Parameters can no longer be created with
  Component::create, Property::create and Parameter::create. They must instead
  be created through the root component. (A VCalendar or VCard object).
* Changed: API for DateTime properties has slightly changed.
* Changed: the ->value property is now protected everywhere. Use getParts() and
  getValue() instead.
* BC Break: No support for mac newlines (\r). Never came across these anyway.
* Added: add() method to the Property class.
* Added: It's now possible to easy set multi-value properties as arrays.
* Added: When setting date-time properties you can just pass PHP's DateTime
  object.
* Added: New components automatically get a bunch of default properties, such as
  VERSION and CALSCALE.
* Added: You can add new sub-components much quicker with the magic setters, and
  add() method.


2.1.4 (2014-03-30)
------------------

* Fixed: Issue #87: Several compatibility fixes related to timezone handling
  changes in PHP 5.5.10.


2.1.3 (2013-10-02)
------------------

* Fixed: Issue #55. \r must be stripped from property values.
* Fixed: Issue #65. Putting quotes around parameter values that contain a colon.


2.1.2 (2013-08-02)
------------------

* Fixed: Issue #53. A regression in RecurrenceIterator.


2.1.1 (2013-07-27)
------------------

* Fixed: Issue #50. RecurrenceIterator gives incorrect result when exception
  events are out of order in the iCalendar file.
* Fixed: Issue #48. Overridden events in the recurrence iterator that were past
  the UNTIL date were ignored.


2.1.0 (2013-06-17)
------------------

* This version is fully backwards compatible with 2.0.\*. However, it contains a
  few new API's that mimic the VObject 3 API. This allows it to be used a
  'bridge' version. Specifically, this new version exists so SabreDAV 1.7 and
  1.8 can run with both the 2 and 3 versions of this library.
* Added: Property\DateTime::hasTime().
* Added: Property\MultiDateTime::hasTime().
* Added: Property::getValue().
* Added: Document class.
* Added: Document::createComponent and Document::createProperty.
* Added: Parameter::getValue().


2.0.7 (2013-03-05)
------------------

* Fixed: Microsoft re-uses their magic numbers for different timezones,
  specifically id 2 for both Sarajevo and Lisbon). A workaround was added to
  deal with this.


2.0.6 (2013-02-17)
------------------

* Fixed: The reader now properly parses parameters without a value.


2.0.5 (2012-11-05)
------------------

* Fixed: The FreeBusyGenerator is now properly using the factory methods for
  creation of components and properties.


2.0.4 (2012-11-02)
------------------

* Added: Known Lotus Notes / Domino timezone id's.


2.0.3 (2012-10-29)
------------------

* Added: Support for 'GMT+????' format in TZID's.
* Added: Support for formats like SystemV/EST5EDT in TZID's.
* Fixed: RecurrenceIterator now repairs recurrence rules where UNTIL < DTSTART.
* Added: Support for BYHOUR in FREQ=DAILY (@hollodk).
* Added: Support for BYHOUR and BYDAY in FREQ=WEEKLY.


2.0.2 (2012-10-06)
------------------

* Added: includes.php file, to load the entire library in one go.
* Fixed: A problem with determining alarm triggers for TODO's.


2.0.1 (2012-09-22)
------------------

* Removed: Element class. It wasn't used.
* Added: Basic validation and repair methods for broken input data.
* Fixed: RecurrenceIterator could infinitely loop when an INTERVAL of 0 was
  specified.
* Added: A cli script that can validate and automatically repair vcards and
  iCalendar objects.
* Added: A new 'Compound' property, that can automatically split up parts for
  properties such as N, ADR, ORG and CATEGORIES.
* Added: Splitter classes, that can split up large objects (such as exports)
  into individual objects (thanks @DominikTO and @armin-hackmann).
* Added: VFREEBUSY component, which allows easily checking wether timeslots are
  available.
* Added: The Reader class now has a 'FORGIVING' option, which allows it to parse
  properties with incorrect characters in the name (at this time, it just allows
  underscores).
* Added: Also added the 'IGNORE_INVALID_LINES' option, to completely disregard
  any invalid lines.
* Fixed: A bug in Windows timezone-id mappings for times created in Greenlands
  timezone (sorry Greenlanders! I do care!).
* Fixed: DTEND was not generated correctly for VFREEBUSY reports.
* Fixed: Parser is at least 25% faster with real-world data.


2.0.0 (2012-08-08)
------------------

* VObject is now a separate project from SabreDAV. See the SabreDAV changelog
  for version information before 2.0.
* New: VObject library now uses PHP 5.3 namespaces.
* New: It's possible to specify lists of parameters when constructing
  properties.
* New: made it easier to construct the FreeBusyGenerator.
