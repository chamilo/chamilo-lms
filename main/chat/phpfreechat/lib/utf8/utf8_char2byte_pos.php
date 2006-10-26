<?php

/**
 * Translates a character position into an 'absolute' byte position.
 * Unit tested by Kasper.
 * (http://phpxref.com/xref/moodle/lib/typo3/class.t3lib_cs.php.source.html.gz)
 *
 * @param    string        UTF-8 string
 * @param    integer        Character position (negative values start from the end)
 * @return    integer        Byte position
 * @author    Martin Kutschker <martin.t.kutschker@blackbox.net>
 */
function utf8_char2byte_pos($str,$pos)    {
  $n = 0;                // number of characters found
  $p = abs($pos);        // number of characters wanted
  
  if ($pos >= 0)    {
    $i = 0;
    $d = 1;
  } else {
    $i = strlen($str)-1;
    $d = -1;
  }
  
  for( ; strlen($str{$i}) && $n<$p; $i+=$d)    {
    $c = (int)ord($str{$i});
    if (!($c & 0x80))    // single-byte (0xxxxxx)
      $n++;
    elseif (($c & 0xC0) == 0xC0)    // multi-byte starting byte (11xxxxxx)
      $n++;
  }
  if (!strlen($str{$i}))    return false; // offset beyond string length
  
  if ($pos >= 0)    {
    // skip trailing multi-byte data bytes
    while ((ord($str{$i}) & 0x80) && !(ord($str{$i}) & 0x40)) { $i++; }
  } else {
    // correct offset
    $i++;
  }
  
  return $i;
}

?>