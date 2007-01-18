<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_init extends pfcCommand
{
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];

    $c =& $this->c;
    $u =& $this->u;
    
    $cmd =& pfcCommand::Factory("quit");
    $cmd->run($xml_reponse, $p);

    $u->destroy();
  }
}

?>