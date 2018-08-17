<?php
/* PHP code to install the plugin
 * For example:
 *
    // To query something to the database

    $table = Database::get_main_table(TABLE_MAIN_USER); // TABLE_MAIN_USER is a constant check the main/inc/database.constants.inc.php
    $sql = "SELECT firstname, lastname FROM $table_users ";
    $users = Database::query($sql);

    You can also use the Chamilo classes
    $users = UserManager::get_user_list();

 */

api_protect_admin_script();

$table = 'vchamilo';
$tablename = Database::get_main_table($table);
$sql = "
    CREATE TABLE IF NOT EXISTS $tablename (
    id int NOT NULL AUTO_INCREMENT,
    sitename varchar(80) NOT NULL,
    slug varchar(255) NOT NULL,
    institution varchar(80) NOT NULL,
    root_web varchar(120),
    db_host varchar(80) NOT NULL,
    db_user varchar(16) DEFAULT 'root',
    db_password varchar(32),  
    table_prefix varchar(16),
    db_prefix varchar(16),
    main_database varchar(60) DEFAULT 'chamilo',
    url_append varchar(32),
    course_folder varchar(80),
    visible int,
    lastcrongap int,
    lastcron int,
    croncount int,
    template varchar(255),
    password_encryption varchar(255),
    archive_url varchar(255),
    home_url varchar(255),
    upload_url varchar(255),
    course_url varchar(255),
    css_theme_folder varchar(255),
    PRIMARY KEY (id)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

/*ALTER TABLE vchamilo ADD COLUMN archive_url varchar(255);
ALTER TABLE vchamilo ADD COLUMN home_url varchar(255);
ALTER TABLE vchamilo ADD COLUMN upload_url varchar(255);
ALTER TABLE vchamilo ADD COLUMN course_url varchar(255);*/
Database::query($sql);

$table = 'vchamilo_config';
$tablename = Database::get_main_table($table);
// TODO: rename "name" and "value" fields - these are reserved keywords
$sql = "CREATE TABLE IF NOT EXISTS $tablename (
    id int(11) NOT NULL AUTO_INCREMENT, 
    component int(11) NOT NULL,
    `name` varchar(64) NOT NULL,
    `value` varchar(255) NOT NULL,
    longvalue varchar(255) NOT NULL,
    PRIMARY KEY (id)
)
";
Database::query($sql);

api_add_setting(0, 'vchamilo_cron_lasthost', 'vchamilo', 'setting', 'Plugins');
api_add_setting(0, 'vchamilo_vcrontime', 'vchamilo', 'setting', 'Plugins');
api_add_setting(0, 'vchamilo_vcrontickperiod', 'vchamilo', 'setting', 'Plugins');

// create root storage directory for templates
global $_configuration;
if (!is_dir($_configuration['root_sys'].'plugin/vchamilo/templates')) {
    $mode = api_get_permissions_for_new_directories();
    mkdir($_configuration['root_sys'].'plugin/vchamilo/templates', $mode, true);
}
