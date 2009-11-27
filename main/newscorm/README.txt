To use the new SCORM tool, first run the scorm_update_db.php from where it is in the (new)scorm directory.
This will update all courses databases by adding 4 tables (lp, lp_item, lp_view and lp_item_view) and add an lp_type table in the main database.
If you had installed a development version of the newscorm tool, it is a good idea to also run scorm_update_db_alter.php to make sure the latest changes are in.

Then go to the database and modify the 'tool' table in each course to add the 'newscorm' tool (copy the tool row from 'learnpath' and change the name). Make sure to select newscorm/lp_list.php as a starting point for this tool (although index.php should work as well).
You can do this by using the scorm_migrate_hometools.php script now.

If you are using one of these scripts, it is vital you check by yourself that all the courses have been migrated accurately. For example, you will need to check that all links to learning paths in the course homepage are pointing towards the newscorm/ directory, not scorm/ or learnpath/.

Go to one of your courses' homepage and try the new SCORM tool as you would normally do with the old SCORM tool.
Please note this DOES NOT manage the Dokeos learnpaths yet.

For more detailed development information, please read the corresponding Wiki page: http://www.dokeos.com/wiki/index.php/SCORM_tool_redesign

For any problem contact me directly at <yannick.warnier@dokeos.com>
