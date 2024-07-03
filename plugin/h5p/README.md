H5P plugin
===

This plugin is only compatible with Chamilo version 1.11.10 and above.

This plugin allows you to create H5P resources directly from inside Chamilo,
using the Open Source H5P library.

To enable:

* install the plugin
* mark it as "enabled" (inside the plugin configuration page)
* set the plugin to the region "pre_footer"
* change permissions on disk for the plugin/h5p/cache-h5p/launch folder to be writeable by the web server

Once enabled, permissions granted for the web server to write in plugin/h5p/cache-h5p/launch/ 
and the pre_footer region set for the plugin, a new H5P logo will appear in the
new document creation form (the WYSIWIG editor), as a small icon at the end of
the editor interface, exclusively in the learning path document creation
interface (don't look for it anywhere else at this point).

However, it is still very limited at this stage: the H5P files are created in a
common directory for all teachers. This means all teachers can re-use contents of
others (which is good) but also that all teachers can remove contents of others
(which is bad).

We hope to be improving this in the future, but we will not be able, in future
versions, to assign content created in this version to the correct teacher.

We suggest you consider this plugin as a Beta version.

To enable tools in the document editor, please enable it in region pre_footer.

Other docs: 

https://www.ludiscape.com/ressources/resources-elearning-en/integration-of-h5p-into-our-lms-chamilo/