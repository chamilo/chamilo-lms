<?php
/* For licensing terms, see /license.txt */

/**
 * Process part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
$cwdir = getcwd();
require_once '../lp/lp_upload.php';

// Reinit current working directory as many functions in upload change it
chdir($cwdir);

if (api_get_setting('search_enabled') === 'true') {
    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
    $specific_fields = get_specific_field_list();

    foreach ($specific_fields as $specific_field) {
        $values = explode(',', trim($_POST[$specific_field['code']]));
        if (!empty($values)) {
            foreach ($values as $value) {
                $value = trim($value);
                if (!empty($value)) {
                    add_specific_field_value(
                        $specific_field['id'],
                        api_get_course_id(),
                        TOOL_LEARNPATH,
                        $oScorm->lp_id,
                        $value
                    );
                }
            }
        }
    }
}

header('location: ../lp/lp_controller.php?action=list&'.api_get_cidreq());
exit;
