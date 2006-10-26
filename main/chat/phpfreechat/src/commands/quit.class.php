<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_quit extends pfcCommand
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

    // then remove the nickname file
    $ct =& $c->getContainerInstance();
    $quitmsg = $param == "" ? _pfc("%s quit", $u->nick) : _pfc("%s quit (%s)", $u->nick, $param);
    
    // from the channels
    foreach( $u->channels as $id => $chandetail )
      if ($ct->removeNick($chandetail["recipient"], $u->nickid))
      {
        $cmdp = $p;
        $cmdp["param"] = $id;
        $cmdp["recipient"] = $chandetail["recipient"];
        $cmdp["recipientid"] = $id;
        $cmd =& pfcCommand::Factory("leave");
        $cmd->run($xml_reponse, $cmdp);
      }
    // from the private messages
    foreach( $u->privmsg as $id => $pvdetail )
      if ($ct->removeNick($pvdetail["recipient"], $u->nickid))
      {
        $cmdp = $p;
        $cmdp["param"] = $id;
        $cmdp["recipient"] = $pvdetail["recipient"];
        $cmdp["recipientid"] = $id;        
        $cmd =& pfcCommand::Factory("leave");
        $cmd->run($xml_reponse, $cmdp);
      }
    // from the server
    $ct->removeNick(NULL, $u->nickid);

    // then set the chat inactive
    $u->active = false;
    $u->saveInCache();
    
    $xml_reponse->addScript("pfc.handleResponse('quit', 'ok', '');");

    if ($c->debug) pxlog("/quit (a user just quit -> nick=".$u->nick.")", "chat", $c->getId());
  }
}

?>