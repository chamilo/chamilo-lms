<?php
/**
 * pfci18n.class.php
 *
 * Copyright © 2006 Stephane Gully <stephane.gully@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details. 
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */
 
require_once(dirname(__FILE__)."/pfctools.php");

function _pfc()
{
  $args = func_get_args();
  $args[0] = isset($GLOBALS["i18n"][$args[0]]) && $GLOBALS["i18n"][$args[0]] != "" ?
    ($GLOBALS["output_encoding"] == "UTF-8" ?
       $GLOBALS["i18n"][$args[0]] :
       iconv("UTF-8", $GLOBALS["output_encoding"], $GLOBALS["i18n"][$args[0]])) :
    "_".$args[0]."_";
  return call_user_func_array('sprintf', $args);
}
/**
 * Just like _pfc but just return the raw translated string, keeping the %s into it
 * (used byt the javascript resources (i18n) class)
 */
function _pfc2()
{
  $args = func_get_args();
  $args[0] = isset($GLOBALS["i18n"][$args[0]]) && $GLOBALS["i18n"][$args[0]] != "" ?
    ($GLOBALS["output_encoding"] == "UTF-8" ?
       $GLOBALS["i18n"][$args[0]] :
       iconv("UTF-8", $GLOBALS["output_encoding"], $GLOBALS["i18n"][$args[0]])) :
    "_".$args[0]."_";
  return $args[0];
}

class pfcI18N
{
  function Init($language,$type="main")
  {
    if ($type=="admin")
      if (!in_array($language, pfcI18N::GetAcceptedLanguage("admin")))
        $language = pfcI18N::GetDefaultLanguage();
    if (!in_array($language, pfcI18N::GetAcceptedLanguage()))
      $language = pfcI18N::GetDefaultLanguage();
    
    if ($type=="admin")
      require_once(dirname(__FILE__)."/../i18n/".$language."/admin.php");
    else
      require_once(dirname(__FILE__)."/../i18n/".$language."/main.php");
    $GLOBALS["output_encoding"] = "UTF-8"; // by default client/server communication is utf8 encoded
  }

  /**
   * Switch output encoding in order to write the right characteres in the web page
   */
  function SwitchOutputEncoding($oe = "")
  {
    if ($oe == "")
    {
      $GLOBALS["output_encoding"]     = $GLOBALS["old_output_encoding"];
      unset($GLOBALS["old_output_encoding"]);
    }
    else
    {
      if (isset($GLOBALS["old_output_encoding"]))
        die("old_output_encoding must be empty (".$GLOBALS["old_output_encoding"].")");
      $GLOBALS["old_output_encoding"] = $GLOBALS["output_encoding"];
      $GLOBALS["output_encoding"]     = $oe;
    }
  }
  
  /**
   * Return the default language : "en"
   */
  function GetDefaultLanguage()
  {
    return "en_US";
  }

  /**
   * Return the language list supported bye i18n system
   * (content of the i18n directory)
   */
  function GetAcceptedLanguage($type="main")
  {
    if ($type=="admin"){
      if (isset($GLOBALS["accepted_admin_languages"]))
        return $GLOBALS["accepted_admin_languages"]; // restore the cached languages list
      $GLOBALS["accepted_admin_languages"] = array();
      $dir_handle = opendir(dirname(__FILE__)."/../i18n");
      while (false !== ($file = readdir($dir_handle)))
      {
        // skip . and .. generic files
        // skip also .svn directory
        if ($file == "." || $file == ".." || strpos($file,".")===0) continue;
        if (file_exists(dirname(__FILE__)."/../i18n/".$file."/admin.php"))
        $GLOBALS["accepted_admin_languages"][] = $file;
      }
      return $GLOBALS["accepted_admin_languages"];
    }
    else{
      if (isset($GLOBALS["accepted_languages"]))
        return $GLOBALS["accepted_languages"]; // restore the cached languages list
      $GLOBALS["accepted_languages"] = array();
      $dir_handle = opendir(dirname(__FILE__)."/../i18n");
      while (false !== ($file = readdir($dir_handle)))
      {
        // skip . and .. generic files
        // skip also .svn directory
        if ($file == "." || $file == ".." || strpos($file,".")===0) continue;
        $GLOBALS["accepted_languages"][] = $file;
      }
      return $GLOBALS["accepted_languages"];
    }
  }
  
  /**
   * Parse the source-code and update the i18n ressources files
   */
  function UpdateMessageRessources()
  {
    $files = array();
    $files = array_merge($files, glob(dirname(__FILE__)."/*.php"));
    $files = array_merge($files, glob(dirname(__FILE__)."/commands/*.php")); 
    $files = array_merge($files, glob(dirname(__FILE__)."/containers/*.php"));
    $files = array_merge($files, glob(dirname(__FILE__)."/proxies/*.php"));    
    $files = array_merge($files, glob(dirname(__FILE__)."/client/*.php"));    
    $files = array_merge($files, glob(dirname(__FILE__)."/../themes/default/templates/*.php"));

    $res = array();
    foreach ( $files as $src_filename )
    {
      $lines = file($src_filename);
      $line_nb = 1;
      foreach( $lines as $l)
      {
        // the labels server side
        if( preg_match_all('/_pfc\("([^\"]*)"(\s*\,.*|)\)/', $l, $matches) )
        {
          echo "line: ".$line_nb."\t- ".$matches[1][0]."\n";
          $res[$matches[1][0]] = "// line ".$line_nb." in ".basename($src_filename);
        }
        // the labels client side (JS)
        if( preg_match_all('/"([^"]*)",\s\/\/\s_pfc/', $l, $matches) )
        {
          echo "line: ".$line_nb."\t- ".$matches[1][0]."\n";
          $res[$matches[1][0]] = "// line ".$line_nb." in ".basename($src_filename);
        }
        $line_nb++;
      }
    }

    $dst_filenames = array();
    foreach(pfcI18N::GetAcceptedLanguage() as $lg)
      $dst_filenames[] = dirname(__FILE__)."/../i18n/".$lg."/main.php";

    foreach( $dst_filenames as $dst_filename )
    {
      // filter lines to keep, line to add
      $old_content = file_get_contents($dst_filename);
      // remove php tags to keep only real content
      $old_content = preg_replace("/^\<\?php/", "", $old_content);
      $old_content = preg_replace("/\?\>$/", "", $old_content);
      
      // save into the file
      $new_content = "";
      foreach($res as $str => $com)
      {
        //echo "com=".$com."\n";
        //echo "str=".$str."\n";
        if (preg_match("/".preg_quote($str,'/')."/", $old_content) == 0)
          $new_content .= $com."\n\$GLOBALS[\"i18n\"][\"".$str."\"] = \"\";\n\n";
      }
      $content = "<?php" . $old_content . $new_content . "?>";
      //echo $content;
      
      file_put_contents($dst_filename, $content);
    }
  }
}

?>