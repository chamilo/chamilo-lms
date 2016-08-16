========================
Integration with Symfony
========================

This library provides a Symfony bundle that you can register in the kernel of your application.
Doing so will make a ``sonata.exporter.exporter`` service available.
This service is able to build a streamed response directly usable in a Symfony controller
from a format, a filename, and a source.

Registering the bundle
----------------------

.. code-block:: php

    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // …
            new Exporter\Bridge\Symfony\Bundle\SonataExporterBundle();
        );

        // …

        return $bundles;
    }


The default writers
-------------------

Under the hood, the exporter uses one service for each available format.
Each service has its own parameters, documented below.

Each service parameter has a configuration countepart:

.. code-block:: yaml

    sonata_exporter:
        writers:
            some_format:
                some_setting: some_value

The CSV writer service
~~~~~~~~~~~~~~~~~~~~~~
This service can be configured throught the following parameters:

* ``sonata.exporter.writer.csv.filename``: defaults to ``php://output``
* ``sonata.exporter.writer.csv.delimiter``: defaults to ``,``
* ``sonata.exporter.writer.csv.enclosure``: defaults to ``"``
* ``sonata.exporter.writer.csv.escape``: defaults to ``\\``
* ``sonata.exporter.writer.csv.show_headers``: defaults to ``true``
* ``sonata.exporter.writer.csv.with_bom``: defaults to ``false``

The JSON writer service
~~~~~~~~~~~~~~~~~~~~~~~

Only the filename may be configured for this service:
``sonata.exporter.writer.json.filename``: defaults to ``php://output``

The XLS writer service
~~~~~~~~~~~~~~~~~~~~~~~

This service can be configured throught the following parameters:

* ``sonata.exporter.writer.xls.filename``: defaults to ``php://output``
* ``sonata.exporter.writer.xls.show_headers``: defaults to ``true``

The XML writer service
~~~~~~~~~~~~~~~~~~~~~~~

This service can be configured throught the following parameters:

* ``sonata.exporter.writer.xml.filename``: defaults to ``php://output``
* ``sonata.exporter.writer.xml.show_headers``: defaults to ``true``
* ``sonata.exporter.writer.xml.main_element``: defaults to ``datas``
* ``sonata.exporter.writer.xml.child_element``: defaults to ``data``

Adding a custom writer to the list
----------------------------------

If you want to add a custom writer to the list of writers supported by the exporter,
you simply need to tag your service,
which must implement ``Exporter\Writer\TypedWriterInterface``,
with the ``sonata.exporter.writer`` tag.

Configuring the default writers
-------------------------------

The default writers list can be altered through configuration:

.. code-block:: yaml

    sonata_exporter:
        exporter:
            default_writers:
                - csv
                - json
