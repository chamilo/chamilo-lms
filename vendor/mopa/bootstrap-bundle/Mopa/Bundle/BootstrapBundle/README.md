MopaBootstrapBundle
===================

MopaBootstrapBundle is a collection of code to integrate twitter's bootstrap
(http://twitter.github.com/bootstrap/) as easy as possible into your symfony2
(http://www.symfony.com) Project.

The Bootstrap 3 support is now mainly working. Check out the [Beta-3](https://github.com/phiamo/MopaBootstrapBundle/releases/tag/v3.0.0-beta3)

To use MopaBootstrapBundle and twitters Bootstrap 3 in your project add it via [composer](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/1-installation.md)

NOTICE:
-------

Recent BC breaks:

Navbar extensions have been replaced for menu extensions update your config:

```
mopa_bootstrap:
    navbar: ~
```

Gets

```
mopa_bootstrap:
    menu: ~
```


BS3 (master branch of this bundle) is nearly stable see [Beta-3](https://github.com/phiamo/MopaBootstrapBundle/releases/tag/v3.0.0-beta3)
BS2 (v2.3.x) is quite stable

BC breaking changes will probably not be ported to 2.3. 


Branches
--------

To use this bundle with boostrap 3 use the master branch:

``` json
{
    "require": {
        "mopa/bootstrap-bundle": "v3.0.0-beta2",
        "twbs/bootstrap": "v3.0.0"
    }
}
```

For bootstrap 2 use the v2.3.x branch:

```json
If you want to use bootstrap 2:

{
    "require": {
        "mopa/bootstrap-bundle": "2.3.x-dev",
        "twbs/bootstrap": "v2.3.2"
    }
}
```
To understand which versions are currently required have a look into `BRANCHES.md`
 
Documentation
-------------

The bulk of the documentation is stored in the `Resources/doc/index.md` file in this bundle
In any case, if something is not working as expected after a update:

* [READ the CHANGELOG!](https://github.com/phiamo/MopaBootstrapBundle/blob/master/CHANGELOG.md)

Recent BackwardsCompatibility breaking changes:

* c892cd9: Changed the way how navbars are created, read the [doc](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/4-navbar-generation.md) 
* a4b78d5: Added Version Detection for BS2 or BS3
* 5f1200f: Changed the widget_addon form parameter to use type (prepend/append) instead of append (true/false)

Live Show
---------

To see the bundle, its capabilities and some more doc just have a look on

[MopaBootstrapBundle Live](http://bootstrap.mohrenweiserpartner.de/mopa/bootstrap)

Additional Resources:

*  [MopaBootstrapSandboxBundle](http://github.com/phiamo/MopaBootstrapSandboxBundle) - Seperate live docs from code
*  [symfony-bootstrap](https://github.com/phiamo/symfony-bootstrap) is also available

Installation
------------

Installation instructions are located in the

* [master documentation](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/1-installation.md)

Included Features
-----------------

* Bootstrap Version detection via Composer Brigde 
* Twig Extensions and templates for use with symfony2 Form component
  * control your form either via the form builder or the template engine
  * control nearly every bootstrap2 form feature
  * javascript and twig blocks for dynamic collections
* A generic Navbar class to generate your Navbar outside the template
  * helpers for dropdowns, seperators, etc.
* A generic Tab class to Manage bootstrap tabbing
* twig templates for KnpPaginatorBundle (https://github.com/knplabs/KnpPaginatorBundle)
* twig templates for CraueFormFlowBundle (https://github.com/craue/CraueFormFlowBundle)
* twig template for KnpMenuBundle (https://github.com/KnpLabs/KnpMenuBundle)
  * icon support on menu links

Recently added Features
-----------------------
<h4>Tabs</h4>
We need to add more info here

<h4>Bootstrap 3</h4>

We now officially suport bootstrap3 in our master branch, 
The default is to determine Version by composer, this means, MopaBootstrapBundle tries to determine
which version you have installed, and configures itself to use it. This is done in a compiler pass and stored in local cache.
If for any reason this does not wor for you, you might want to set the 

We have several files seperated for bs2 and bs3 to be abled to support both e.g. forms:
 * https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/views/Form/fields_bs_2.html.twig
 * https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/views/Form/fields_bs_3.html.twig
or JS:
 * https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/public/js/eyecon-bootstrap-datepicker.js
 * https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/public/js/eyecon-bootstrap-3-datepicker.js

The bs3 feature set is not yet complete, but we hope that it gets soon, with your help!
E.g Form have some known issues, JS Collections, etc

<h4>Dynamic SubnavBars</h4>

To kick start your Navigation, the Navbar component got some face lift.
It got even easier to integrate also the dynamic sub navbar you might have seen in the twitter bootstrap docs.

To learn how to use this features read [Navbar docs (4-navbar-generation)](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/4-navbar-generation.md).

Also have a look into the [Sandbox](http://bootstrap.mohrenweiserpartner.de/mopa/bootstrap/navbar)

<h4>initializr</h4>

To make your base HTML5 even better and let you use good practices we added
to this bundle features from [HTML5 BoilerPlate Project](http://html5boilerplate.com/).
Integration was done using setup pregenerated with support of [Initializr](http://www.initializr.com/).
Actually we support __HTML5__ __BoilerPlate__ _v3.0.3_ with __Modernizr__ _v2.5.3_ and __Respond__.

To learn how to use this features read [Initializr docs (50-Initializr.md)](https://github.com/phiamo/MopaBootstrapBundle/blob/master/Resources/doc/50-initializr.md).

Warning
-------

> The branching strategy has been adapted to be more flexible:
> * The old branch for bootstrap 1.x (v1.x) still exists.
> * The v2.0.x (previously v2.x_sf2.0) branch is following Symfony 2.0.x with bootstrap 2.x
> * The master branch is following Symfony master with bootstrap 2.x
> * The master-crud branch is following Symfony master with bootstrap 2.x but still has the CRUD Generator, which depends on SensioGeneratorBundle

Translations
------------
If you use [KnpPaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle) with MopaBootstrapBundle, you can translate labels to your language.
To do this add new file

```sh
Resources/translations/pagination.[YOUR LOCALE CODE].yml
```

As example you have there Polish translation.

Contribute
----------
If you want to contribute your code to MopaBootstrapBundle please be sure that your PR's
are valid to Symfony2.1 Coding Standards. You can automatically fix your code for that
with [PHP-CS-Fixer](http://cs.sensiolabs.org) tool.

You can see who already contributed to this project on [Contributors](https://github.com/phiamo/MopaBootstrapBundle/contributors) page

License
-------

This bundle is under the MIT license. For more information, see the complete [LICENCE](LICENCE) file in the bundle.
