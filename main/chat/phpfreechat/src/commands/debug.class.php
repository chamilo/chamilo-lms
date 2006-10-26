<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_debug extends pfcCommand
{
  function run(&$xml_reponse, $p)
  {
    $c =& $this->c;
    $u =& $this->u;

    if ($p["param"] == "userconfig")
    {
      $msg   = "";
      $msg  .= var_export($u, true);
      $msg = str_replace("\n","",addslashes(nl2br($msg)));
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$msg."');");
    }

    if ($p["param"] == "globalconfig")
    {
      $msg   = "";
      $msg  .= var_export($c, true);
      $msg = str_replace("\n","",addslashes(nl2br($msg)));
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$msg."');");
    }
    if ($p["param"] == "phpserver")
    {
      $msg   = "";
      $msg  .= var_export($_SERVER, true);
      $msg = str_replace("\n","",addslashes(nl2br($msg)));
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$msg."');");
    }
    
  }
}

?>