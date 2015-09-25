CHANGELOG
=========

A [BC BREAK] means the update will break the project for many reasons:

* new mandatory configuration
* new dependencies
* class refactoring

### 2014-04-23

* [BC BREAK] Complete support for context, add new method in BlockServiceManager (getServicesByContext)

### 2014-03-10

* [BC BREAK] Updated configuration: ``sonata_block.profiler.container_types`` is deprecated in favor of ``sonata_block.container.types``; please update your configuration accordingly.

### 2013-03-28

* [BC BREAK] Introduction of an BlockContext to hold a BlockInterface instance and the related settings.

