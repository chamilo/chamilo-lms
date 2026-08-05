Search courses plugin
===

This plugin provides a course catalogue search page and can also be rendered as a portal/home block through Chamilo plugin regions.

Usage
---

1. Install and activate the plugin.
2. Go to `Administration > Settings > Plugins > Plugin regions`.
3. Assign `SearchCourse` to a visible region such as `content_top`, `main_top` or `main_bottom`.
4. The region renders a search box. Submitting the form opens `/plugin/SearchCourse/index.php` with the search results.

The plugin does not create a course tool because it searches courses globally. It should be used as a portal/home block, not inside a specific course.
