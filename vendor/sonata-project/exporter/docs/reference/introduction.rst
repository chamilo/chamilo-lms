===============
Sonata exporter
===============

Sonata exporter allows you to convert large amount of data from a source to an output format (most generally to a file) by streaming it (hence avoiding too much memory consumption).

Usage
=====

.. code-block:: php

    <?php

    // ...
    // This can be any instance of SourceIteratorInterface
    $source = new ArraySourceIterator(array(/* your data */));
    // This could be any format supported
    $writer = new JsonWriter('php://output');

    $handler = Handler::create($source, $writer);
    $handler->export();
