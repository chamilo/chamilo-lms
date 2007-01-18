<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_kick extends pfcCommand
{
  var $usage = "/kick {nickname}";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    
    $c =& $this->c;
    $u =& $this->u;

    if (trim($param) == "")
    {
      // error
      $cmdp = $p;
      $cmdp["param"] = _pfc("Missing parameter");
      $cmdp["param"] .= " (".$this->usage.")";
      $cmd =& pfcCommand::Factory("error");
      $cmd->run($xml_reponse, $cmdp);
      return;
    }

    // kicking a user just add a command to play to the aimed user metadata.
    $container =& $c->getContainerInstance();
    $nickid = $container->getNickId($param);
    if ($nickid != "")
    {
      $cmdtoplay = $container->getUserMeta($nickid, 'cmdtoplay');
      $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);
      $reason = _pfc("kicked from %s by %s", $u->channels[$recipientid]["name"], $sender);
      $cmdtmp = array("leave",  /* cmdname */
                      $recipientid." ".$reason, /* param */
                      $sender,     /* sender */
                      $recipient,  /* recipient */
                      $recipientid,/* recipientid */
                      );
      $cmdtoplay[] = $cmdtmp; // kick the user from the current channel
      $container->setUserMeta($nickid, 'cmdtoplay', serialize($cmdtoplay));
    }
  }
}

?>