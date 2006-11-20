for more information open documentation/index.html in your favorite browser.

=======================================
 Dokeos 1.8 README
=======================================

Dokeos is an elearning and course management web application,
and is free software (GNU GPL). It's translated into 30 languages,
SCORM compliant, and light and flexible.

Dokeos supports many different kinds of learning and collaboration activities.
Teachers/trainers can create, manage and publish their courses through the web.
Students/trainees can follow courses, read content or participate actively
through groups, forums, chat.

Technically, Dokeos is a web application written in PHP that stores data in a 
MySQL database. Users access it using a web browser.

If you would like to know more or help develop this software, please visit
our homepage at http://www.dokeos.com/


INSTALLING DOKEOS
=================
Please read the installation_guide.html for instructions on how to
install Dokeos.


DOCUMENTATION
=============
We have some short guides available inside this release package,
in the documentation folder.

More detailed documentation can be found at
http://www.dokeos.com/documentation.php contains

- Student manual
- Teacher manual
- And much more

Developers will find all the info they need on our wiki:
http://www.dokeos.com/wiki/


SCORM
=====

Dokeos imports Scorm 1.2 and 1.3. compliant learning contents.
For more information on Scorm normalisation, see http://www.adlnet.org


LICENSE
=======

Dokeos is distributed under the GNU General Public license (GPL).
See the file license.txt for all details.


PORTABILITY
===========

Dokeos is an AMP software. This means it should work on any platform running Apache
+ MySQL + PHP. It is then supposed to work on the following Operating Systems :

	Linux
	Windows (98, Me, NT4, 2000, XP)
	Unix
	Mac OS X

We tested it on
- Fedora, Mandrake, Red Hat Enterprise Server,
- Windows XP, Windows 2000
- Mac OS X 10.3

Email functions remain silent on systems where there is no mail sending software
(Sendmail, Postfix, Hamster...), which is the case by default on a Windows machine.


INTEROPERABILITY
================

Dokeos imports Scorm compliant learning contents. It imports then "On the shelve"
contents from many companies : NETg, Skillsoft, Explio, Microsoft, Macromedia, etc.

Admin interface imports users through CSV and XML. You can create a CSV file from
a list of users in MS Excel. OpenOffice can export to both CSV and XML formats.
Many database management systems, like Oracle, SAP, Access, SQL-Server, LDAP ...
export to CSV and/or XML.

Dokeos includes a LDAP module that allows admin to deactivate MySQL authentication
and replace it by connection to a LDAP directory.

Client side, Dokeos runs on any browser : Firefox, MS Internet Explorer (5.0+), Netscape (4.7+),
Mozilla (1.2+), Safari, Opera, ...


PHP Configuration
=================
 
Dokeos needs PHP version 4.3.2 or later (4.x versions), configured with the
following modules : mysql, zlib, preg, xml. PHP versions 5 or later are not
supported by Dokes yet. It will accept the following settings :

safe_mode				= Off
magic_quotes_gpc		= On
magic_quotes_runtime	= Off
short_open_tag			= On

The backticks charachter (`) inserted inside some of the Dokeos SQL queries doesn't
work with MySQL versions previous to 3.23.6

In some sections (Backup, Documents, Learning Path..), Dokeos requires the "zlib"
library on windows servers or the unzip shell software on Linux/Unix servers.


SECURITY
==============

In Dokeos 1.6, security and interoperability have been improved. Protection
for documents has improved, and courses have more accessibility options.
Password encryption is enabled by default. The php.ini setting 
"register globals" does not have be on anymore.

NEW FEATURES IN DOKEOS 1.8
===========================
Release notes - summary


NEW FEATURES IN DOKEOS 1.6
===========================
Release notes - summary
http://www.dokeos.com/wiki/index.php/Dokeos_1.6_release_notes

Complete roadmap
http://www.dokeos.com/wiki/index.php/Roadmap_1.6

- Campus home page can be edited online
- Improved translations, made with the new Dokeos translation tool
- Language switch - when you enter the portal, you can choose your language.
- Who is online: a list of users who are logged in, you can click to see
  their pictures and portfolio, or click to talk to them through our built-in web chat tool.
- Learning path - import and export of SCORM packages, improved layout,
  prerequisites based on score in tests
- Agenda - many new options, e.g. every user can add personal agenda items.
- Document tool - many new options, improved layout, improved HtmlArea
- Security - PHP register globals setting don't need to be on anymore
- Administration section - all functions are easier to access, you can
  configure many options through the web interface instead of by digging through the code.
- Improved course management - completely rewritten course import/export
  functions, easily copy content from one course to another
- Plugins and modularity - new system to add plugins to Dokeos more easily
- API libraries - our function libraries have been expanded and improved
- Interoperability: support for SCORM import/export, XML import/export for
  some features, IEEE LOM Metadata support in documents and groups, import of
  Hotpotatoes, connection with QuestionMark (this last one will be available
  as plugin).

NEW FEATURES IN DOKEOS 1.5
===========================
- Learning path : Scorm content import tool
- WYSIWYG editor : create content on the fly
- Table of contents : structure content on the fly
- Dropbox : peer2peer content sharing manegement
- Links categories : structure links catalogue
- New navigation : one click to tool
- Events since my last visit : be informed of what has changed since your last login
- My agenda : synthetic weekly view of all the events related to you
- Add a picture to my profile : see who is who
- Security : privacy and anti-cracking protection
- 5 more languages : russian, catalan, vietnamese, brazilian, thai and a revised chinese
- New chat tool : real-time textual discussion
- Audio & video conference : real-time live broadcasting of events + textual interaction with ore than 200 people.
- Announcements to some users or some groups only
- Time-based learning management : add resources to time line in Agenda
- Audio & video in Tests tool : create listening comprehensions, situation-based questions on the fly
- Forum thread/flat view : see discussions in more detail
- Forum email notification : get an email when your forum topic is active
- language revision : dokeos vocabulary has been generalised to be adapted
  to different types of organisations and not only universities


CREDITS
=======

See CREDITS.txt file


=========================================================================
Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
Mail: info@dokeos.com
September 2006
================================== END ===================================
