<?php

/* For licensing terms, see /license.txt */

require_once 'xapian.php';
// TODO: think another way without including specific fields here
require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

use Chamilo\CoreBundle\Framework\Container;

/**
 * Legacy default index path (Chamilo 1).
 *
 * In Chamilo 2, we will prefer the Symfony SearchIndexPathResolver
 * but we keep this constant as a fallback for older installs.
 */
define('XAPIAN_DB', api_get_path(SYS_UPLOAD_PATH) . 'plugins/xapian/searchdb/');

/**
 * Returns a XapianDatabase instance using the configured index directory.
 *
 * In Chamilo 2, this will prefer the Symfony SearchIndexPathResolver
 * (var/search or whatever is configured) and fall back to the legacy
 * upload path when needed.
 *
 * @param XapianDatabase|null $db Existing database instance (optional)
 *
 * @return XapianDatabase
 *
 * @throws Exception If the database cannot be opened
 */
function xapian_get_database($db = null)
{
    if ($db instanceof XapianDatabase) {
        return $db;
    }

    // Default: legacy path (Chamilo 1 behavior)
    $path = XAPIAN_DB;

    // If Chamilo 2 container is available, try to use the new index dir
    if (class_exists(Container::class)) {
        try {
            /** @var \Chamilo\CoreBundle\Search\Xapian\SearchIndexPathResolver $resolver */
            $resolver = Container::getSearchIndexPathResolver();
            $resolver->ensureIndexDirectoryExists();
            $path = $resolver->getIndexDir();
        } catch (\Throwable $e) {
            // Fallback to legacy path if resolver or container are not available.
            // This keeps backward compatibility and avoids hard failures.
        }
    }

    return new XapianDatabase($path);
}

/**
 * Queries the database.
 * The xapian_query function queries the database using both a query string
 * and application-defined terms. Based on drupal-xapian.
 *
 * @param string              $query_string The search string. This string will
 *                                          be parsed and stemmed automatically.
 * @param XapianDatabase|null $db           Xapian database to connect
 * @param int                 $start        An integer defining the first
 *                                          document to return
 * @param int                 $length       The number of results to return
 * @param array               $extra        An array containing arrays of
 *                                          extra terms to search for
 * @param int                 $count_type   How to compute the match count:
 *                                          0 = best estimate,
 *                                          1 = lower bound,
 *                                          2 = upper bound
 *
 * @return array|null [int $count, array $results] or null on error
 */
function xapian_query($query_string, $db = null, $start = 0, $length = 10, $extra = [], $count_type = 0)
{
    try {
        $db = xapian_get_database($db);

        // Build subqueries from $extra array. Now only used by tags search filter on search widget.
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
            // TODO: choose stemmer based on platform/user language if needed
            $stemmer = new XapianStem('english');
            $query_parser->set_stemmer($stemmer);
            $query_parser->set_database($db);
            $query_parser->set_stemming_strategy(XapianQueryParser::STEM_SOME);
            $query_parser->add_boolean_prefix('courseid', XAPIAN_PREFIX_COURSEID);
            $query_parser->add_boolean_prefix('toolid', XAPIAN_PREFIX_TOOLID);

            $parsedQuery = $query_parser->parse_query($query_string);
            $final_array = array_merge($subqueries, [$parsedQuery]);
            $query = new XapianQuery(XapianQuery::OP_AND, $final_array);
        } else {
            // No free-text query: OR all subqueries (e.g. tag-only search)
            $query = new XapianQuery(XapianQuery::OP_OR, $subqueries);
        }

        $enquire->set_query($query);

        $matches = $enquire->get_mset((int) $start, (int) $length);

        $specific_fields = get_specific_field_list();

        $results = [];
        $i = $matches->begin();

        $count = 0;

        while (!$i->equals($matches->end())) {
            $count++;
            $document = $i->get_document();

            if (is_object($document)) {
                // Process one item terms (course id, tool id)
                $courseid_terms = xapian_get_doc_terms($document, XAPIAN_PREFIX_COURSEID);
                $results[$count]['courseid'] = substr($courseid_terms[0]['name'], 1);

                $toolid_terms = xapian_get_doc_terms($document, XAPIAN_PREFIX_TOOLID);
                $results[$count]['toolid'] = substr($toolid_terms[0]['name'], 1);

                // Process each specific field prefix
                foreach ($specific_fields as $specific_field) {
                    $results[$count]['sf-' . $specific_field['code']] = xapian_get_doc_terms(
                        $document,
                        $specific_field['code']
                    );
                }

                // Rest of data
                $results[$count]['xapian_data'] = unserialize($document->get_data());
                $results[$count]['score'] = $i->get_percent();
            }

            $i->next();
        }

        // Compute match count according to requested type
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
 * Build a boolean query.
 *
 * @param string $term The term string
 *
 * @return XapianQuery
 */
function xapian_get_boolean_query($term)
{
    return new XapianQuery($term);
}

/**
 * Retrieve a list of database terms.
 *
 * @param int                 $count  Number of terms to retrieve (0 means "no limit")
 * @param string              $prefix The prefix of the term to retrieve
 * @param XapianDatabase|null $db     Xapian database to connect
 *
 * @return array|null
 */
function xapian_get_all_terms($count = 0, $prefix, $db = null)
{
    try {
        $db = xapian_get_database($db);

        if (!empty($prefix)) {
            $termi = $db->allterms_begin($prefix);
        } else {
            $termi = $db->allterms_begin();
        }

        $terms = [];
        $i = 0;

        for (; !$termi->equals($db->allterms_end()) && (++$i <= $count || $count === 0); $termi->next()) {
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
 * Retrieve all terms of a document filtered by prefix.
 *
 * @param XapianDocument|null $doc    Document to inspect
 * @param string              $prefix Prefix used to filter the terms
 *
 * @return array|null
 */
function xapian_get_doc_terms($doc = null, $prefix)
{
    try {
        if (!is_a($doc, 'XapianDocument')) {
            return null;
        }

        // TODO: make the filter by prefix on xapian if possible
        // ojwb marvil07: use Document::termlist_begin() and then skip_to(prefix) on the TermIterator
        // ojwb you'll need to check the end condition by hand though
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
 * Join Xapian queries.
 *
 * @param XapianQuery|array      $query1 First query or array of queries
 * @param XapianQuery|array|null $query2 Second query or array of queries (optional)
 * @param string                 $op     Logical operator: 'or' or 'and'
 *
 * @return XapianQuery
 */
function xapian_join_queries($query1, $query2 = null, $op = 'or')
{
    // Decide how to join, avoiding including xapian.php outside
    switch ($op) {
        case 'and':
            $op = XapianQuery::OP_AND;
            break;
        case 'or':
        default:
            $op = XapianQuery::OP_OR;
            break;
    }

    // Normalize parameters to arrays
    if (!is_array($query1)) {
        $query1 = [$query1];
    }

    if ($query2 === null) {
        // Join an array of queries with $op
        return new XapianQuery($op, $query1);
    }

    if (!is_array($query2)) {
        $query2 = [$query2];
    }

    return new XapianQuery($op, array_merge($query1, $query2));
}

/**
 * Maps Xapian errors to human-readable messages.
 *
 * @author Isaac flores paz <florespaz@bidsoftperu.com>
 *
 * @param string $xapian_error_message The Xapian error message
 *
 * @return void
 */
function display_xapian_error($xapian_error_message)
{
    $message = explode(':', $xapian_error_message);
    $type_error_message = $message[0];

    if ($type_error_message === 'DatabaseOpeningError') {
        $message_error = get_lang('Failed to open the search database');
    } elseif ($type_error_message === 'DatabaseVersionError') {
        $message_error = get_lang('The search database uses an unsupported format');
    } elseif ($type_error_message === 'DatabaseModifiedError') {
        $message_error = get_lang('The search database has been modified/broken');
    } elseif ($type_error_message === 'DatabaseLockError') {
        $message_error = get_lang('Failed to lock the search database');
    } elseif ($type_error_message === 'DatabaseCreateError') {
        $message_error = get_lang('Failed to create the search database');
    } elseif ($type_error_message === 'DatabaseCorruptError') {
        $message_error = get_lang('The search database has suffered corruption');
    } elseif ($type_error_message === 'NetworkTimeoutError') {
        $message_error = get_lang('Connection timed out while communicating with the remote search database');
    } else {
        $message_error = get_lang('Error in search engine');
    }

    $display_message = get_lang('Error') . ' : ' . $message_error;
    echo Display::return_message($display_message, 'error');
}
