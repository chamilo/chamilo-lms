<?php

/**
 * Counts the number of characters of a string in UTF-8.
 * Unit-tested by Kasper and works 100% like strlen() / mb_strlen()
 *
 * @param    string        UTF-8 multibyte character string
 * @return    integer        The number of characters
 * @see strlen()
 * @author    Martin Kutschker <martin.t.kutschker@blackbox.net>
 */
function utf8_strlen($str)    {
  $n=0;
  for($i=0; isset($str{$i}) && strlen($str{$i})>0; $i++)    {
    $c = ord($str{$i});
    if (!($c & 0x80))    // single-byte (0xxxxxx)
      $n++;
    elseif (($c & 0xC0) == 0xC0)    // multi-byte starting byte (11xxxxxx)
      $n++;
  }
  return $n;
}

?>