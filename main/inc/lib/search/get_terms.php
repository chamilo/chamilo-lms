<?php

/**
 * @file
 * This script retrieves a list of terms that have xapian documents
 * related with the term passed
 */
$items = array();

// verify parameter and return a right value to avoid problems parsing it
if (empty($_GET['term']) || empty($_GET['prefix']) || !in_array($_GET['operator'], array('or', 'and'))) {
    echo json_encode($array);
    return;
}

$term = $_GET['term'];
$prefix = $_GET['prefix'];
$operator = $_GET['operator'];

require_once dirname(__FILE__) . '../../../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'/search/DokeosQuery.php';
//TODO: manage Any/All
$term_filter = dokeos_get_boolean_query($prefix . $term);
$dkterms = dokeos_query_simple_query('', 0, 1000, array($term_filter));
if (is_array($dkterms) && is_array($dkterms[1])) {
    $specific_fields = get_specific_field_list();
    $sf_terms = array();
    // build base array avoding repeated terms
    foreach ($specific_fields as $specific_field) {
        foreach($dkterms[1] as $obj) {
            foreach ($obj['sf-'.$specific_field['code']] as $raw_term) {
                if (count($raw_term) > 1) {
                    $normal_term = substr($raw_term['name'], 1);
                    $sf_terms[$specific_field['code']][$normal_term] = $normal_term;
                }
            }
        }
    }
    // build array to return
    foreach ($sf_terms as $prefix => $term_group) {
        $first_term = array('__all__' => ($operator=='or'? '-- Any --': '-- All -- '));
        $terms_list[] = array(
            'prefix' => $prefix,
            'terms' => array_merge($first_term, $term_group),
        );
    }
}

echo json_encode($terms_list);
