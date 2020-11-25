<?php
/* For licensing terms, see /license.txt */

/**
 * Script defining generic functions against a search engine api. Just only if one day the search engine changes.
 *
 * @package chamilo.include.search
 */
require 'xapian/XapianQuery.php';

/**
 * Wrapper for queries.
 *
 * @param string $query_string The search string
 * @param int    $offset       Offset to the first item to retrieve. Optional
 * @param   int       length          Number of items to retrieve. Optional
 * @param   array     extra           Extra queries to join with. Optional
 *
 * @return array
 */
function chamilo_query_query($query_string, $offset = 0, $length = 10, $extra = null)
{
    list($count, $results) = xapian_query($query_string, null, $offset, $length, $extra);

    return chamilo_preprocess_results($results);
}

function chamilo_query_simple_query($query_string, $offset = 0, $length = 10, $extra = null)
{
    return xapian_query($query_string, null, $offset, $length, $extra);
}

/**
 * Wrapper for getting boolean queries.
 *
 * @param string $term The term string
 */
function chamilo_get_boolean_query($term)
{
    return xapian_get_boolean_query($term);
}

/**
 * Preprocess all results depending on the toolid.
 */
function chamilo_preprocess_results($results)
{
    // group by toolid
    $results_by_tool = [];
    if (count($results) > 0) {
        foreach ($results as $key => $row) {
            $results_by_tool[$row['toolid']][] = $row;
        }

        $processed_results = [];
        foreach ($results_by_tool as $toolid => $rows) {
            $tool_processor_class = $toolid.'_processor';
            $tool_processor_path = api_get_path(LIBRARY_PATH).'search/tool_processors/'.$tool_processor_class.'.class.php';
            if (file_exists($tool_processor_path)) {
                require_once $tool_processor_path;
                $tool_processor = new $tool_processor_class($rows);
                $processed_results = array_merge($tool_processor->process(), $processed_results);
            }
        }

        return [count($processed_results), $processed_results];
    }
}

/**
 * Wrapper for join xapian queries.
 *
 * @param XapianQuery|array $query1
 * @param XapianQuery|array $query2
 * @param string            $op
 *
 * @return XapianQuery query joined
 */
function chamilo_join_queries($query1, $query2 = null, $op = 'or')
{
    return xapian_join_queries($query1, $query2, $op);
}
