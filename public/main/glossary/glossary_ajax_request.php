<?php

/* For licensing terms, see /license.txt */

/* @todo move this file in the inc/ajax/ folder */
/**
 * Glossary ajax request code.
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

/**
 * Search a term and return description from a glossary.
 */
$charset = api_get_system_encoding();

// Replace image path
// @todo redo the image path replace
/*$path_image = api_get_path(WEB_COURSE_PATH).api_get_course_path();
$path_image_search = '../..'.api_get_path(REL_COURSE_PATH).api_get_course_path();*/
$glossaryId = isset($_REQUEST['glossary_id']) ? (int) $_REQUEST['glossary_id'] : 0;
$description = get_lang('No results found');

if (!empty($glossaryId)) {
    $description = GlossaryManager::get_glossary_term_by_glossary_id($glossaryId);
//$description = str_replace($path_image_search, $path_image, $description);
} elseif (isset($_REQUEST['glossary_data']) && 'true' === $_REQUEST['glossary_data']) {
    // get_glossary_terms
    $glossary_data = GlossaryManager::get_glossary_terms();
    $glossary_all_data = [];
    if (count($glossary_data) > 0) {
        foreach ($glossary_data as $glossary_index => $glossary_value) {
            $glossary_all_data[] = $glossary_value['id'].'__|__|'.$glossary_value['title'];
        }
        $description = implode('[|.|_|.|-|.|]', $glossary_all_data);
    }
} elseif (isset($_REQUEST['glossary_name'])) {
    $glossaryInfo = GlossaryManager::get_glossary_term_by_glossary_name($_REQUEST['glossary_name']);

    if (!empty($glossaryInfo)) {
        /*$description = str_replace(
            $path_image_search,
            $path_image,
            $glossaryInfo['description']
        );*/

        if (null === $description || 0 == strlen(trim($description))) {
            $description = get_lang('No results found');
        } else {
            $description = str_replace('class="glossary"', '', $description);
        }
    }
}

echo api_xml_http_response_encode($description);
