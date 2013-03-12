<?php
/* For licensing terms, see /license.txt */

/**
 * This is the index file displayed when a user is logged in on Chamilo.
 *
 * It displays:
 * - personal course list
 * - menu bar
 * Search for CONFIGURATION parameters to modify settings
 * @package chamilo.main
 * @todo Shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for?
 *       If these are really configuration settings then we can add those to the dokeos config settings.
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension?
 */

//Temporal hack to redirect calls to the new web/index.php
header('Location: web/userportal');
exit;

/**
 * INIT SECTION
 */
// Language files that should be included.

use \ChamiloSession as Session;

$language_file = array('courses', 'index', 'admin');

$cidReset = true; /* Flag forcing the 'current course' reset,
  as we're not inside a course anymore */

if (isset($_SESSION['this_section']))
    unset($_SESSION['this_section']); // For HTML editor repository.

/* Included libraries */

require_once './main/inc/global.inc.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;

$load_dirs = api_get_setting('show_documents_preview');

if ($load_dirs) {
    $url = api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=document_preview';
    $folder_icon = api_get_path(WEB_IMG_PATH).'icons/22/folder.png';
    $close_icon = api_get_path(WEB_IMG_PATH).'loading1.gif';

    $htmlHeadXtra[] = '<script>

    $(document).ready(function() {
        $(".document_preview_container").hide();
        $(".document_preview").click(function() {
            var my_id = this.id;
            var course_id  = my_id.split("_")[2];
            var session_id = my_id.split("_")[3];

            //showing div
            $(".document_preview_container").hide();

            $("#document_result_" +course_id+"_" + session_id).show();

            //Loading
            var image = $("img", this);
            image.attr("src", "'.$close_icon.'");

            $.ajax({
                url: "'.$url.'",
                data: "course_id="+course_id+"&session_id="+session_id,
                success: function(return_value) {
                    image.attr("src", "'.$folder_icon.'");
                    $("#document_result_" +course_id+"_" + session_id).html(return_value);

                }
            });

        });
    });
    </script>';
}
