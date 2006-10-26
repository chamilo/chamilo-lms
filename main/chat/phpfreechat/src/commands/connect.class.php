<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_connect extends pfcCommand
{
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    $getoldmsg   = isset($p["getoldmsg"]) ? $p["getoldmsg"] : true;
    
    $c =& $this->c;
    $u =& $this->u;
    $ct =& $c->getContainerInstance();

    // reset the message id indicator (see getnewmsg.class.php)
    // i.e. be ready to re-get all last posted messages
    if ($getoldmsg)
    {
      // reset the channel identifiers
      require_once(dirname(__FILE__)."/join.class.php");
      $channels = array();
      if (count($u->channels) == 0)
        $channels = $c->channels;
      else
        foreach($u->channels as $chan)
          $channels[] = $chan["name"];
      foreach($channels as $channame)
      {
        $chanrecip = pfcCommand_join::GetRecipient($channame);
        $chanid    = pfcCommand_join::GetRecipientId($channame);
        // reset the fromid flag
        $from_id_sid = "pfc_from_id_".$c->getId()."_".$clientid."_".$chanid;
        $from_id     = $ct->getLastId($chanrecip)-$c->max_msg;
        $_SESSION[$from_id_sid] = ($from_id<0) ? 0 : $from_id;
        // reset the oldmsg flag
        $oldmsg_sid = "pfc_oldmsg_".$c->getId()."_".$clientid."_".$chanid;
        $_SESSION[$oldmsg_sid] = true;
      }
      // reset the private messages identifiers
      if (count($u->privmsg) > 0)
      {
        foreach($u->privmsg as $recipientid2 => $pv)
        {
          $recipient2   = $pv['recipient'];
          // reset the fromid flag
          $from_id_sid = "pfc_from_id_".$c->getId()."_".$clientid."_".$recipientid2;
          $from_id     = $ct->getLastId($recipient2)-$c->max_msg;
          $_SESSION[$from_id_sid] = ($from_id<0) ? 0 : $from_id;
          // reset the oldmsg flag
          $oldmsg_sid = "pfc_oldmsg_".$c->getId()."_".$clientid."_".$recipientid2;
          $_SESSION[$oldmsg_sid] = true;
        }
      }
    }

    // check if the user is alone on the server, and give it the admin status if yes
    $isadmin = $ct->getUserMeta($u->nickid, 'isadmin');
    if ($isadmin == NULL)
      $isadmin = $c->isadmin;
    if ($c->firstisadmin && !$isadmin)
    {
      $users = $ct->getOnlineNick(NULL);
      if (isset($users["nickid"]) &&
          (count($users["nickid"]) == 0 || (count($users["nickid"]) == 1 && $users["nickid"][0] == $u->nickid)))
        $isadmin = true;
    }
    
    // setup some user meta
    $nickid = $u->nickid;
    // store the user ip
    $ip = $_SERVER["REMOTE_ADDR"];
    if ($ip == "::1") $ip = "127.0.0.1"; // fix for konqueror & localhost
    $ct->setUserMeta($nickid, 'ip', $ip);
    // store the admin flag
    $ct->setUserMeta($nickid, 'isadmin', $isadmin);
    // store the customized nick metadata
    foreach($c->nickmeta as $k => $v)
      $ct->setUserMeta($nickid, $k, $v);
    
    // register the user (and his metadata) in the allready joined channel
    foreach( $u->channels as $id => $chan )
      $ct->createNick($chan["recipient"], $u->nick, $u->nickid);
    foreach( $u->privmsg as $id => $pv )
      $ct->createNick($pv["recipient"], $u->nick, $u->nickid);

    $this->forceWhoisReload($u->nick);
    
    // connect to the server
    $xml_reponse->addScript("pfc.handleResponse('connect', 'ok', '');");
  }
}

?>