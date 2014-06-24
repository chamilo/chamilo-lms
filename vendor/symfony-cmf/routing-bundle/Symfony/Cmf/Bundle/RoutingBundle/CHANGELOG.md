Changelog
=========

1.2.0
-----

Release 1.2.0

1.2.0-RC2
---------

* **2014-04-23**: changed ``cmf_routing.dynamic.route_collection_limit`` default to ``0``

* **2014-04-15**: Removed the unused ContentAwareGenerator class from the
  bundle. Since 1.1 the one from the routing component was used.

* **2014-04-14**: DynamicRouter no longer implements the ContainerAwareInterface
* **2014-04-11**: drop Symfony 2.2 compatibility

1.2.0-RC1
---------

* **2014-03-29**: [Multilang] Added some options to support locale matching
  without separate routes per locale. See the new configuration options
  `match_implicit_locale` and `auto_locale_pattern`.

* **2014-03-29**: [PHPCR] The route provider can now load Routes from more than
  path in PHPCR. The configuration option `route_basepath` is renamed to
  `route_basepaths` and accepts a list of base paths. See the changelog of
  the SimpleCmsBundle as the main impact is on that side.

* **2014-03-29**: Route document and entity constructor changed. The options
  are no longer mapped separately but as route options. See UPGRADE-1.2.md for
  instructions.

* **2014-03-26**: [ORM] Applied the cleanup for the PHPCR provider to the ORM
  provider now: If the route matched a pattern with a format extension, the
  format extension is no longer set as route a default.

* **2014-03-25**: [PHPCR] setParent() and getParent() are now deprecated.
  Use setParentDocument() and getParentDocument() instead.
  Moreover, you should now enable the ChildExtension from the CoreBundle.

* **2014-03-23**: [PHPCR] urls can now be generated from the route
  uuid as route name as well, in addition to the repository path.

* **2013-12-23**: add support for ChainRouter::getRouteCollection(), added new
  config setting ``cmf_routing.dynamic.route_collection_limit``

* **2013-11-28**: [BC BREAK for xml configuration] the alias attribute of the
  <template-by-class> is renamed to class in the bundle configuration.

1.1.0
-----

* **2013-10-14**: The route enhancers now have a non-default priority so that
  the order is defined and you can add your own enhancers.

1.1.0-RC8
---------

* **2013-10-08**: The idPrefix is now used as a filter in getRouteByName() and
  getRoutesByNames() in the PHPCR RouteProvider. This means its no longer
  possible to get routes that are not children of a path that begins with
  idPrefix.

1.1.0-RC5
---------

* **2013-09-04**: If the route matched a pattern with a format extension, the
  format extension is no longer set as route a default.
* **2013-09-04**: Marked ContentAwareGenerator as obsolete, use
  ContentAwareGenerator from the CMF routing component directly instead. This
  class will be removed in 1.2.
* **2013-08-09**: dynamic.generic_controller is now defaulting to null instead
  of the controller from CmfContentBundle. CmfCoreBundle is prepending the
  CmfContentBundle generic controller if that bundle is present. If you do not
  have Core and Content, you need to configure the value explicitly if you want
  routes specifying a template to use the generic controller or have
  template_by_class mapping configuration.

1.1.0-RC2
---------

* **2013-08-04**: [PHPCR-ODM] Fixed the configuration of the LocaleListener to
  make routes again automatically provide the _locale based on their repository
  path if the bundle is configured with locales.
* **2013-08-04**: [Bundle] Only build the compiler passes for ORM and PHPCR-ODM
  if all required repositories are present.

1.1.0-RC1
---------

* **2013-07-31**: [EventDispatcher] Added events to the dynamic router at the
  start of match and matchRequest.
* **2013-07-29**: [DependencyInjection] restructured `phpcr_provider` config
  into `persistence` -> `phpcr` to match other Bundles.
* **2013-07-28**: [DependencyInjection] added `enabled` flag to
  `phpcr_provider` config.
* **2013-07-26**: [Model] Removed setRouteContent and getRouteContent, use
  setContent and getContent instead.
* **2013-07-19**: [Model] Separated database agnostic, doctrine generic and
  PHPCR-ODM specific code to prepare for Doctrine ORM support.
* **2013-07-17**: [FormType] Moved TermsFormType to CoreBundle and renamed it
  to CheckboxUrlLableFormType.

1.1.0-beta2
-----------

* **2013-05-28**: [Bundle] Only include Doctrine PHPCR compiler pass if
  PHPCR-ODM is present.
* **2013-05-25**: [Bundle] Drop symfony_ from symfony_cmf prefix.
* **2013-05-24**: [Document] ContentRepository now requires ManagerRegistry in
  the constructor and provides `setManagerName()` in the same way as
  RouteProvider.
* **2013-05-24**: [Document] RouteProvider now requires ManagerRegistry in the
  constructor (the `cmf_routing.default_route_provider` does this for you). To
  use a different document manager from the registry, call `setManagerName()`
  on the RouteProvider.
