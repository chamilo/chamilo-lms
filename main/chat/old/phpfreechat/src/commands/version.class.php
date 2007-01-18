<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_version extends pfcCommand
{
  var $usage = "/version";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    
    $c =& $this->c;
    $u =& $this->u;

    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$c->version."');");
  }
}

?>