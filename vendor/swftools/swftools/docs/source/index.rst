PHP SwfTools
============

.. toctree::
   :maxdepth: 2

Introduction
------------

PHP SwfTools is a lib for manipulating PDF files and SWF files with SWFTools
(http://www.swftools.org/).

Installation
------------

We rely on `composer <http://getcomposer.org/>`_ to use this library. If you do
no still use composer for your project, you can start with this ``composer.json``
at the root of your project:

.. code-block:: json

    {
        "require": {
            "php-ffmpeg/php-ffmpeg": "master"
        }
    }

Install composer :

.. code-block:: bash

    # Install composer
    curl -s http://getcomposer.org/installer | php
    # Upgrade your install
    php composer.phar install

You now just have to autoload the library to use it :

.. code-block:: php

    <?php
    require 'vendor/autoload.php';

This is a very short intro to composer.
If you ever experience an issue or want to know more about composer,
you will find help on their  website
`http://getcomposer.org/ <http://getcomposer.org/>`_.

Basic Usage
-----------

Recipes
-------

API is quite simple : Two objects should be mainly used and cover the needs :
FlashFile and PDFFile.

FlashFile
*********

The following code extracts various data from a Swf file :

.. code-block:: php

    <?php

    use SwfTools\FlashFile;
    use SwfTools\Configuration;

    $flash = new FlashFile('file.swf');

    // Renders the flash file with swfrender
    $flash->render('output.jpg');

    // List all embedded objects
    // Available object types are one EmbeddedObject::TYPE_* constants
    foreach($flash->listEmbeddedObjects() as $embeddedObject)
    {
        $id = $embeddedObject->getId();

        // Extract an embedded object
        $flash->extractEmbedded($id, sprintf('output%d.jpg', $id));
    }

    // Extract the first image obecjt found (jpeg or png)
    $flash->extractFirstImage('output.jpg');


PDFFile
*******

The following code converts a PDF to a Swf file :

.. code-block:: php

    <?php

    use SwfTools\PDFFile;
    use SwfTools\Configuration;

    $pdf = new PDFFile('file.pdf');
    $pdf->toSwf('target.swf');

Using Custom Configuration
--------------------------

PHP SwfTools autodetects SWFTools binaries on most \*nix systems with the command
**where**. If you would like to provide your own configuration, you can do it :

.. code-block:: php

    <?php

    use SwfTools\PDFFile;
    use SwfTools\Configuration;

    $PDF = new PDFFile('file.pdf');
    /* Will autodetect pdf2swf location */
    $PDF->toSwf('destination.swf');

    $PDF = new PDFFile('file.pdf', new Configuration('pdf2swf'=>'/my/custom/path/to/pdf2swf'));
    /* Will use /my/custom/path/to/pdf2swf */
    $PDF->toSwf('destination.swf');

Process Timeout
---------------

PHPSwfTools uses underlying processes to execute commands. You can set a timeout
to prevent these processes to run more than a defined duration.

To disable timeout, set it to `0` (default value).

.. code-block:: php

    $configuration = new SwfTools\Configuration(array(
        'timeout' => 0
    ));

    $file = new SwfTools\FlashFile('Animation.swf', $configuration);

Handling Exceptions
-------------------

PHP-SwfTools throws 3 different types of exception :

- ``\SwfTools\Exception\BinaryNotFoundException`` is thrown when no acceptable
  pdf2text binary is found.
- ``\SwfTools\Exception\InvalidArgumentException`` is thrown when an invalid
  argument (file, format, ...) is provided
- ``\SwfTools\Exception\RuntimeException`` which extends SPL RuntimeException

All these Exception implements ``\SwfTools\Exception\Exception`` so you can catch
any of these exceptions by catching this exception interface.

Report a bug
------------

If you experience an issue, please report it in our
`issue tracker <https://github.com/alchemy-fr/PHPSwftools/issues>`_. Before
reporting an issue, please be sure that it is not already reported by browsing
open issues.

When reporting, please give us information to reproduce it by giving your
platform (Linux / MacOS / Windows) and its version, the version of PHP you use
(the output of ``php --version``), the version of swftools you use
(the output of ``swfextract --version``).

Ask for a feature
-----------------

We would be glad you ask for a feature ! Feel free to add a feature request in
the `issues manager <https://github.com/alchemy-fr/PHPSwftools/issues>`_ on GitHub !

Contribute
----------

You find a bug and resolved it ? You added a feature and want to share ? You
found a typo in this doc and fixed it ? Feel free to send a
`Pull Request <http://help.github.com/send-pull-requests/>`_ on GitHub, we will
be glad to merge your code.

Run tests
---------

PHP-SwfTools relies on `PHPUnit <http://www.phpunit.de/manual/current/en/>`_ for
unit tests. To run tests on your system, ensure you have PHPUnit installed,
and, at the root of PHP-SwfTools, execute it :

.. code-block:: bash

    phpunit

About
-----

PHP-SwfTools has been written by Romain Neutron @ `Alchemy <http://alchemy.fr/>`_
for `Phraseanet <https://github.com/alchemy-fr/Phraseanet>`_, our DAM software.
Try it, it's awesome !

License
-------

PHP-SwfTools is licensed under the `MIT License <http://opensource.org/licenses/MIT>`_