<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_nick extends pfcCommand
{
  var $usage = "/nick {newnickname}";
  
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
    
    $newnick = phpFreeChat::FilterNickname($param);
    $oldnick = $u->nick;

    $container =& $c->getContainerInstance();
    $newnickid = $container->getNickId($newnick);
    $oldnickid = $container->getNickId($oldnick);

    if ($c->debug) pxlog("/nick ".$newnick, "chat", $c->getId());

    // new nickname is undefined (not used) and
    // current nickname (oldnick) is mine and
    // oldnick is different from new nick
    // -> this is a nickname change
    if ($oldnickid == $u->nickid &&
        $oldnick != $newnick && $oldnick != "")
    {
      // really change the nick (rename it)
      $container->changeNick($newnick, $oldnick);
      $u->nick = $newnick;
      $u->saveInCache();
      $this->forceWhoisReload($u->nick);

      // notify all the joined channels/privmsg
      $cmdp = $p;
      $cmdp["param"] = _pfc("%s changes his nickname to %s",$oldnick,$newnick);
      $cmdp["flag"]  = 1;
      $cmd =& pfcCommand::Factory("notice");
      foreach($u->channels as $id => $chan)
      {
        $cmdp["recipient"]   = $chan["recipient"];
        $cmdp["recipientid"] = $id;
        $cmd->run($xml_reponse, $cmdp);
      }
      foreach( $u->privmsg as $id => $pv )
      {
        $cmdp["recipient"]   = $pv["recipient"];
        $cmdp["recipientid"] = $id;
        $cmd->run($xml_reponse, $cmdp);
      }
      $xml_reponse->addScript("pfc.handleResponse('nick', 'changed', '".addslashes($newnick)."');");
    }
    
    // new nickname is undefined (not used) and
    // current nickname (oldnick) is not mine or is undefined
    // -> this is a first connection
    if ($oldnickid != $u->nickid)
    {
      // this is a first connection : create the nickname on the server
      $container->createNick(NULL, $newnick, $u->nickid);
      /*
      // useless code, it's done in updatemynick command
      foreach($u->channels as $chan)
        $container->createNick($chan["recipient"], $newnick, $u->nickid);
      foreach($u->privmsg as $pv)
        $container->createNick($pv["recipient"], $newnick, $u->nickid);
      */
      $u->nick   = $newnick;
      $u->active = true;
      $u->saveInCache();
      $this->forceWhoisReload($u->nick);

      $xml_reponse->addScript("pfc.handleResponse('nick', 'connected', '".addslashes($newnick)."');");
    
      if ($c->debug)
        pxlog("/nick ".$newnick." (first connection, oldnick=".$oldnick.")", "chat", $c->getId());
    }
  }
}

?>