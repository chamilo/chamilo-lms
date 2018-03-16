<?php

/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.search
 */
require_once __DIR__.'/../../global.inc.php';

/**
 * Class wrapper.
 *
 * @package chamilo.include.search
 */
class ChamiloIndexer extends XapianIndexer
{
    /**
     * Set terms on search_did given.
     *
     * @param string $terms_string        Comma-separated list of terms from input form
     * @param string $prefix              Search engine prefix
     * @param string $course_code         Course code
     * @param string $tool_id             Tool id from mainapi.lib.php
     * @param int    $ref_id_high_level   Main id of the entity to index (Ex. lp_id)
     * @param int    $ref_id_second_level Secondary id of the entity to index (Ex. lp_item)
     * @param int    $search_did          Search engine document id from search_engine_ref table
     *
     * @return bool False on error or nothing to do, true otherwise
     */
    public function set_terms(
        $terms_string,
        $prefix,
        $course_code,
        $tool_id,
        $ref_id_high_level,
        $ref_id_second_level,
        $search_did
    ) {
        $terms_string = trim($terms_string);
        $terms = explode(',', $terms_string);
        array_walk($terms, 'trim_value');

        $stored_terms = $this->get_terms_on_db($prefix, $course_code, $tool_id, $ref_id_high_level);

        // don't do anything if no change, verify only at DB, not the search engine
        if ((count(array_diff($terms, $stored_terms)) == 0) &&
            (count(array_diff($stored_terms, $terms)) == 0)
        ) {
            return false;
        }

        require_once api_get_path(LIBRARY_PATH).'search/xapian/XapianQuery.php';

        // compare terms
        $doc = $this->get_document($search_did);
        $xapian_terms = xapian_get_doc_terms($doc, $prefix);
        $xterms = [];
        foreach ($xapian_terms as $xapian_term) {
            $xterms[] = substr($xapian_term['name'], 1);
        }

        $dterms = $terms;

        $missing_terms = array_diff($dterms, $xterms);
        $deprecated_terms = array_diff($xterms, $dterms);

        // save it to search engine
        foreach ($missing_terms as $term) {
            $this->add_term_to_doc($prefix.$term, $doc);
        }
        foreach ($deprecated_terms as $term) {
            $this->remove_term_from_doc($prefix.$term, $doc);
        }

        // don't do anything if no change
        if ((count($missing_terms) > 0) || (count($deprecated_terms) > 0)) {
            $this->replace_document($doc, (int) $search_did);
        }

        return true;
    }

    /**
     * Get the terms stored at database.
     *
     * @return array Array of terms
     */
    public function get_terms_on_db($prefix, $course_code, $tool_id, $ref_id)
    {
        require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
        $terms = get_specific_field_values_list_by_prefix(
            $prefix,
            $course_code,
            $tool_id,
            $ref_id
        );
        $prefix_terms = [];
        foreach ($terms as $term) {
            $prefix_terms[] = $term['value'];
        }

        return $prefix_terms;
    }
}
