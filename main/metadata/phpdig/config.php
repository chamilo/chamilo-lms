<?php
/*
----------------------------------------------------------------------------------
PhpDig Version 1.8.x - See the config file for the full version number.
This program is provided WITHOUT warranty under the GNU/GPL license.
See the LICENSE file for more information about the GNU/GPL license.
Contributors are listed in the CREDITS and CHANGELOG files in this package.
Developer from inception to and including PhpDig v.1.6.2: Antoine Bajolet
Developer from PhpDig v.1.6.3 to and including current version: Charter
Copyright (C) 2001 - 2003, Antoine Bajolet, http://www.toiletoine.net/
Copyright (C) 2003 - current, Charter, http://www.phpdig.net/
Contributors hold Copyright (C) to their code submissions.
Do NOT edit or remove this copyright or licence information upon redistribution.
If you modify code and redistribute, you may ADD your copyright to this notice.
----------------------------------------------------------------------------------
*/

//---------DEFAULT VALUES

// error_reporting(E_ALL);
// @ini_set('error_reporting',E_ALL);

//-------------CONFIGURATION FILE-------
//-------------PHP DIG------------------

// NOTE: If you want a different path, you need to add that path (relative path up to the
// admin directory: ../dir or full path up to the admin directory: /full/path/to/dir) in
// the first if statement in this config.php file - for example:
// && ($relative_script_path != "../dir") // relative path
// && ($relative_script_path != "/full/path/to/dir") // full path
// You may also need to set $relative_script_path to this path in search.php, clickstats.php,
// and function_phpdig_form.php depending on what files you are calling from where
// NOTE: double dot means go back one and single dot means stay in same directory
// NOTE: the path should be UP TO but NOT INCLUDING the admin directory - NO ending slash
/***** Example
* PhpDig installed at: http://www.domain.com/phpdig/
* Want search page at: http://www.domain.com/search.php
* Copy http://www.domain.com/phpdig/search.php to http://www.domain.com/search.php
* Copy http://www.domain.com/phpdig/clickstats.php to http://www.domain.com/clickstats.php
* Set $relative_script_path = './phpdig'; in search.php, clickstats.php, and function_phpdig_form.php
* Add ($relative_script_path != "./phpdig") && to if statement
*****/

define('ABSOLUTE_SCRIPT_PATH','/full/path/to/dir'); // full path up to but not including admin dir, no end slash

if ((!isset($relative_script_path)) || (($relative_script_path != ".") &&
($relative_script_path != "..") && ($relative_script_path != ABSOLUTE_SCRIPT_PATH))) {
  // echo "\n\nPath $relative_script_path not recognized!\n\n";
  exit();
}

// NOTE: If you receive an "undefined index" message that means that your server is not recognizing
// one or some of the $_SERVER variables so check your PHP info and set the $_SERVER variables to
// those recognized by your server: See http://www.php.net/reserved.variables for a list
// If using RSS (config vars below) there are $_SERVER variables in the custom_search_page.php file too

if ((isset($_SERVER['SCRIPT_FILENAME'])) && (eregi("config.php",$_SERVER['SCRIPT_FILENAME']))) {
  exit();
}
if ((isset($_SERVER['SCRIPT_URI'])) && (eregi("config.php",$_SERVER['SCRIPT_URI']))) {
  exit();
}
if ((isset($_SERVER['SCRIPT_URL'])) && (eregi("config.php",$_SERVER['SCRIPT_URL']))) {
  exit();
}
if ((isset($_SERVER['REQUEST_URI'])) && (eregi("config.php",$_SERVER['REQUEST_URI']))) {
  exit();
}
if ((isset($_SERVER['SCRIPT_NAME'])) && (eregi("config.php",$_SERVER['SCRIPT_NAME']))) {
  exit();
}
if ((isset($_SERVER['PATH_TRANSLATED'])) && (eregi("config.php",$_SERVER['PATH_TRANSLATED']))) {
  exit();
}
$self = api_get_self();
if ((isset($self)) && (eregi('config.php',$self))) {
  exit();
}

define('PHPDIG_VERSION','1.8.6');

$phpdig_language = "en"; // ca, cs, da, de, en, es, fr, gr, it, nl, no, pt
if (!isset($phpdig_language)) { $phpdig_language = "en"; }

define('PHPDIG_ADM_AUTH','1');     //Activates/deactivates the authentification functions
define('PHPDIG_ADM_USER','admin'); //Username
define('PHPDIG_ADM_PASS','secret');

// template file and style - checks to see that template is set to a valid value
if (isset($_REQUEST['template_demo'])) { $template_demo = $_REQUEST['template_demo']; }
$templates_array = array('black.html','bluegrey.html','corporate.html','green.html','grey.html','lightgreen.html','linear.html','newspaper.html','phpdig.html','simple.html','terminal.html','yellow.html','gaagle.html');
if(isset($template_demo) && in_array($template_demo, $templates_array)) {
    $template = "$relative_script_path/templates/$template_demo";
} else {
    $template = "$relative_script_path/templates/phpdig.html";
}
$template = "array";  // RH: overrides the above

// template file and style - alternatively force the template value
// $template = "$relative_script_path/templates/phpdig.html";
// if using array, set $template = "array";
// if using classic, set $template = "classic";

// now set $template_demo to clean $template filename or empty string
if (($template != "array") && ($template != "classic")) {
    $template_demo = substr($template,strrpos($template,"/")+1); // get filename.ext from $template variable
} else {
    $template_demo = "";
}

define('HIGHLIGHT_BACKGROUND','#FFBB00');        //Highlighting background color
                                                 //Only for classic mode
define('HIGHLIGHT_COLOR','#000000');             //Highlighting text color
                                                 //Only for classic mode

define('LINK_TARGET','_blank');                  //Target for result links
define('WEIGHT_IMGSRC','./tpl_img/weight.gif');  //Baragraph image path
define('WEIGHT_HEIGHT','5');                     //Baragraph height
define('WEIGHT_WIDTH','50');                     //Max baragraph width

define('SEARCH_PAGE','search.php');              //The name of the search page
define('DISPLAY_DROPDOWN',true);                 //Display dropdown on search page
define('DROPDOWN_URLS',true);                    //Always URLs in dropdown: DISPLAY_DROPDOWN needs to be true

define('SUMMARY_DISPLAY_LENGTH',700);            // RH: was 150 //Max chars displayed in summary
define('SNIPPET_DISPLAY_LENGTH',150);            //Max chars displayed in each snippet

define('DISPLAY_SNIPPETS',true);                 //Display text snippets
define('DISPLAY_SNIPPETS_NUM',4);                //Max snippets to display
define('DISPLAY_SUMMARY',false);                 //Display description

define('PHPDIG_DATE_FORMAT','\1-\2-\3');         // Date format for last update
                                                 // \1 is year, \2 month and \3 day
                                                 // if using rss, use date format \1-\2-\3

define("END_OF_LINE_MARKER","\r\n");             // End of line marker - keep double quotes
define('SEARCH_BOX_SIZE',15);                    // Search box size
define('SEARCH_BOX_MAXLENGTH',50);               // Search box maxlength

//define('PHPDIG_ENCODING','iso-8859-1');  // encoding for interface, search and indexing.
define('PHPDIG_ENCODING', strtolower($charset));
                                         // iso-8859-1, iso-8859-2, iso-8859-7, tis-620,
                                         // and windows-1251 supported in this version.

// replace/edit phpdig_string_subst/phpdig_words_chars for encodings as needed
// note: you may need to alter table keywords modify keyword varchar(64) binary; for certain encodings

$phpdig_string_subst['iso-8859-1'] = 'A:������,a:������,O:������,o:������,E:����,e:����,C:�,c:�,I:����,i:����,U:����,u:����,Y:�,y:��,N:�,n:�';
$phpdig_string_subst['iso-8859-2'] = 'A:���á,C:���,D:��,E:����,I:��,L:ť�,N:��,O:����,R:��,S:���,T:ޫ,U:����,Y:�,Z:���,a:����,c:���,d:��,e:����,i:��,l:嵳,n:��,o:����,r:��,s:���,t:��,u:����,y:�,z:���';
$phpdig_string_subst['iso-8859-6'] = 'Q:Q,q:q';
//$phpdig_string_subst['iso-8859-7'] = '�:��,�:�,�:�,�:�,�:�,�:��,�:�';
$phpdig_string_subst['iso-8859-7'] = '�:���,�:�,�:�,�:�,�:Ÿ�,�:�,�:�ǹ,�:�,�:��ɺ,�:�,�:�,�:�,�:�,�:�,�:��,�:�,�:�,�:��,�:�,�:��վ,�:�,�:�,�:�,�:�ٿ';
$phpdig_string_subst['tis-620'] = 'Q:Q,q:q';
$phpdig_string_subst['windows-1251'] = '�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�,�:�';

$phpdig_words_chars['iso-8859-1'] = '[:alnum:]��ߵ';
$phpdig_words_chars['iso-8859-2'] = '[:alnum:]��ߵ';
$phpdig_words_chars['iso-8859-6'] = '[:alnum:]�������������������������������������������������';
$phpdig_words_chars['iso-8859-7'] = '[:alnum:]�����������������������٢�������������������������������������������';
$phpdig_words_chars['tis-620'] = '[:alnum:]������������_���������������������������������������������������������������������������';
$phpdig_words_chars['windows-1251'] = '[:alnum:]����������������������������������������������������������������';

// start is AND OPERATOR, exact is EXACT PHRASE, and any is OR OPERATOR
define('SEARCH_DEFAULT_MODE','start');  // default search mode (start|exact|any)
// in language pack make the appropriate changes to 'w_begin', 'w_whole', and 'w_part'
// 'w_begin' => 'and operator', 'w_whole' => 'exact phrase', 'w_part' => 'or operator'

define('SEARCH_DEFAULT_LIMIT',10);      //results per page

define('SPIDER_MAX_LIMIT',20);          //max recurse levels in spider
define('RESPIDER_LIMIT',5);             //recurse respider limit for update
define('LINKS_MAX_LIMIT',20);           //max links per each level
define('RELINKS_LIMIT',5);              //recurse links limit for an update

//for limit to directory, URL format must either have file at end or ending slash at end
//e.g., http://www.domain.com/dirs/ (WITH ending slash) or http://www.domain.com/dirs/dirs/index.php
define('LIMIT_TO_DIRECTORY',true);      //limit index to given (sub)directory, no sub dirs of dirs are indexed

define('LIMIT_DAYS',0);                 //default days before reindex a page
define('SMALL_WORDS_SIZE',2);           //words to not index - must be 2 or more
define('MAX_WORDS_SIZE',50);            // RH: was 30 //max word size

define('PHPDIG_EXCLUDE_COMMENT','<!-- phpdigExclude -->');  //comment to exclude a page part
define('PHPDIG_INCLUDE_COMMENT','<!-- phpdigInclude -->');  //comment to include a page part
                                                            // must be on own lines in HTML source
                                                            // text within comments not indexed
                                                            // links within comments still indexed

define('PHPDIG_DEFAULT_INDEX',false);    //phpDig considers /index or /default
                                         //html, htm, php, asp, phtml as the
                                         //same as '/'

define('ALLOW_RSS_FEED',false);                       // Do RSS and display link - if true, set rss dir to 777
$theenc = PHPDIG_ENCODING;                            // needs to be same encoding used in index
$theurl = "http://www.phpdig.net/";                   // site offering the RSS feed
$thetitle = "PhpDig.net";                             // title for site offering the RSS feed
$thedesc = "PhpDig :: Web Spider and Search Engine";  // description of site offering the RSS feed
$thedir = "./rss";                                    // the rss directory name, no ending slash
$thefile = "search.rss";                              // used in rss filenames

define('PHPDIG_SESSID_REMOVE',true);        // remove SIDS or vars from indexed URLS
define('PHPDIG_SESSID_VAR','PHPSESSID,s');  // name of SID or variable to remove
                                            // can be 's' or comma delimited 's,id,var,foo,etc'

define('APPEND_TITLE_META',false);       //append title and meta information to results
define('TITLE_WEIGHT',3);                //relative title weight: APPEND_TITLE_META needs to be true

define('CHUNK_SIZE',1024);               //chunk size for regex processing

define('SUMMARY_LENGTH',500);            //length of results summary

define('TEXT_CONTENT_PATH','text_content/'); //Text content files path
define('CONTENT_TEXT',0);                    // RH: was 1 //Activates/deactivates the
                                             //storage of text content.
define('PHPDIG_IN_DOMAIN',false);            //allows phpdig jump hosts in the same
                                             //domain. If the host is "www.mydomain.tld",
                                             //domain is "mydomain.tld"

define('PHPDIG_LOGS',true);               //write logs
define('SILENCE_404S',true);              //silence 404 output

define('TEMP_FILENAME_LENGTH',8);         //filename length of temp files
// if using external tools with extension, use 4 for filename of length 8

define('NUMBER_OF_RESULTS_PER_SITE',-1);  //max number of results per site
                                          // use -1 to display all results

define('USE_RENICE_COMMAND','1');         //use renice for process priority

//---------EXTERNAL TOOLS SETUP
// if set to true is_executable used - set to '0' if is_executable is undefined
define('USE_IS_EXECUTABLE_COMMAND','0');  // RH: was 1 //use is_executable for external binaries

// if set to true, full path to external binary required
define('PHPDIG_INDEX_MSWORD',false);
define('PHPDIG_PARSE_MSWORD','/usr/local/bin/catdoc');
define('PHPDIG_OPTION_MSWORD','-s 8859-1');

define('PHPDIG_INDEX_PDF',false);
define('PHPDIG_PARSE_PDF','/usr/local/bin/pstotext');
define('PHPDIG_OPTION_PDF','-cork');

define('PHPDIG_INDEX_MSEXCEL',false);
define('PHPDIG_PARSE_MSEXCEL','/usr/local/bin/xls2csv');
define('PHPDIG_OPTION_MSEXCEL','');

define('PHPDIG_INDEX_MSPOWERPOINT',false);
define('PHPDIG_PARSE_MSPOWERPOINT','/usr/local/bin/ppt2text');
define('PHPDIG_OPTION_MSPOWERPOINT','');

//---------EXTERNAL TOOLS EXTENSIONS
// if external binary is not STDOUT or different extension is needed
// for example, use '.txt' if external binary writes to filename.txt
define('PHPDIG_MSWORD_EXTENSION','');
define('PHPDIG_PDF_EXTENSION','');
define('PHPDIG_MSEXCEL_EXTENSION','');
define('PHPDIG_MSPOWERPOINT_EXTENSION','');

//---------FTP SETTINGS
define('FTP_ENABLE',0);//enable ftp content for distant PhpDig
define('FTP_HOST','<ftp host>'); //if distant PhpDig, ftp host;
define('FTP_PORT',21); //ftp port
define('FTP_PASV',1); //passive mode
define('FTP_PATH','<path to phpdig directory>'); //distant path from the ftp root
define('FTP_TEXT_PATH','text_content');//ftp path to text-content directory
define('FTP_USER','<ftp usename>');
define('FTP_PASS','<ftp password>');

//--------CRON SETTINGS
define('CRON_ENABLE',false);
define('CRON_EXEC_FILE','/usr/bin/crontab');
define('CRON_CONFIG_FILE',ABSOLUTE_SCRIPT_PATH.'/admin/temp/cronfile.txt');
define('PHPEXEC','/usr/local/bin/php');
// NOTE: make sure ABSOLUTE_SCRIPT_PATH is the full path up to but not including the admin dir, no ending slash
// NOTE: CRON_ENABLE set to true writes a file at CRON_CONFIG_FILE containing the cron job information
// The CRON_CONFIG_FILE must be 777 permissions if applicable to your OS/setup
// You still need to call the CRON_CONFIG_FILE to run the cron job!!!
// From shell: crontab CRON_CONFIG_FILE to set the cron job: replace CRON_CONFIG_FILE with actual file
// From shell: crontab -l to list and crontab -d to delete

// regular expression to ban useless external links in index
define('BANNED','^ad\.|banner|doubleclick');

// regexp forbidden extensions - return sometimes text/html mime-type !!!
define('FORBIDDEN_EXTENSIONS','\.(rm|ico|cab|swf|css|gz|z|tar|zip|tgz|msi|arj|zoo|rar|r[0-9]+|exe|bin|pkg|rpm|deb|bz2)$');

//----------HTML ENTITIES
$spec = array( "&amp" => "&",
               "&agrave" => "�",
               "&egrave" => "�",
               "&ugrave" => "�",
               "&oacute;" => "�",
               "&eacute" => "�",
               "&icirc" => "�",
               "&ocirc" => "�",
               "&ucirc" => "�",
               "&ecirc" => "�",
               "&ccedil" => "�",
               "&#156" => "oe",
               "&gt" => " ",
               "&lt" => " ",
               "&deg" => " ",
               "&apos" => "'",
               "&quot" => " ",
               "&acirc" => "�",
               "&iuml" => "�",
               "&euml" => "�",
               "&auml" => "�",
               "&Auml" => "�",
               "&Euml" => "�",
               "&Iuml" => "�",
               "&Uuml" => "�",
               "&ouml" => "�",
               "&uuml" => "�",
               "&nbsp" => " ",
               "&szlig" => "�",
               "&iacute" => "�",
               "&reg" => " ",
               "&copy" => " ",
               "&aacute" => "�",
               "&Aacute" => "�",
               "&eth" => "�",
               "&ETH" => "�",
               "&Eacute" => "�",
               "&Iacute" => "�",
               "&Oacute" => "�",
               "&uacute" => "�",
               "&Uacute" => "�",
               "&THORN" => "�",
               "&thorn" => "�",
               "&Ouml" => "�",
               "&aelig" => "�",
               "&AELIG" => "�",
               "&aring" => "�",
               "&Aring" => "�",
               "&oslash" => "�",
               "&Oslash" => "�"
               );

//month names in iso dates
$month_names = array ('jan'=>1,
                      'feb'=>2,
                      'mar'=>3,
                      'apr'=>4,
                      'may'=>5,
                      'jun'=>6,
                      'jul'=>7,
                      'aug'=>8,
                      'sep'=>9,
                      'oct'=>10,
                      'nov'=>11,
                      'dec'=>12
                      );

//apache multi indexes parameters
$apache_indexes = array (  "?N=A" => 1,
                           "?N=D" => 1,
                           "?M=A" => 1,
                           "?M=D" => 1,
                           "?S=A" => 1,
                           "?S=D" => 1,
                           "?D=A" => 1,
                           "?D=D" => 1,
                           "?C=N&amp;O=A" => 1,
                           "?C=M&amp;O=A" => 1,
                           "?C=S&amp;O=A" => 1,
                           "?C=D&amp;O=A" => 1,
                           "?C=N&amp;O=D" => 1,
                           "?C=M&amp;O=D" => 1,
                           "?C=S&amp;O=D" => 1,
                           "?C=D&amp;O=D" => 1);

//includes language file
define('PHPDIG_LANG_CONSTANT',$phpdig_language); // this line for classic
if (is_file("$relative_script_path/locales/$phpdig_language-language.php")) {
    include "$relative_script_path/locales/$phpdig_language-language.php";
}
elseif (is_file("$relative_script_path/locales/en-language.php")) {
    include "$relative_script_path/locales/en-language.php";
}
else {
    die("Unable to select language pack.\n");
}

//connection to database
if ((!isset($no_connect)) || ($no_connect != 1)) {
    if (is_file("$relative_script_path/includes/connect.php")) {
        include "$relative_script_path/includes/connect.php";
    }
    else {
        die("Unable to find connect.php file.\n");
    }
}

//includes of libraries
if (is_file("$relative_script_path/libs/phpdig_functions.php")) {
    include "$relative_script_path/libs/phpdig_functions.php";
}
else {
    die ("Unable to find phpdig_functions.php file.\n");
}
if (is_file("$relative_script_path/libs/function_phpdig_form.php")) {
    include "$relative_script_path/libs/function_phpdig_form.php";
}
else {
    die ("Unable to find function_phpdig_form.php file.\n");
}
if (is_file("$relative_script_path/libs/mysql_functions.php")) {
    include "$relative_script_path/libs/mysql_functions.php";
}
else {
    die ("Unable to find mysql_functions.php file.\n");
}
if ((!isset($template)) || ((!is_file($template)) && ($template != "array") && ($template != "classic"))) {
    die ("Unable to render template file.\n");
}

if (!defined('CONFIG_CHECK')) {
  exit();
}

// parse encodings (create global $phpdigEncode);
phpdigCreateSubstArrays($phpdig_string_subst);
// send encoding if needed
if (!headers_sent()) {
   header('Content-type:text/html; Charset='.PHPDIG_ENCODING);
}
// turn off magic_quotes_runtime for escaping purposes
@ini_set('magic_quotes_runtime',false);
// turn off magic_quotes_sybase for escaping purposes
@ini_set('magic_quotes_sybase',false);
if ((!isset($no_connect)) || ($no_connect != 1)) {
     phpdigCheckTables($id_connect,array('engine',
                                    'excludes',
                                    'keywords',
                                    'sites',
                                    'spider',
                                    'tempspider',
                                    'logs',
                                    'clicks',
                                    'site_page',
                                    'includes'));
}
?>
