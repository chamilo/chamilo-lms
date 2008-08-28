<?php
require_once 'xapian.php';

define('XAPIAN_DB', api_get_path(SYS_PATH).'searchdb/');

/**
 * Queries the database.  
 * The xapian_query function queries the database using both a query string
 * and application-defined terms. Based on drupal-xapian
 * 
 * @param   string          $query_string   The search string. This string will
 *                                          be parsed and stemmed automatically.
 * @param   XapianDatabase  $db             Xapian database to connect
 * @param   int             $start          An integer defining the first 
 *                                          document to return
 * @param   int             $length         The number of results to return.
 * @param   array           $extra          An array containing arrays of 
 *                                          extra terms to search for.
 * @param   int             $count_type     Number of items to retrieve
 * @return  array                           An array of nids corresponding to the results.
 */
function xapian_query($query_string, $db = NULL, $start = 0, $length = 10, 
  $extra = array(), $count_type = 0) {
        
    try {
        if (!is_object($db)) {
            $db = new XapianDatabase(XAPIAN_DB);
        }

        $enquire = new XapianEnquire($db);
        $query_parser = new XapianQueryParser();
        $stemmer = new XapianStem("english");
        $query_parser->set_stemmer($stemmer);
        $query_parser->set_database($db);
        $query_parser->set_stemming_strategy(XapianQueryParser::STEM_SOME);
        $query_parser->add_boolean_prefix('filetype', 'F');
        $query_parser->add_boolean_prefix('tag', 'T');
        $query_parser->add_boolean_prefix('courseid', 'C');
        $query = $query_parser->parse_query($query_string);

        // Build subqueries from $extra array.
        foreach ($extra as $subq) {
          if (!empty($subq)) {
            /* TODO: review if we want to use this constructor
             * deprecated in C: http://xapian.org/docs/apidoc/html/classXapian_1_1Query.html#f85d155b99f1f2007fe75ffc7a8bd51e
             * maybe use: Query (Query::op op_, const Query &left, const Query &right) ?
             */
            $subquery = new XapianQuery(XapianQuery::OP_OR, $subq);
            $query = new XapianQuery(XapianQuery::OP_AND, array($subquery, $query));
          }
        }

        $enquire->set_query($query);
        $matches = $enquire->get_mset((int)$start, (int)$length);

        $results = array();
        $i = $matches->begin();
        $count = 0;
        while (!$i->equals($matches->end())) {
          $count++;
          $document = $i->get_document();
          if (is_object($document)) {
            $results[$count]->ids = ($document->get_data());
            $results[$count]->score = ($i->get_percent());
            $results[$count]->terms = xapian_get_doc_terms($document);
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

        return array($count, $results);
    }
    catch (Exception $e) {
      Display::display_error_message('xapian error message: '. $e->getMessage());
        return NULL;
    }
}

/**
 * Retrieve a list db terms
 * 
 * @param   int             $count  Number of terms to retrieve
 * @param   char            $prefix The prefix of the term to retrieve
 * @param   XapianDatabase  $db     Xapian database to connect
 * @return  array
 */
function xapian_get_all_terms($count=0, $prefix='T', $db=NULL) {
  try {
    if (!is_object($db)) {
        $db = new XapianDatabase(XAPIAN_DB);
    }

    if (!empty($prefix)) {
      $termi= $db->allterms_begin($prefix);
    }
    else {
      $termi= $db->allterms_begin();
    }

    $terms = array();
    $i = 0;
    for ( ; !$termi->equals($db->allterms_end()) && (++$i<=$count || $count==0) ; $termi->next() ) {
      $terms[] = array(
        'frequency' => $termi->get_termfreq(),
        'name' => $termi->get_term(),
      );
    }

    return $terms;
  }
  catch (Exception $e) {
    Display::display_error_message('xapian error message: '. $e->getMessage());
    return NULL;
  }
}

/**
 * Retrieve all terms of a document
 * 
 * @param   XapianDocument  document searched
 * @return  array
 */
function xapian_get_doc_terms($doc=NULL, $prefix='T') {
  try {
    if (!is_a($doc, 'XapianDocument')) {
      return;
    }

    //TODO: make the filter by prefix on xapian if possible
    //ojwb marvil07: use Document::termlist_begin() and then skip_to(prefix) on the TermIterator
    //ojwb you'll need to check the end condition by hand though
    $terms = array();
    for ($termi=$doc->termlist_begin() ; !$termi->equals($doc->termlist_end()); $termi->next() ) {
      $term = array(
        'frequency' => $termi->get_termfreq(),
        'name' => $termi->get_term(),
      );
      if ($term['name'][0] === $prefix) {
        $terms[] = $term;
      }
    }

    return $terms;
  }
  catch (Exception $e) {
    Display::display_error_message('xapian error message: '. $e->getMessage());
    return NULL;
  }
}
?>
