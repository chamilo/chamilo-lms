Virtual Chamilo
===================

Authors : Valery Fremaux (valery.fremaux@gmail.com), Julio Montoya

Virtual chamilo is a feature that allows running several chamilo instances sharing the same
code base.

Version features
===================
This is a yet prototypal version that is not full featured in back-office tools.
At the moment, the setup of virtual clones still is a technical operation and has no
middle-office GUI. Development is in progress to offer a sufficient medium-level
administrability of the process.

How to setup
===================

You need :

1. Install the vchamilo package into the <chamiloroot>/plugin directory
2. Install the plugin in chamilo administration
3. Insert the virtualisation hook into the chamilo master configuration file:

```
<chamiloroot>/app/config/configuration.php
```

Insert the hook at the end of the file.

```
include_once $_configuration['root_sys'].'plugin/vchamilo/lib/Virtual.php';
vchamilo_hook_configuration($_configuration);
```

What you need for a virtual node is:
-------------

- a blank database copy of chamilo
- a dedicated course directory, that needs being accessible from chamilo installation root (directly, or using symlinks). the name
of this directory is free, as it will be mapped into the vchamilo record.
- a dedicated home page directory, that is located into <chamiloroot>/home directory and is named
as the chamilo instance domain name.
- a vchamilo record into the vchamilo table of the master installation. (the master installation is the install that refers to
the effective "configuration.php" information.
- an appropriate multiroot home root setup in the local chamilo instance

Prerequisites for VChamilo working nice
-------------

Multiple URL access must be enabled:

```
# in <chamiloroot>/main/inc/config/configuration.php
$_configuration['multiple_access_urls'] = true;
```

In the administration, you will need configure an adequate home root definition for the instance finding
the dedicated home directory. You just need editing the http://localhost default host, and give the real domain
name you are using.

Check you have the <chamilo>/home/<instancedomain> clone of the standard home directory.

Important note about file system permissions
-------------

Vchamilos will use several side-directories apart from the standard installation (dedicated courses,
dedicated home page). Check you set the adequate filesystem permissions (usually let the server write
in there) for them.
