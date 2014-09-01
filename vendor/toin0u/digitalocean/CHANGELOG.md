CHANGELOG
=========

1.4.2 (2014-01-14)
------------------

* Updated doc
* Removeed enable and disable backup to droplets (removed from the API)
* Updated CLI droplet show command shows backups and snapshots numbers
* Added private_ip_address property to droplets
* [BC break] Renamed 'TransfertCommand' to 'TransferCommand' (@geevcookie)
* Updated CLI outputs with TableHelper (@geevcookie)
* Added hhvm to travis-ci
* Added SensioLabsInsight badge


1.4.1 (2013-11-03)
------------------

* SansioLabsInsight compliant

1.4.0 (2013-09-26)
------------------

* Updated doc with events examples
* Added events to CLI + tests - fix #29
* Added events + tests - fix #28
* Updated doc with symfony2 integration
* Updated doc with laravel integration

1.3.1 (2013-08-18)
------------------

* Added: possibility to set an adapter to instantiated object
* Fixed: droplet api changes + CLI + tests + doc
* Updated: doc - add integration with frameworks

1.3.0 (2013-08-18)
------------------

* Fixed: Domain test
* Updated: doc
* Added: CLI domains:records:destroy + tests
* Added: CLI domains:records:edit + tests
* Added: CLI domains:record:show + tests
* Added: CLI domains:records:add + tests
* Added: CLI domains:records:all + tests
* Fixed: Domains new record test - domain id is already in the api url
* Added: CLI domains:destroy + tests
* Added: CLI domains:add + tests
* Fixed: DigitalOcean's API url in doc
* Fixed: Domains::show test
* Added: CLI domains:show + tests
* Added: CLI domains:all + tests
* Added: domains and records + tests - fix #23
* Fixed: SSH key destroy test

1.2.1 (2013-08-15)
------------------

* Fixed: CLI when you do not have images + test
* Fixed: CLI where there is no droplets + test
* Fixed: CLI when there is no ssh keys + test
* Fixed: catch errors when the API returns an old error object response - fix #27

1.2.0 (2013-08-06)
------------------

* Updated: composer.json
* Updated: sshkey edit test
* Added: typehint to id variables
* Fixed: mock call in images destroy test
* Added: images transfert to CLI + tests
* Added: images transfert + tests - fix #24
* Added: droplets rename to CLI - tests
* Added: droplets rename action + tests - fix #25
* Added: ssh edit in CLI + test
* Added: ssh key edit + test - fix #12
* Fixed: query procesing
* Added: poser.pugx.org badges
* Removed: stillmaintained.com

1.1.3 (2013-04-26)
------------------

* Added: HttpAdapter library
* Fixed: socket test
* Updated: socket user-agent
* Updated: add command help text in CLI
* Fixed: travis-ci
* Added: bitdeli.com
* Added: coveralls.io
* Updated: Contribution doc
* Fixed: digitalocean abstract class

1.1.2 (2013-03-22)
------------------

* Added: droplet create interactively test
* Fixed: creating a new ssh key is more verbose
* Fixed: ssh key argument name to ssh_pub_key

1.1.1 (2013-03-22)
------------------

* Added: terminal screencast about create-interactively command
* Fixed: tests
* Fixed: droplet create command
* Fixed: images and ssh keys destroy command
* Added: create interactively a new droplet in CLI

1.1.0 (2013-03-21)
------------------

* Added: distribution credential file as a constant
* Added: edit command to CLI - fix #13
* Added: ask confirmation on droplet reboot command - fix #21
* Added: ask confirmation on droplet rebuild command - fix #20
* Added: ask confirmation on droplet reset root password command - fix #19
* Added: ask confirmation on droplet resize command - fix #18
* Added: ask confirmation on droplet restore command - fix #17
* Added: ask confirmation on droplet shutdown command - fix #16
* Added: ask confirmation on ssh key destroy command - fix #14
* Added: ask confirmation on image destroy command - fix #14
* Added: ask confirmation on droplet destroy command - fix #14
* Fixed: doc about credential file and removed screenshot

1.0.0 (2013-03-19)
------------------

* Added: tests to ssh keys and command CLI
* Added: tests to images CLI
* Added: tests to droplets CLI
* Added: tests to regions and sizes CLI
* Updated: doc with credentials option
* Updated: doc about CLI
* Added: CLI

0.2.0 (2013-03-18)
------------------

* Fixed: tests
* Added: Credential class + test [BC break]
* Added: cURL adapter is the default one
* Updated: doc with exemples - fix #11
* Added: check when adding ssh keys to new droplets

0.1.1 (2013-03-15)
------------------

* Fixed: class names more consistant [BC break]
* Fixed: adapter test filenames
* Fixed: credits
* Updated: composer.json and doc
* Added: SocketAdapter + test - fix #9
* Added: ZendAdapter + test - fix #10
* Added: GuzzleAdapter + test - fix #7
* Added: BuzzAdapter + test - fix #8

0.1.0 (2013-03-15)
------------------

* Updated: doc with exemples
* Refactored: api url construction
* Refactored: tests
* Updated: composer.json keywords
* Added: SSH Keys API + test - fix #3
* Fixed: Size alias
* Added: Sizes API + test - fix #4
* Added: Images API + test - fix #2
* Updated: regions() method to all()
* Refactored: use ReflectionMethod() in tests
* Added: Regions API + test - fix #1
* Refactored: constructor, buildQuery and processQuery
* Refactored: droplet actions
* Fixed: composer.json name convention
* Added: travis-ci and stillmaintained
* Initial import
