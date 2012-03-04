<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.search
 */
/**
 * Base class to make tool processors
 *
 * This processor have to prepare the raw data from the search engine api to
 * make it usable by search. See some implementations of these classes if you
 * want to make one.
 *
 * Classes that extends this one should be named like: TOOL_<toolname> on
 * TOOL_<toolname>.class.php
 * See lp_list_search for an example of calling the process.
 * @package chamilo.include.search
 */
abstract class search_processor {
    /**
     * Search engine api results
     */
    protected $rows = array();

    /**
     * Process the data sorted by the constructor
     */
    abstract protected function process();
}