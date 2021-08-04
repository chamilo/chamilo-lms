<?php
/* For licensing terms, see /license.txt */

/**
 * Script to draw the results from a query.
 *
 * @author Diego Escalante Urrelo <diegoe@gmail.com>
 * @author Marco Antonio Villegas Vega <marvil07@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> bug fixing
 */
require api_get_path(LIBRARY_PATH).'search/search_widget.php';
require api_get_path(LIBRARY_PATH).'search/ChamiloQuery.php';
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

Event::event_access_tool(TOOL_SEARCH);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = ['url' => './index.php', 'name' => get_lang(ucfirst(TOOL_SEARCH))];
search_widget_prepare($htmlHeadXtra);
Display::display_header(null, 'Path');

if (api_get_setting('search_enabled') !== 'true') {
    echo Display::return_message(get_lang('SearchFeatureNotEnabledComment'), 'error');
} else {
    if (!empty($_GET['action'])) {
        search_widget_show($_GET['action']);
    } else {
        search_widget_show();
    }
}

// Initialize.
$op = 'or';
if (!empty($_REQUEST['operator']) && in_array($op, ['or', 'and'])) {
    $op = $_REQUEST['operator'];
}

$query = null;

if (isset($_REQUEST['query'])) {
    $query = stripslashes(htmlspecialchars_decode($_REQUEST['query'], ENT_QUOTES));
}

$mode = 'default';
if (isset($_GET['mode']) && in_array($_GET['mode'], ['gallery', 'default'])) {
    $mode = $_GET['mode'];
}

$term_array = [];
$specific_fields = get_specific_field_list();
foreach ($specific_fields as $specific_field) {
    if (!empty($_REQUEST['sf_'.$specific_field['code']])) {
        $values = $_REQUEST['sf_'.$specific_field['code']];
        if (in_array('__all__', $values)) {
            $sf_terms_for_code = xapian_get_all_terms(
                1000,
                $specific_field['code']
            );
            foreach ($sf_terms_for_code as $term) {
                if (!empty($term)) {
                    $term_array[] = chamilo_get_boolean_query($term['name']); // Here name includes prefix.
                }
            }
        } else {
            foreach ($values as $term) {
                if (!empty($term)) {
                    $prefix = $specific_field['code'];
                    $term_array[] = chamilo_get_boolean_query($prefix.$term);
                }
            }
        }
    } else {
        $sf_terms_for_code = xapian_get_all_terms(1000, $specific_field['code']);
        foreach ($sf_terms_for_code as $term) {
            if (!empty($term)) {
                // Here name includes prefix.
                $term_array[] = chamilo_get_boolean_query($term['name']);
            }
        }
    }
}

// Get right group of terms to show on multiple select.
$fixed_queries = [];
$course_filter = null;
if (($cid = api_get_course_id()) != -1) {
    // Results only from actual course.
    $course_filter = chamilo_get_boolean_query(XAPIAN_PREFIX_COURSEID.$cid);
}

if (count($term_array)) {
    $fixed_queries = chamilo_join_queries($term_array, null, $op);

    if ($course_filter != null) {
        $fixed_queries = chamilo_join_queries(
            $fixed_queries,
            $course_filter,
            'and'
        );
    }
} else {
    if (!empty($query)) {
        $fixed_queries = [$course_filter];
    }
}

if ($query) {
    list($count, $results) = chamilo_query_query(
        api_convert_encoding($query, 'UTF-8', $charset),
        0,
        1000,
        $fixed_queries
    );
} else {
    $count = 0;
    $results = [];
}

// Prepare blocks to show.
$blocks = [];

if ($count > 0) {
    foreach ($results as $result) {
        // Fill the result array.
        if (empty($result['thumbnail'])) {
            $result['thumbnail'] = Display::returnIconPath('no_document_thumb.jpg');
        }

        if (!empty($result['url'])) {
            $a_prefix = '<a href="'.$result['url'].'">';
            $a_suffix = '</a>';
        } else {
            $a_prefix = '';
            $a_suffix = '';
        }

        if ($mode == 'gallery') {
            $title = $a_prefix.str_replace('_', ' ', $result['title']).$a_suffix;
            $blocks[] = [1 => $a_prefix.'<img src="'.$result['thumbnail'].'" />'.$a_suffix.'<br />'.$title.'<br />'.$result['author'],
            ];
        } else {
            $title = '<div style="text-align:left;">'.$a_prefix.$result['title'].$a_suffix.(!empty($result['author']) ? ' '.$result['author'] : '').'<div>';
            $blocks[] = [1 => $title];
        }
    }
}

// Show results.
if (count($blocks) > 0) {
    $s = new SortableTableFromArray($blocks);
    $s->display_mode = $mode; // default
    $s->display_mode_params = 3;
    $s->per_page = 9;
    $additional_parameters = [
        'mode' => $mode,
        'action' => 'search',
        'query' => Security::remove_XSS($_REQUEST['query']),
    ];
    $get_params = '';
    foreach ($specific_fields as $specific_field) {
        if (isset($_REQUEST['sf_'.$specific_field['code']])) {
            $values = $_REQUEST['sf_'.$specific_field['code']];
            //Sortable additional_parameters doesn't accept multi dimensional arrays
            //$additional_parameters[ 'sf_'. $specific_field['code'] ] = $values;
            foreach ($values as $value) {
                $get_params .= '&sf_'.$specific_field['code'].'[]='.$value;
            }
            $get_params .= '&';
        }
    }
    $additional_parameters['operator'] = $op;
    $s->additional_parameters = $additional_parameters;

    if ('default' == $mode) {
        $s->set_header(0, get_lang(ucfirst(TOOL_SEARCH)), false);
    }

    $search_link = '<a href="%ssearch/index.php?mode=%s&action=search&query=%s%s">';

    $iconGallery = (($mode == 'gallery') ? 'ButtonGallOn' : 'ButtonGallOff').'.png';
    $iconDefault = (($mode == 'default') ? 'ButtonListOn' : 'ButtonListOff').'.png';

    $mode_selector = '<div id="mode-selector">';
    $mode_selector .= sprintf($search_link, api_get_path(WEB_CODE_PATH), 'gallery', $_REQUEST['query'], $get_params);
    $mode_selector .= Display::return_icon($iconGallery).'</a>';
    $mode_selector .= sprintf($search_link, api_get_path(WEB_CODE_PATH), 'default', $_REQUEST['query'], $get_params);
    $mode_selector .= Display::return_icon($iconDefault).'</a>';
    $mode_selector .= '</div>';

    echo '<div id="search-results-container">';
    echo $mode_selector;
    $s->display();
    echo '</div>';
}

Display::display_footer();
