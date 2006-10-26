<?php

require_once dirname(__FILE__)."/utf8_char2byte_pos.php";

/**
 * Returns a part of a UTF-8 string.
 * Unit-tested by Kasper and works 100% like substr() / mb_substr() for full range of $start/$len
 * (http://phpxref.com/xref/moodle/lib/typo3/class.t3lib_cs.php.source.html.gz)
 *
 * @param    string        UTF-8 string
 * @param    integer        Start position (character position)
 * @param    integer        Length (in characters)
 * @return    string        The substring
 * @see substr()
 * @author    Martin Kutschker <martin.t.kutschker@blackbox.net>
 */
function utf8_substr($str,$start,$len=null)    {
  if (!strcmp($len,'0'))    return '';
  
  $byte_start = @utf8_char2byte_pos($str,$start);
  if ($byte_start === false)    {
    if ($start > 0)    {
      return false;    // $start outside string length
    } else {
      $start = 0;
    }
  }
  
  $str = substr($str,$byte_start);
  
  if ($len!=null)    {
    $byte_end = @utf8_char2byte_pos($str,$len);
    if ($byte_end === false)    // $len outside actual string length
      return $len<0 ? '' : $str;    // When length is less than zero and exceeds, then we return blank string.
    else
      return substr($str,0,$byte_end);
  }
  else    return $str;
}

?>