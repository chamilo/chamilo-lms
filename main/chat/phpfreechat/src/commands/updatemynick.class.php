<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_updatemynick extends pfcCommand
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

    $container =& $c->getContainerInstance();
    $container->updateNick($u->nickid);
  }
}

?>