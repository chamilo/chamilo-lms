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
 * @return  array
 */
function dokeos_query_query($query_string, $offset=0, $length=10) {
    return xapian_query($query_string, NULL, $offset, $length);
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
?>