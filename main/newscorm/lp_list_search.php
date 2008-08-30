<?php
/*
 * Script to draw the results from a query
 * @package: dokeos.learnpath
 * @author: Diego Escalante Urrelo <diegoe@gmail.com>
 */
require api_get_path(LIBRARY_PATH).'search/search_widget.php';
require api_get_path(LIBRARY_PATH).'search/DokeosQuery.php';


$htmlHeadXtra[] = '
    <style type="text/css">
    .doc_table {
        width: 30%;
        text-align: left;
    }
    .doc_img {
        border: 1px solid black;
        padding: 1px solid white;
        background: white;
    }
    .doc_img,
    .doc_img img {
        width: 120px;
    }
    .doc_text, 
    .doc_title {
        padding-left: 10px;
        vertical-align: top;
    }
    .doc_title {
        font-size: large;
        font-weight: bold;
        height: 2em;
    }
    </style>';


search_widget_prepare(&$htmlHeadXtra);

Display::display_header(null,'Path');

if (api_get_setting('search_enabled') !== 'true') {
    Display::display_error_message(get_lang('SearchFeatureNotEnabledComment'));
    Display::display_footer();
}
else
{
    search_widget_show(empty($search_action)?null:'index.php');
}


/**
 * Utility function to get a table for a specific course.
 * 
 * @param   string $course_code     The course_code as in cidReq 
 * @param   string $table           The desired table, this is just concat'd.
 * @return  string 
 */
function get_course_table($course_code, $table) {
    $ret = NULL;

    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $sql = "SELECT 
                $course_table.db_name, $course_cat_table.code
            FROM $course_table
            LEFT JOIN $course_cat_table
            ON 
                $course_table.category_code =  $course_cat_table.code
            WHERE 
                $course_table.code = '$course_code'
            LIMIT 1";
    $res = api_sql_query($sql, __FILE__, __LINE__);
    $result = Database::fetch_array($res);

    $ret = sprintf ("%s.%s",
        $result[0],
        $table);

    return $ret;
}

$tags = explode(",", trim($_REQUEST['tags']));
$tags2 = '';

foreach ($tags as $tag)
    if (strlen($tag)> 1)
        $tags2 .= " tag:".trim($tag);

$query = $_REQUEST['query'] . ' ' . $tags2;

$query_results = dokeos_query_query($query, 0, 1000);
$count = $query_results[0];
$results = $query_results[1];

$blocks = array();

$url = api_get_path(WEB_CODE_PATH)."/newscorm/lp_controller.php";

$search_url = sprintf('%s?action=search&query=%s&tags=%s', 
                $url, $_REQUEST['query'], $_REQUEST['tags']);

$link_format = $url.'?cidReq=%s&action=view&lp_id=%s&item_id=%s';

if ($count > 0) {
    foreach ($results as $result) {
        /* FIXME:diegoe: marco debe darme los valores adecuados. */
        $ids = explode(":", $result->ids);
        $course_id = $ids[0];
        $lp_id = $ids[1];
        $doc_id = $ids[2];

        if (!api_is_course_visible_for_user(NULL, $course_id))
            continue;

        $tags = '';
        foreach ($result->terms as $term) {
            $tags .= trim($term['name'], 'T') . ", ";
        }

        
        $lp_table = get_course_table($course_id, TABLE_LP_ITEM);
        $doc_table = get_course_table($course_id, TABLE_DOCUMENT);
        $sql = "SELECT 
                    $doc_table.title, $doc_table.path, $lp_table.id
                FROM $lp_table INNER JOIN $doc_table 
                    ON $lp_table.path = $doc_table.id  
                    WHERE 
                        $lp_table.lp_id = $lp_id 
                    AND 
                        $doc_table.id = $doc_id 
                LIMIT 1";

        $dk_result = api_sql_query ($sql);

        while ($row = Database::fetch_array ($dk_result)) {
            /* Get the image path */
            $img_path = str_replace ('.png.html', '_thumb.png', $row['path']);
            $doc_id = $row['id'];
            $title = $row['title'];

            $href = sprintf($link_format, $course_id, $lp_id, $doc_id);

            /* Fill the result array */
            $blocks[] = array(
                api_get_path(WEB_COURSE_PATH).api_get_course_path($course_id)."/document/".$img_path, 
                $title, 
                $tags,
                $href
                );
        }
    }
}

if (count($blocks) < 1) {
    Display::display_normal_message(get_lang('SearchFeatureSearchExplanation'), FALSE);
}
else
{
    function to_img($i) {
        return sprintf('<img src="%s"/>', $i);
    }
    function to_link($i) {
        return sprintf('<a href="%s">%s</a>', $i, get_lang('ViewLearningPath'));
    }

    $s = new SortableTableFromArray($blocks);
    $s->additional_parameters = array(
                'action' => 'search',
                'query' => $_REQUEST['query'],
                'tags' => $_REQUEST['tags'],
                );
    $s->set_header(0, get_lang('Preview'));
    $s->set_header(1, get_lang('Title'));
    $s->set_header(2, get_lang('Tags'));
    $s->set_header(3, get_lang('Learning path'));
    $s->set_column_filter(0,'to_img');
    $s->set_column_filter(3,'to_link');
    $s->display();
}

Display::display_footer();
?>