<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_ban extends pfcCommand
{
  var $usage = "/ban {nickname}";
  
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


    $container =& $c->getContainerInstance();
    $nickid = $container->getNickId($param);
    if ($nickid != "")
    {
      $cmdtoplay = $container->getUserMeta($nickid, 'cmdtoplay');
      $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);

      $cmdtmp = array("leave",     /* cmdname */
                      $recipientid,/* param */
                      $sender,     /* sender */
                      $recipient,  /* recipient */
                      $recipientid,/* recipientid */
                      );
      //_pfc("banished from %s by %s", $recipient, $sender);
      $cmdtoplay[] = $cmdtmp; // ban the user from the current channel
      $container->setUserMeta($nickid, 'cmdtoplay', serialize($cmdtoplay));      
    }

    // update the recipient banlist
    $banlist = $container->getChanMeta($recipient, 'banlist_nickid');
    if ($banlist == NULL)
      $banlist = array();
    else
      $banlist = unserialize($banlist);
    $banlist[] = $nickid; // append the nickid to the banlist
    $container->setChanMeta($recipient, 'banlist_nickid', serialize($banlist));
  }
}

?>