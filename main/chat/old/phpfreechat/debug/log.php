<?php

function pxlog($data, $section = "", $id = "")
{
  $msg = htmlspecialchars(stripslashes(var_export($data, true)));
  $filename = dirname(__FILE__)."/../data/private/debug".$section."_".$id.".log";
  if (!file_exists($filename))
    @touch($filename);
  $fp = @fopen($filename, 'a');
  @fwrite($fp, "[".$id."] ".date("Y/m/d H:i:s - ").$msg."\n");
  @fclose($fp);
}

?>
