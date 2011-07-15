<?php
/* For licensing terms, see /license.txt */

/**
 * The following table contains two types of conventions concerning person names:
 *
 * "format" - determines how a full person name to be formatted, i.e. in what order the title, the first_name and the last_name to be placed.
 * You maight need to correct the value for your language. The possible values are:
 * title first_name last_name  - Western order;
 * title last_name first_name  - Eastern order;
 * title last_name, first_name - Western libraries order.
 * Placing the title (Dr, Mr, Miss, etc) depends on the tradition in you country.
 * @link http://en.wikipedia.org/wiki/Personal_name#Naming_convention
 *
 * "sort_by" - determines you preferable way of sorting person names. The possible values are:
 * first_name                  - sorting names with priority for the first name;
 * last_name                   - sorting names with priority for the last name.
 */
return array(
	'afrikaans' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'albanian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'alemannic' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'amharic' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'armenian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'arabic' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'asturian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'bosnian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'brazilian' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'breton' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'bulgarian' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'catalan' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'croatian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'czech' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'danish' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'dari' =>             array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'dutch' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'english' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'esperanto' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'estonian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'euskera' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'finnish' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'french' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'frisian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'friulian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'galician' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'georgian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'german' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'greek' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'hawaiian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'hebrew' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'hindi' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'hungarian' =>        array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'icelandic' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'indonesian' =>       array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'irish' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'italian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'japanese' =>         array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'korean' =>           array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'latin' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'latvian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'lithuanian' =>       array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'macedonian' =>       array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'malay' =>            array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'manx' =>             array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'marathi' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'middle_frisian' =>   array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'mingo' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'nepali' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'norwegian' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'occitan' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'pashto' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'persian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'polish' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'portuguese' =>       array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'quechua_cusco' =>    array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'romanian' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'rumantsch' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'russian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'sanskrit' =>         array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'serbian' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'serbian_cyrillic' => array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'simpl_chinese' =>    array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'slovak' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'slovenian' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),

	//'spanish' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'last_name'  ),
	//'spanish' =>          array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'  ),
	// Some experimental settings for Spanish language:
	//'spanish' =>          array(  'format' => 'title first_name LAST_NAME',  'sort_by' => 'first_name'  ), // Western order, last name is uppercase when a full name is assembled
	//'spanish' =>          array(  'format' => 'title first_name LAST_NAME',  'sort_by' => 'last_name'   ), // Western order, last name is uppercase when a full name is assembled
	'spanish' =>          array(  'format' => 'title last_name, first_name',  'sort_by' => 'last_name'  ), // Library order
	//'spanish' =>          array(  'format' => 'title LAST_NAME, first_name',  'sort_by' => 'last_name'  ), // Library order, last name is uppercase when a full name is assembled

	'swahili' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'swedish' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'tagalog' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'tamil' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'thai' =>             array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'trad_chinese' =>     array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'turkce' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'ukrainian' =>        array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'vietnamese' =>       array(  'format' => 'title last_name first_name',  'sort_by' => 'last_name'   ), // Eastern order
	'welsh' =>            array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'yiddish' =>          array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  ),
	'yoruba' =>           array(  'format' => 'title first_name last_name',  'sort_by' => 'first_name'  )
);
