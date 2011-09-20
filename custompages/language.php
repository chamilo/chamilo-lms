<?php
function get_preferred_language($available_langs) {
	$langs = array();
	foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $httplang) {
		$rawlang = explode(';q=', $httplang);
		if (strpos($rawlang[0], '-') !== FALSE) {
			$rawlang[0] = substr($rawlang[0], 0, strpos($rawlang[0], '-'));
		}
		if (count($rawlang) == 1) {
			$rawlang[1] = 1.0;
		}
		$langs[$rawlang[1]] = $rawlang[0];
	}
	krsort($langs, SORT_NUMERIC);
	foreach($langs as $weight => $code) {
		if (in_array($code, $available_langs)) {
			return $code;
		}
	}
	return null;
}

function cblue_get_lang($variable) {
	return get_lang($variable, null, $_SESSION['user_language_choice']);
}

$language_file = array('courses', 'index', 'registration', 'admin','userInfo');
$available_langs = array('en','fr');
$chamilo_langs = array(null => 'english', 'en' => 'english', 'fr' => 'french', 'nl' => 'dutch', 'de' => 'german', 'es' => 'spanish');
$lang_match = $chamilo_langs[get_preferred_language($available_langs)];
// recover previous value ... 
if (isset($_SESSION['user_language_choice']))
	$lang_match = $_SESSION['user_language_choice'];

// Chamilo parameter, on logout
if (isset($_REQUEST['language']) && !empty($_REQUEST['language']) && in_array($_REQUEST['language'], $chamilo_langs)) {
	$lang_match = $_REQUEST['language'];
}
// Incoming link parameter
if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang']) && in_array($_REQUEST['lang'], $available_langs)) {
	$lang_match = $chamilo_langs[$_REQUEST['lang']];
}
$_user['language'] = $lang_match;
$_SESSION['user_language_choice'] = $lang_match;
?>
