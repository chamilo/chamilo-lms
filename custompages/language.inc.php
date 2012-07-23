<?php
/* For licensing terms, see /license.txt */
/**
 * CustomPages : Browser language detection
 * Include this file in your custom page if you want to set the language variable of the Chamilo session to the best pick according to the visitor's browser's options.
 * 2011, Jean-Karim Bockstael, CBlue <jeankarim@cblue.be>
 * This requires the Chamilo system to be initialized
 * (note that it's easier to do the following include in the parent page)
 * @package chamilo.custompages
 */
/**
 * Returns the best match between available languages and visitor preferences
 * @return string the best match as 2-chars code, null when none match
*/
function get_preferred_language($available_langs) {
    // Parsing the Accept-languages HTTP header
    $langs = array();
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $httplang) {
        $rawlang = explode(';q=', $httplang);
        if (strpos($rawlang[0], '-') !== FALSE) {
            // We ignore the locale part, as in en-GB vs en-US
            $rawlang[0] = substr($rawlang[0], 0, strpos($rawlang[0], '-'));
        }
        if (count($rawlang) == 1) {
            $rawlang[1] = 1.0; // The absence of weighting means a weight of 1 (max)
        }
        $langs[$rawlang[1]][] = $rawlang[0];
    }
    krsort($langs, SORT_NUMERIC);
    // Choosing the best match
    foreach($langs as $weight => $codes) {
		foreach ($codes as $code) {
        	if (in_array($code, $available_langs)) {
            	return $code;
        	}
		}
    }
    // No match
    return null;
}

/**
 * Wrapper function for the get_lang function
 * use this if you want to avoid translation caching issues
 */
function cp_get_lang($variable) {
	return get_lang($variable, null, $_SESSION['user_language_choice']);
}
/**
 * Code
 */
// Note that Chamilo languages are expressed as full english names, not 2-characters codes
// e.g. 'english' instead of 'en', 'french' instead of 'fr', ...
// We need a matching array. Note the value for the null key, which is the default language.
// Also note that this is an example matchin array, not all languages are present.
$chamilo_langs = array(null => 'english', 'en' => 'english', 'fr' => 'french', 'es' => 'spanish');

// Which of these can we actually pick from ?
$available_langs = array('en','fr');

// Which language files will we need ?
$language_file = array('courses', 'index', 'registration', 'admin','userInfo');

// Let's find out which language to serve to this particular browser
$lang_match = $chamilo_langs[get_preferred_language($available_langs)];

// Chamilo overrides this parameters at some places, e.g. in the logout link
if (isset($_REQUEST['language']) && !empty($_REQUEST['language']) && in_array($_REQUEST['language'], $chamilo_langs)) {
	$lang_match = $_REQUEST['language'];
}

// Maybe a language had already been selected, we should honor this
if (isset($_SESSION['user_language_choice']) && in_array($_SESSION['user_language_choice'], $chamilo_langs)) {
	$lang_match = $_SESSION['user_language_choice'];
}

// We need to set the relevant session variables to the best match, to use Chamilo's i18n lib.
$_user['language'] = $lang_match;
$_SESSION['user_language_choice'] = $lang_match;
?>
