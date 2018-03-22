<?php

/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.search
 */
require_once 'xapian.php';
//TODO: think another way without including specific fields here
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
define('XAPIAN_DB', api_get_path(SYS_UPLOAD_PATH).'plugins/xapian/searchdb/');

/**
 * Queries the database.
 * The xapian_query function queries the database using both a query string
 * and application-defined terms. Based on drupal-xapian.
 *
 * @param string         $query_string The search string. This string will
 *                                     be parsed and stemmed automatically.
 * @param XapianDatabase $db           Xapian database to connect
 * @param int            $start        An integer defining the first
 *                                     document to return
 * @param int            $length       the number of results to return
 * @param array          $extra        an array containing arrays of
 *                                     extra terms to search for
 * @param int            $count_type   Number of items to retrieve
 *
 * @return array an array of nids corresponding to the results
 */
function xapian_query($query_string, $db = null, $start = 0, $length = 10, $extra = [], $count_type = 0)
{
    try {
        if (!is_object($db)) {
            $db = new XapianDatabase(XAPIAN_DB);
        }

        // Build subqueries from $extra array. Now only used by tags search filter on search widget
        $subqueries = [];
        foreach ($extra as $subquery) {
            if (!empty($subquery)) {
                $subqueries[] = new XapianQuery($subquery);
            }
        }

        $query = null;
        $enquire = new XapianEnquire($db);
        if (!empty($query_string)) {
            $query_parser = new XapianQueryParser();
            //TODO: choose stemmer
            $stemmer = new XapianStem("english");
            $query_parser->set_stemmer($stemmer);
            $query_parser->set_database($db);
            $query_parser->set_stemming_strategy(XapianQueryParser::STEM_SOME);
            $query_parser->add_boolean_prefix('courseid', XAPIAN_PREFIX_COURSEID);
            $query_parser->add_boolean_prefix('toolid', XAPIAN_PREFIX_TOOLID);
            $query = $query_parser->parse_query($query_string);
            $final_array = array_merge($subqueries, [$query]);
            $query = new XapianQuery(XapianQuery::OP_AND, $final_array);
        } else {
            $query = new XapianQuery(XapianQuery::OP_OR, $subqueries);
        }

        $enquire->set_query($query);

        $matches = $enquire->get_mset((int) $start, (int) $length);

        $specific_fields = get_specific_field_list();

        $results = [];
        $i = $matches->begin();

        // Display the results.
        //echo $matches->get_matches_estimated().'results found';

        $count = 0;

        while (!$i->equals($matches->end())) {
            $count++;
            $document = $i->get_document();

            if (is_object($document)) {
                // process one item terms
                $courseid_terms = xapian_get_doc_terms($document, XAPIAN_PREFIX_COURSEID);
                $results[$count]['courseid'] = substr($courseid_terms[0]['name'], 1);
                $toolid_terms = xapian_get_doc_terms($document, XAPIAN_PREFIX_TOOLID);
                $results[$count]['toolid'] = substr($toolid_terms[0]['name'], 1);

                // process each specific field prefix
                foreach ($specific_fields as $specific_field) {
                    $results[$count]['sf-'.$specific_field['code']] = xapian_get_doc_terms($document, $specific_field['code']);
                }

                // rest of data
                $results[$count]['xapian_data'] = unserialize($document->get_data());
                $results[$count]['score'] = ($i->get_percent());
            }
            $i->next();
        }

        switch ($count_type) {
            case 1: // Lower bound
                $count = $matches->get_matches_lower_bound();
                break;

            case 2: // Upper bound
                $count = $matches->get_matches_upper_bound();
                break;

            case 0: // Best estimate
            default:
                $count = $matches->get_matches_estimated();
                break;
        }

        return [$count, $results];
    } catch (Exception $e) {
        display_xapian_error($e->getMessage());

        return null;
    }
}

/**
 * build a boolean query.
 */
function xapian_get_boolean_query($term)
{
    return new XapianQuery($term);
}

/**
 * Retrieve a list db terms.
 *
 * @param int            $count  Number of terms to retrieve
 * @param char           $prefix The prefix of the term to retrieve
 * @param XapianDatabase $db     Xapian database to connect
 *
 * @return array
 */
function xapian_get_all_terms($count = 0, $prefix, $db = null)
{
    try {
        if (!is_object($db)) {
            $db = new XapianDatabase(XAPIAN_DB);
        }

        if (!empty($prefix)) {
            $termi = $db->allterms_begin($prefix);
        } else {
            $termi = $db->allterms_begin();
        }

        $terms = [];
        $i = 0;
        for (; !$termi->equals($db->allterms_end()) && (++$i <= $count || $count == 0); $termi->next()) {
            $terms[] = [
                'frequency' => $termi->get_termfreq(),
                'name' => $termi->get_term(),
            ];
        }

        return $terms;
    } catch (Exception $e) {
        display_xapian_error($e->getMessage());

        return null;
    }
}

/**
 * Retrieve all terms of a document.
 *
 * @param   XapianDocument  document searched
 *
 * @return array
 */
function xapian_get_doc_terms($doc = null, $prefix)
{
    try {
        if (!is_a($doc, 'XapianDocument')) {
            return;
        }

        //TODO: make the filter by prefix on xapian if possible
        //ojwb marvil07: use Document::termlist_begin() and then skip_to(prefix) on the TermIterator
        //ojwb you'll need to check the end condition by hand though
        $terms = [];
        for ($termi = $doc->termlist_begin(); !$termi->equals($doc->termlist_end()); $termi->next()) {
            $term = [
                'frequency' => $termi->get_termfreq(),
                'name' => $termi->get_term(),
            ];
            if ($term['name'][0] === $prefix) {
                $terms[] = $term;
            }
        }

        return $terms;
    } catch (Exception $e) {
        display_xapian_error($e->getMessage());

        return null;
    }
}

/**
 * Join xapian queries.
 *
 * @param XapianQuery|array $query1
 * @param XapianQuery|array $query2
 * @param string            $op
 *
 * @return XapianQuery query joined
 */
function xapian_join_queries($query1, $query2 = null, $op = 'or')
{
    // let decide how to join, avoiding include xapian.php outside
    switch ($op) {
        case 'or':
            $op = XapianQuery::OP_OR;
            break;
        case 'and':
            $op = XapianQuery::OP_AND;
            break;
        default:
            $op = XapianQuery::OP_OR;
            break;
    }

    // review parameters to decide how to join
    if (!is_array($query1)) {
        $query1 = [$query1];
    }
    if (is_null($query2)) {
        // join an array of queries with $op
        return new XapianQuery($op, $query1);
    }
    if (!is_array($query2)) {
        $query2 = [$query2];
    }

    return new XapianQuery($op, array_merge($query1, $query2));
}

/**
 * @author Isaac flores paz <florespaz@bidsoftperu.com>
 *
 * @param string The xapian error message
 *
 * @return string The chamilo error message
 */
function display_xapian_error($xapian_error_message)
{
    $message = explode(':', $xapian_error_message);
    $type_error_message = $message[0];
    if ($type_error_message == 'DatabaseOpeningError') {
        $message_error = get_lang('SearchDatabaseOpeningError');
    } elseif ($type_error_message == 'DatabaseVersionError') {
        $message_error = get_lang('SearchDatabaseVersionError');
    } elseif ($type_error_message == 'DatabaseModifiedError') {
        $message_error = get_lang('SearchDatabaseModifiedError');
    } elseif ($type_error_message == 'DatabaseLockError') {
        $message_error = get_lang('SearchDatabaseLockError');
    } elseif ($type_error_message == 'DatabaseCreateError') {
        $message_error = get_lang('SearchDatabaseCreateError');
    } elseif ($type_error_message == 'DatabaseCorruptError') {
        $message_error = get_lang('SearchDatabaseCorruptError');
    } elseif ($type_error_message == 'NetworkTimeoutError') {
        $message_error = get_lang('SearchNetworkTimeoutError');
    } else {
        $message_error = get_lang('SearchOtherXapianError');
    }
    $display_message = get_lang('Error').' : '.$message_error;
    echo Display::return_message($display_message, 'error');
}
