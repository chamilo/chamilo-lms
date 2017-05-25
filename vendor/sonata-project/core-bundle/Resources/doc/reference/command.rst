Command
=======

You can create a dump file with entity schema information. For each entity, you will get
table name and the mapping between fields and column names (related entities fields won't be displayed).

.. code-block:: bash

    Usage:
     sonata:core:dump-doctrine-metadata -r "/ProductBundle/" -f /tmp/dump.json

    Options:
     --filename (-f)       If filename is specified, result will be dumped into this file under json format.
     --entity-name (-E)    If entity-name is set, dump will only contain the specified entity and all its extended classes.
     --regex (-r)          If regex is set, dump will only contain entities which name match the pattern.
     --help (-h)           Display this help message.
     --quiet (-q)          Do not output any message.
     --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug.
     --version (-V)        Display this application version.
     --ansi                Force ANSI output.
     --no-ansi             Disable ANSI output.
     --no-interaction (-n) Do not ask any interactive question.
     --shell (-s)          Launch the shell.
     --process-isolation   Launch commands from shell as a separate process.
     --env (-e)            The Environment name. (default: "dev")
     --no-debug            Switches off debug mode.
