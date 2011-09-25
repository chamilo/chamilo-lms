<?php
/* For licensing terms, see /dokeos_license.txt */
/**
 * This script retrieves a list of terms that have xapian documents
 * related with the term passed
 * @package chamilo.include.search
 */
/**
 * Code
 */
$terms_list = array();

// verify parameter and return a right value to avoid problems parsing it
if (empty($_GET['term']) || empty($_GET['prefix']) || !in_array($_GET['operator'], array('or', 'and'))) {
    echo json_encode($terms_list);
    return;
}

require_once dirname(__FILE__) . '../../../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'search/DokeosQuery.php';

/**
 * search with filter and build base array avoding repeated terms
 * @param array $filter XapianQuery array
 * @param array $specific_fields
 * @return array $sf_terms
 */
function get_usual_sf_terms($filter, $specific_fields) {
    $sf_terms = array();
    $dkterms = dokeos_query_simple_query('', 0, 1000, $filter);

    if (is_array($dkterms) && is_array($dkterms[1])) {
        foreach ($specific_fields as $specific_field) {
            foreach($dkterms[1] as $obj) {
                foreach ($obj['sf-'.$specific_field['code']] as $raw_term) {
                    if (count($raw_term['name']) > 1) {
                        $normal_term = substr($raw_term['name'], 1);
                        $sf_terms[$specific_field['code']][$normal_term] = $normal_term;
                    }
                }
            }
        }
    }
    return $sf_terms;
}

$term = $_GET['term'];
$prefix = $_GET['prefix'];
$operator = $_GET['operator'];

$specific_fields = get_specific_field_list();
$sf_terms = array();

if ( ($cid=api_get_course_id()) != -1) { // with cid
    // course filter
    $filter[] = dokeos_get_boolean_query(XAPIAN_PREFIX_COURSEID . $cid);
    // term filter
    if ($term != '__all__') {
        $filter[] = dokeos_get_boolean_query($prefix . $term);
        // always and between term and courseid
        $filter = dokeos_join_queries($filter, null, 'and');
    }

    $sf_terms = get_usual_sf_terms($filter, $specific_fields);

} else { // without cid
    if ($term != '__all__') {
        $filter[] = dokeos_get_boolean_query($prefix . $term);

        $sf_terms = get_usual_sf_terms($filter, $specific_fields);

    } else { // no cid and all/any terms
        foreach ($specific_fields as $specific_field) {
            foreach(xapian_get_all_terms(1000, $specific_field['code']) as $raw_term) {
                if (count($raw_term['name']) > 1) {
                    $normal_term = substr($raw_term['name'], 1);
                    $sf_terms[$specific_field['code']][$normal_term] = $normal_term;
                }
            }
        }
    }

}

// build array to return
foreach ($sf_terms as $sf_prefix => $term_group) {
    //if (count($tem_group) > 0) {
    $first_term = array('__all__' => ($operator=='or'? '-- Any --': '-- All -- '));
    //}
    if ($sf_prefix != $prefix) {
        $terms_list[] = array(
            'prefix' => $sf_prefix,
            'terms' => array_merge($first_term, $term_group),
        );
    }
}

echo json_encode($terms_list);
