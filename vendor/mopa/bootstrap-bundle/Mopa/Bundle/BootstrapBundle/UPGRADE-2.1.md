UPGRADE FROM 2.0 to 2.1
=======================

### General

Make sure you update your namespaces, we changed the Bundles namespace from `Mopa\BootstrapBundle` to `Mopa\Bundle\BootstrapBundle`.

You must change the namespace references in:

    * app/AppKernel.php
    * Your code making use of any MopaBootstrapBundle classes (e.g. Navbar, MenuBuilder, etc.)
    * Configuration referencing any classes (e.g. service definitions for menu, navbar, etc.)

For info about the branches read https://github.com/phiamo/MopaBootstrapBundle/wiki/Branches-&-Versions
If you dont want to care about the twitter/bootstrap dependency, please make sure your [composer.json](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/including_bootstrap.md) is correct


