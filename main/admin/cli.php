<?php
/* For licensing terms, see /license.txt */
/**
 * Used for external support of chamilo's users.
 *
 * @author Arnaud Ligot, CBlue SPRL
 *
 * @package chamilo.admin.cli
 */

// we are in the admin area so we do not need a course id
$cidReset = true;
// include global script
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
// make sure only logged-in admins can execute this
api_protect_admin_script();

// setting the name of the tool
$tool_name = get_lang('CommandLineInterpreter');
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
// including the header file (which includes the banner itself)
Display::display_header($tool_name);
switch ($_GET["cmd"]) {
    case "clear_stapi":
        echo "Are you sure you are willing to erase all storage api data (no backup)? <a href='cli.php?cmd=clear_stapi_confirm' >Yes</a>";
        break;
    case "clear_stapi_confirm":
        Database::query("delete from ".Database::get_main_table(TABLE_TRACK_STORED_VALUES));
        Database::query("delete from ".Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK));
        echo "Done";
        break;
    default:
        echo "UNKNOWN COMMAND";
        break;
}

Display::display_footer();
