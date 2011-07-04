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

define('CONFIG_CHECK','check'); // do not edit this line

//-------------UTILS FUNCTIONS

//=================================================
// extract _POST or _GET variables from a list varname => vartype
// Useful for error_reporting E_ALL too, init variables
// usage in script : extract(phpdigHttpVars(array('foobar'=>'string')));
function phpdigHttpVars($varray=array()) {
$parse_orders = array('_POST','_GET','HTTP_POST_VARS','HTTP_GET_VARS');
$httpvars = array();
// extract the right array
if (is_array($varray)) {
    foreach($parse_orders as $globname) {
          global $$globname;
          if (!count($httpvars) && isset($$globname) && is_array($$globname)) {
              $httpvars = $$globname;
          }
    }
    // extract or create requested vars
    foreach($varray as $varname => $vartype) {
       if (in_array($vartype,array('integer','bool','double','float','string','array')) ) {
         if (!isset($httpvars[$varname])) {
            if (!isset($GLOBALS[$varname])) {
                 $httpvars[$varname] = false;
            }
            else {
                 $httpvars[$varname] = $GLOBALS[$varname];
            }
         }
         settype($httpvars[$varname],$vartype);
       }
    }
return $httpvars;
}
}

//=================================================
// timer for profiling
class phpdigTimer {
      var $time = 0;
      var $mode = '';
      var $marks = array();
      var $template = '';

      function phpdigTimer($mode='html') {
           $this->time = $this->getTime();
           if ($mode == 'cli') {
               $this->template = "%s:\t%0.9f s. \n";
           }
           else {
               $this->template = "<tr><td class=\"greyForm\">%s</td><td class=\"greyForm\">%0.9f s. </td></tr>\n";
           }
      }
      function start($name) {
           if (!isset($this->marks[$name])) {
               $this->marks[$name]['time'] = $this->getTime();
               $this->marks[$name]['stat'] = 'r';
           }
           else if ($this->marks[$name]['stat'] == 's') {
               $this->marks[$name]['time'] = $this->getTime()-$this->marks[$name]['time'];
               $this->marks[$name]['stat'] = 'r';
           }
      }
      function stop($name) {
           if (isset($this->marks[$name]) && $this->marks[$name]['stat'] == 'r') {
               $this->marks[$name]['time'] = $this->getTime()-$this->marks[$name]['time'];
           }
           else {
               $this->marks[$name]['time'] = 0;
           }
           $this->marks[$name]['stat'] = 's';
      }
      function display() {
           if ($this->mode != 'cli') {
               print "<table class=\"borderCollapse\"><tr><td class=\"blueForm\">Mark</td><td class=\"blueForm\">Value</td></tr>\n";
           }
           foreach($this->marks as $name => $value) {
                printf($this->template,ucwords($name),$value['time']);
           }
           if ($this->mode != 'cli') {
               print "</table>\n";
           }
      }
      // increase precision with deltime
      function getTime() {
          return array_sum(explode(' ',microtime()))-$this->time;
      }
}

//-------------STRING FUNCTIONS

//=================================================
//returns a localized string
function phpdigMsg($string='') {
global $phpdig_mess;
if (isset($phpdig_mess[$string])) {
    return nl2br($phpdig_mess[$string]);
}
else {
    return ucfirst($string);
}
}

//=================================================
//print a localized string
function phpdigPrnMsg($string='') {
global $phpdig_mess;
if (isset($phpdig_mess[$string])) {
    print nl2br($phpdig_mess[$string]);
}
else {
    print ucfirst($string);
}
}

//=================================================
//load the common words in an array
function phpdigComWords($file='')
{
$lines = @file($file);
if (is_array($lines))
    {
    while (list($id,$word) = each($lines))
           $common[trim($word)] = 1;
    }
else
    $common['aaaa'] = 1;
return $common;
}

//=================================================
//highlight a string part
function phpdigHighlight($ereg='',$string='')
{
if ($ereg) {
    $string = @eregi_replace($ereg,"\\1<^#_>\\2</_#^>\\3",@eregi_replace($ereg,"\\1<^#_>\\2</_#^>\\3",$string));
    $string = str_replace("^#_","span class=\"phpdigHighlight\"",str_replace("_#^","span",$string));
    return $string;
}
else {
    return null;
}
}

//=================================================
//replace all characters with an accent
function phpdigStripAccents($chaine,$encoding=PHPDIG_ENCODING) {
global $phpdigEncode;
if (!isset($phpdigEncode[$encoding])) {
   $encoding = PHPDIG_ENCODING;
}
// exceptions
if ($encoding == 'iso-8859-1') {
    $chaine = str_replace('�','ae',str_replace('�','ae',$chaine));
}
return( strtr( $chaine,$phpdigEncode[$encoding]['str'],$phpdigEncode[$encoding]['tr']) );
}

//==========================================
//Create a ereg for highlighting
function phpdigPregQuotes($chaine,$encoding=PHPDIG_ENCODING) {
global $phpdigEncode;
if (!isset($phpdigEncode[$encoding])) {
   $encoding = PHPDIG_ENCODING;
}
$chaine = preg_quote(strtolower(phpdigStripAccents($chaine,$encoding)));
return  str_replace($phpdigEncode[$encoding]['char'],$phpdigEncode[$encoding]['ereg'],$chaine);
}

//=================================================
// Create Useful arrays for different encodings
function phpdigCreateSubstArrays($subststrings) {
$phpdigEncode = array();
global $phpdigEncode;

foreach($subststrings as $encoding => $subststring) {
    $tempArray = explode(',',$subststring);
    if (!isset($phpdigEncode[$encoding])) {
       $phpdigEncode[$encoding] = array();
    }
    $phpdigEncode[$encoding]['str'] = '';
    $phpdigEncode[$encoding]['tr'] = '';
    $phpdigEncode[$encoding]['char'] = array();
    $phpdigEncode[$encoding]['ereg'] = array();
    foreach ($tempArray as $tempSubstitution) {
         $chrs = explode(':',$tempSubstitution);
         $phpdigEncode[$encoding]['char'][strtolower($chrs[0])] = strtolower($chrs[0]);
         settype($phpdigEncode[$encoding]['ereg'][strtolower($chrs[0])],'string');
         $phpdigEncode[$encoding]['ereg'][strtolower($chrs[0])] .= $chrs[0].$chrs[1];
         for($i=0; $i < strlen($chrs[1]); $i++) {
             $phpdigEncode[$encoding]['str'] .= $chrs[1][$i];
             $phpdigEncode[$encoding]['tr']  .= $chrs[0];
         }
    }
    foreach($phpdigEncode[$encoding]['ereg'] as $id => $ereg) {
         $phpdigEncode[$encoding]['ereg'][$id] = '['.$ereg.']';
    }
}
}

//=================================================
//epure a string from all non alnum words (words can contain &__&��� character)
function phpdigEpureText($text,$min_word_length=2,$encoding=PHPDIG_ENCODING) {
global $phpdig_words_chars;

$text = phpdigStripAccents(strtolower ($text));
//no-latin upper to lowercase - now islandic
switch (PHPDIG_ENCODING) {
   case 'iso-8859-1':
   $text = strtr( $text,'��','��');
   break;
}

// RH   ereg_replace('[^'.$phpdig_words_chars[$encoding].' \'._~@#$:&%/;,=-]+',' ',$text);
$text = ereg_replace('[^'.$phpdig_words_chars[$encoding].' \'._~@#$&%/=-]+',' ',$text);

// RH   ereg_replace('(['.$phpdig_words_chars[$encoding].'])[\'._~@#$:&%/;,=-]+($|[[:space:]]$|[[:space:]]['.$phpdig_words_chars[$encoding].'])','\1 \2',$text);
$text = ereg_replace('(['.$phpdig_words_chars[$encoding].'])[\'._~@#$&%/=-]+($|[[:space:]]$|[[:space:]]['.$phpdig_words_chars[$encoding].'])','\1 \2',$text);

// the next two repeated lines needed
if ($min_word_length >= 1) {
  $text = ereg_replace('[[:space:]][^ ]{1,'.$min_word_length.'}[[:space:]]',' ',' '.$text.' ');
  $text = ereg_replace('[[:space:]][^ ]{1,'.$min_word_length.'}[[:space:]]',' ',' '.$text.' ');
}

$text = ereg_replace('\.{2,}',' ',$text);
$text = ereg_replace('^[[:space:]]*\.+',' ',$text);

return trim(ereg_replace("[[:space:]]+"," ",$text));
}

//-------------SQL FUNCTIONS

//=================================================
//insert an entry in logs
function phpdigAddLog ($id_connect,$option='start',$includes=array(),$excludes=array(),$num_results=0,$time=0) {
    if (!is_array($excludes)) {
         $excludes = array();
    }
    sort($excludes);
    if (!is_array($includes)) {
         $includes = array();
    }
    sort($includes);
    $query = 'INSERT INTO '.PHPDIG_DB_PREFIX.'logs (l_num,l_mode,l_ts,l_includes,l_excludes,l_time) '
             .'VALUES ('.$num_results.',\''.substr($option,0,1).'\',NOW(),'
             .'\''.implode(' ',$includes).'\',\''.implode(' ',$excludes).'\','.(double)$time.')';
    mysql_query($query,$id_connect);
    return mysql_insert_id($id_connect);
}

?>
