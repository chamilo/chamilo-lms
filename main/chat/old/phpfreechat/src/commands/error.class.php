<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_error extends pfcCommand
{
  function run(&$xml_reponse, $p)
  {
    $c =& $this->c;
    $errors = $p["param"];
    if (is_array($errors))
    {
      $error_ids = ""; $error_str = "";
      foreach ($errors as $k => $e) { $error_ids .= ",'".$k."'"; $error_str.= $e." "; }
      $error_ids = substr($error_ids,1);
      $xml_reponse->addScript("pfc.setError('".addslashes(stripslashes($error_str))."', Array(".$error_ids."));");
    }
    else
      $xml_reponse->addScript("pfc.setError('".addslashes(stripslashes($errors))."', Array());");
  }
}

?>