<?php
/*
 * Script defining generic functions against a search engine api. Just only if one day the search engine changes
 * @package: dokeos.search
 */
require 'xapian/XapianQuery.php';

/**
 * Wrapper for queries
 *
 * @param   string    $query_string   The search string
 * @param   int       $offset         Offset to the first item to retrieve. Optional
 * @param   int       lenght          Number of items to retrieve. Optional
 * @param   array     extra           Extra queries to join with. Optional
 * @return  array
 */
function dokeos_query_query($query_string, $offset=0, $length=10, $extra=NULL) {
    list($count, $results) = xapian_query($query_string, NULL, $offset, $length, $extra);
    return dokeos_preprocess_results($results);
}

function dokeos_query_simple_query($query_string, $offset=0, $length=10, $extra=NULL) {
    return xapian_query($query_string, NULL, $offset, $length, $extra);
}

/**
 * Wrapper for getting boolean queries
 *
 * @param   string    $query_string   The term string
 */
function dokeos_get_boolean_query($term) {
  return xapian_get_boolean_query($term);
}

/**
 * Wrapper for getting tags
 *
 * @param   int   $count  Number of terms to retrieve. Optional.
 * @return  array
 */
function dokeos_query_get_tags($count=100) {
    return xapian_get_all_terms($count);
}

/**
 * Wrapper for getting specific document tags
 *
 * @param   mixed   Document entry, with apropiate class
 * @return  array
 */
function dokeos_query_tags_for_doc($doc) {
    return xapian_get_doc_terms($doc);
}

/**
 * Preprocess all results depending on the toolid
 */
function dokeos_preprocess_results($results) {
    // group by toolid
    $results_by_tool = array();
    foreach ($results as $key => $row) {
        $results_by_tool[$row['toolid']][] = $row;
    }

    $processed_results = array();
    foreach ($results_by_tool as $toolid => $rows) {
        $tool_processor_class = $toolid .'_processor';
        $tool_processor_path = api_get_path(LIBRARY_PATH) .'search/tool_processors/'. $tool_processor_class .'.class.php';
        if (file_exists($tool_processor_path)) {
            require_once($tool_processor_path);
            $tool_processor = new $tool_processor_class($rows);
            $processed_results = $tool_processor->process();
        }
    }

    return array(count($processed_results), $processed_results);
}

/**
 * Wrapper for join xapian queries
 * 
 * @param XapianQuery|array $query1 
 * @param XapianQuery|array $query2 
 * @param string $op 
 * @return XapianQuery query joined
 */
function dokeos_join_queries($query1, $query2=NULL, $op='or') {
	return xapian_join_queries($query1, $query2, $op);
}

?>
