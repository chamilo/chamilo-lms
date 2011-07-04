<?php //$id: $
/**
 * This script defines variables in use in the search plugin for this particular host in the client scripts
 * @package dokeos.search
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
/**
 * Variables
 */
//// Addressing variables
// $search_url is the relative URL from the HTTP root of this portal, to the
// 'searchit.php' script. Something like /plugin/search/client/searchit.php
$search_url = '/plugin/search/client/searchit.php';
// $server_url is the URL of the server containing the search engine XML
// interface (the contents of the server/www directory in this plugin package)
// and, more precisely, the absolute web path to the search.php script
$server_url = 'http://your.domain.com/subdir/search/search.php';

//// Language variables
// The name to be displayed on the 'Search' button
$lang_search_button = 'Search';
// The text to be suffixed to the number of search results found
$lang_search_found = 'r&eacute;sultats trouv&eacute;s.';
// The text to be suffixed to the number of seconds the search took
$lang_seconds = 'secondes';
// the text to be shown if no results were found
$lang_no_result_found = 'La recherche n\'a pas renvoy&eacute; de r&eacute;sultat.';
?>
