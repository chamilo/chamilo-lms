# Update from the 1.8.3 version to the 1.8.6 version
# Add the table prefix if needed
# ---------------------------------
ALTER TABLE sites ADD COLUMN stopped TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE site_page ADD COLUMN days INT(4) NOT NULL DEFAULT 0;
ALTER TABLE site_page ADD COLUMN depth INT(4) NOT NULL DEFAULT 5;
ALTER TABLE site_page CHANGE num_page links INT(4) NOT NULL DEFAULT 5;
DROP TABLE sites_days_upd;
ALTER TABLE spider CHANGE first_words first_words mediumtext NOT NULL;
ALTER TABLE excludes DROP INDEX ex_id;
ALTER TABLE includes DROP INDEX in_id;
ALTER TABLE keywords DROP INDEX key_id;
ALTER TABLE logs DROP INDEX l_id;
ALTER TABLE sites DROP INDEX site_id;
ALTER TABLE spider DROP INDEX spider_id;
ALTER TABLE tempspider DROP INDEX id;

# Upgrading PhpDig from 1.8.3 to 1.8.6 for VELODLA:
#
# - save a copy of includes/connect.php, config.php
# - make the VELODLA/183phpdig directory inaccessible
#
# - restore the 1.8.6 backup to new directory VELODLA/phpdig-1.8.6
# - replace phpdig-1.8.6/sql/update_db.sql by this file
# - run VELODLA/phpdig-1.8.6/admin/install.php, select "Update existing database", 
#   (provide the parameters from the old connect.php)
# - adapt config.php (cfr. old config.php)
#
# - re-apply customizations: search.php, cprgo.gif, simpleplus.html
#   function_phpdig_form.php, phpdig_functions.php, search_function.php
#
# - edit md_phpdig.php, line 37
# - edit VELODLA homepage links (PhpDig admin, Search with PhpDig)

# - change Metadata_for_Dokeos.html
