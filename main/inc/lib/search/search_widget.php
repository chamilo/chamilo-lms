<?php

/**
 * Search widget. Shows the search screen contents.
 * @package dokeos.search
 */
require_once dirname(__FILE__) . '/IndexableChunk.class.php';
require_once api_get_path(LIBRARY_PATH).'/specific_fields_manager.lib.php';
//require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/**
 * Add some required CSS and JS to html's head.
 *
 * Note that $htmlHeadXtra should be passed by reference and not value,
 * otherwise this function will have no effect and your form will be broken.
 *
 * @param   array $htmlHeadXtra     A reference to the doc $htmlHeadXtra
 */
function search_widget_prepare(&$htmlHeadXtra) {
    $htmlHeadXtra[] = '
    <script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js"></script>
    <script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'search/search_widget.js"></script>
    <link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.autocomplete.css" />
    <link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_LIBRARY_PATH).'search/search_widget.css" />
    ';
}

/**
 * Get one term html select
 */
function format_one_specific_field_select($prefix, $sf_term_array, $op, $extra_select_attr='size="7" class="sf-select-multiple"') {
	global $charset;
    $multiple_select .= '<select '. $extra_select_attr .' title="'. $prefix .'" id="sf-'. $prefix .'" name="sf_'. $prefix .'[]">';

    $all_selected = '';
    if (!empty($_REQUEST['sf_'. $prefix]) ) {
        if (in_array('__all__', $_REQUEST['sf_'. $prefix])) {
            $all_selected = 'selected="selected"';
        }
    }
    if ($op == 'and') {
        $all_selected_name = get_lang('All');
    } else if ($op == 'or') {
        $all_selected_name = get_lang('Any');
    }
    $multiple_select .= '<option value="__all__" '. $all_selected .' >-- '. $all_selected_name .' --</option>';

    foreach ($sf_term_array as $raw_term) {
        $term = substr($raw_term, 1);
        if (empty($term)) continue;
        $html_term = htmlspecialchars($term, ENT_QUOTES, $charset);
        $selected = '';
        if (!empty($_REQUEST['sf_'.$prefix]) && is_array($_REQUEST['sf_'.$prefix]) && in_array($term,$_REQUEST['sf_'.$prefix])) {
            $selected = 'selected="selected"';
        }
        $multiple_select .= '<option value="'. $html_term .'" '.$selected.'>'. $html_term .'</option>';
    }
    $multiple_select .= '</select>';
    return $multiple_select;
}

/**
 * Get terms html selects
 */
function format_specific_fields_selects($sf_terms, $op, $prefilter_prefix='') {
    // Process each prefix type term
    $i = 0;
    $max = count($sf_terms);
    $multiple_selects .='';
    foreach ($sf_terms as $prefix => $sf_term_array) {
        if ($prefix == $prefilter_prefix) continue;
        $multiple_select = '';
        if ($i>0) {
            //print "+" image
            $multiple_select .= '<td><img class="sf-select-splitter" src="../img/search-big-plus.gif" alt="plus-sign-decoration"/></td>';
        }
        //sorting the array of terms
        $temp = array();
        foreach ($sf_term_array as $key => $value) {
            $temp[trim(stripslashes($value['name']))] = $key;
        }
        $temp = array_flip($temp);
        unset($sf_term_array);
        natcasesort($temp);
        $sf_term_array = $temp;

        $sf_copy = $sf_term_array;
        // get specific field name
        $sf_value = get_specific_field_list(array( 'code' => "'$prefix'" ));
        $sf_value = array_shift($sf_value);
        $multiple_select .= '<td><label class="sf-select-multiple-title" for="sf_'. $prefix .'[]">'.$icons_for_search_terms[$prefix].' '.$sf_value['name'].'</label><br />';
        $multiple_select .= format_one_specific_field_select($prefix, $sf_term_array, $op, 'multiple="multiple" size="7" class="sf-select-multiple"');
        $multiple_select .= '</td>';
        $multiple_selects .= $multiple_select;
        $i++;
    }
    return $multiple_selects;
}

/**
 * Build the normal form.
 *
 * First, natural way.
 */
function search_widget_normal_form($action, $show_thesaurus, $sf_terms, $op) {
    $thesaurus_icon = Display::return_icon('thesaurus.gif', get_lang('SearchAdvancedOptions'), array('id'=>'thesaurus-icon'));
    $advanced_options = '<a id="tags-toggle" href="#">'.  get_lang('SearchAdvancedOptions') .'</a>';
    $display_thesaurus = ($show_thesaurus==true? 'block': 'none');
    $help = '<h3>'. get_lang('SearchKeywordsHelpTitle') .'</h3>'. get_lang('SearchKeywordsHelpComment');
    $mode = (!empty($_REQUEST['mode'])? htmlentities($_REQUEST['mode']): 'gallery');
    $type = (!empty($_REQUEST['type'])? htmlentities($_REQUEST['type']): 'normal');

    /**
     * POST avoid long urls, but we are using GET because
     * SortableTableFromArray pagination is done with simple links, so now we
     * could not send a form in pagination
     */

	if (isset($_GET['action']) && strcmp(trim($_GET['action']),'search')===0) {
		$action='index.php';
	}
    $form = '
        <form id="dokeos_search" action="'. $action .'" method="GET">
            <input type="text" id="query" name="query" size="40" />
            <input type="hidden" name="mode" value="'. $mode .'"/>
            <input type="hidden" name="type" value="'. $type .'"/>
            <input type="hidden" name="tablename_page_nr" value="1" />
            <input type="submit" id="submit" value="'. get_lang("Search") .'" />
            <br /><br />
            <span class="search-links-box">'. $thesaurus_icon . $advanced_options .'&nbsp;</span>
            <div id="tags" class="tags" style="display:'. $display_thesaurus .';">
                <div class="search-help-box">'. $help .'</div>
                <table>
                <tr>
            ';
    $form .= format_specific_fields_selects($sf_terms, $op);
    $or_checked = '';
    $and_checked = '';
    if ($op == 'or') {
        $or_checked = 'checked="checked"';
    } else if ($op == 'and') {
        $and_checked = 'checked="checked"';
    }
    $form .= '
                </tr>
                <tr>
                    <td id="operator-select">
                        '. get_lang('SearchCombineSearchWith') .':<br />
                        <input type="radio" class="search-operator" name="operator" value="or" '. $or_checked .'>'. api_strtoupper(get_lang('Or')) .'</input>
                        <input type="radio" class="search-operator" name="operator" value="and" '. $and_checked .'>'. api_strtoupper(get_lang('And')) .'</input>
                    </td>
                    <td></td>
                    <td>
                        <br />
                        <input class="lower-submit" type="submit" value="'. get_lang('Search') .'" />
                        <input type="submit" id="tags-clean" value="'. get_lang('SearchResetKeywords') .'" />
                    </td>
                </tr>
                </table>
            </div>
        </form>
        <br style="clear: both;"/>
             ';
    return $form;
}

/**
 * Build the prefilter form.
 *
 * This type allow filter all other multiple select terms by one term in a dinamic way
 */
function search_widget_prefilter_form($action, $show_thesaurus, $sf_terms, $op, $prefilter_prefix=NULL) {
    $thesaurus_icon = Display::return_icon('thesaurus.gif', get_lang('SearchAdvancedOptions'), array('id'=>'thesaurus-icon'));
    $advanced_options = '<a id="tags-toggle" href="#">'.  get_lang('SearchAdvancedOptions') .'</a>';
    $display_thesaurus = ($show_thesaurus==true? 'block': 'none');
    $help = '<h3>'. get_lang('SearchKeywordsHelpTitle') .'</h3>'. get_lang('SearchKeywordsHelpComment');
    $mode = (!empty($_REQUEST['mode'])? htmlentities($_REQUEST['mode']): 'gallery');
    $type = (!empty($_REQUEST['type'])? htmlentities($_REQUEST['type']): 'normal');

    /**
     * POST avoid long urls, but we are using GET because
     * SortableTableFromArray pagination is done with simple links, so now we
     * could not send a form in pagination
     */
	if (isset($_GET['action']) && strcmp(trim($_GET['action']),'search')===0) {
		$action='index.php';
	}
    $form = '
        <form id="dokeos_search" action="'. $action .'" method="GET">
            <input type="text" id="query" name="query" size="40" />
            <input type="hidden" name="mode" value="'. $mode .'"/>
            <input type="hidden" name="type" value="'. $type .'"/>
            <input type="hidden" name="tablename_page_nr" value="1" />
            <input type="submit" id="submit" value="'. get_lang("Search") .'" />
            <br /><br />
            <span class="search-links-box">'. $thesaurus_icon . $advanced_options .'&nbsp;</span>
            <div id="tags" class="tags" style="display:'. $display_thesaurus .';">
                <div class="search-help-box">'. $help .'</div>
                <table>
                <tr>
            ';

    if (!is_null($prefilter_prefix)) {
        //sorting the array of terms
        $temp = array();
        foreach ($sf_terms[$prefilter_prefix] as $key => $value) {
            $temp[trim(stripslashes($value['name']))] = $key;
        }
        $temp = array_flip($temp);
        unset($sf_term_array);
        natcasesort($temp);
        $sf_term_array = $temp;

        // get specific field name
        $sf_value = get_specific_field_list(array( 'code' => "'$prefilter_prefix'" ));
        $sf_value = array_shift($sf_value);
        $form .= '<label class="sf-select-multiple-title" for="sf_'. $prefix .'[]">'.$icons_for_search_terms[$prefix].' '.$sf_value['name'].'</label><br />';

        $form .= format_one_specific_field_select($prefilter_prefix, $sf_term_array, $op, 'id="prefilter"');
        $form .= format_specific_fields_selects($sf_terms, $op, $prefilter_prefix);
    } else {
        $form .= format_specific_fields_selects($sf_terms, $op);
    }
    $or_checked = '';
    $and_checked = '';
    if ($op == 'or') {
        $or_checked = 'checked="checked"';
    } else if ($op == 'and') {
        $and_checked = 'checked="checked"';
    }
    $form .= '
                </tr>
                <tr>
                    <td id="operator-select">
                        '. get_lang('SearchCombineSearchWith') .':<br />
                        <input type="radio" class="search-operator" name="operator" value="or" '. $or_checked .'>'. api_strtoupper(get_lang('Or')) .'</input>
                        <input type="radio" class="search-operator" name="operator" value="and" '. $and_checked .'>'. api_strtoupper(get_lang('And')) .'</input>
                    </td>
                    <td></td>
                    <td>
                        <br />
                        <input class="lower-submit" type="submit" value="'. get_lang('Search') .'" />
                        <input type="submit" id="tags-clean" value="'. get_lang('SearchResetKeywords') .'" />
                    </td>
                </tr>
                </table>
            </div>
        </form>
        <br style="clear: both;"/>
             ';
    return $form;
}

/**
 * Show search form
 */
function display_search_form($action, $show_thesaurus, $sf_terms, $op) {
    $type = (!empty($_REQUEST['type'])? htmlentities($_REQUEST['type']): 'normal');
    switch ($type) {
    case 'prefilter':
        $prefilter_prefix = api_get_setting('search_prefilter_prefix');
        $form = search_widget_prefilter_form($action, $show_thesaurus, $sf_terms, $op, $prefilter_prefix);
        break;
    case 'normal':
    default:
        $form = search_widget_normal_form($action, $show_thesaurus, $sf_terms, $op);
    }

    // show built form
    echo $form;
}

/**
 * Show the search widget
 *
 * The form will post to index.php by default, you can pass a value to
 * $action to use a custom action.
 * IMPORTANT: you have to call search_widget_prepare() before calling this
 * function or otherwise the form will not behave correctly.
 *
 * @param   string $action     Just in case your action is not
 * index.php
 */
function search_widget_show($action='index.php') {
    global $charset;
    require_once api_get_path(LIBRARY_PATH).'/search/DokeosQuery.php';
    // TODO: load images dinamically when they're avalaible from specific field ui to add
    $icons_for_search_terms = array();

    $sf_terms = array();
    $specific_fields = get_specific_field_list();
    $url_params = array();

    if ( ($cid=api_get_course_id()) != -1 ) { // with cid

        // get search engine terms
        $course_filter = dokeos_get_boolean_query(XAPIAN_PREFIX_COURSEID . $cid);
        $dkterms = dokeos_query_simple_query('', 0, 1000, array($course_filter));

        //prepare specific fields names (and also get possible URL param names)
        foreach ($specific_fields as $specific_field) {
            $temp = array();
            foreach($dkterms[1] as $obj) {
                $temp = array_merge($obj['sf-'.$specific_field['code']], $temp);
            }
            $sf_terms[$specific_field['code']] = $temp;
            $url_params[] = 'sf_'.$specific_field['code'];
            unset($temp);
        }

    } else { // without cid

        // prepare specific fields names (and also get possible URL param names)
        foreach ($specific_fields as $specific_field) {
            //get Xapian terms for a specific term prefix, in ISO, apparently
            $sf_terms[$specific_field['code']] = xapian_get_all_terms(1000, $specific_field['code']);
            $url_params[] = 'sf_'.$specific_field['code'];
        }

    }

    echo '<h2>'.get_lang('Search').'</h2>';

    
	// Tool introduction
    // TODO: Settings for the online editor to be checked (insert an image for example). Probably this is a special case here.
    if (api_get_course_id() !== -1)
    if (!empty($_SESSION['_gid'])) {
        Display::display_introduction_section(TOOL_SEARCH.$_SESSION['_gid']);
    } else {
        Display::display_introduction_section(TOOL_SEARCH);
    }


    $op = 'or';
    if (!empty($_REQUEST['operator']) && in_array($op,array('or','and'))) {
        $op = $_REQUEST['operator'];
    }

    //check if URL params are defined (to see if we show the thesaurus or not)
    $show_thesaurus = false;
    foreach ($url_params as $param) {
        if (is_array($_REQUEST[$param])) {
            $thesaurus_decided = FALSE;
            foreach ($_REQUEST[$param] as $term) {
                if (!empty($term)) {
                    $show_thesaurus = true;
                    $thesaurus_decided = TRUE;
                    break;
                }
            }
            if ($thesaurus_decided) break;
        }
    }

    // create the form
    // TODO: use FormValidator
    display_search_form($action, $show_thesaurus, $sf_terms, $op);

}
