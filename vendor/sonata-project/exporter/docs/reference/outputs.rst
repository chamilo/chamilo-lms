=======
Outputs
=======

Several output formatters are supported:

* CSV
* GSA Feed (Google Search Appliance)
* In Memory (for test purposes mostly)
* JSON
* Sitemap
* XML
* Excel XML
* XLS (MS Excel)

You may also create your own. To do so, simply create a class that implements the ``Exporter\Writer\WriterInterface``,
or better, if you know what ``Content-Type`` header should be used along with
your output and what format it produces, ``TypedWriterInterface``.
