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
global $_configuration;

api_add_setting(
    @$_configuration['defaults']['customfooter_footer_left'],
    'customfooter_footer_left',
    'customfooter',
    'setting',
    'Plugins'
);
api_add_setting(
    @$_configuration['defaults']['customfooter_footer_right'],
    'customfooter_footer_right',
    'customfooter',
    'setting',
    'Plugins'
);
