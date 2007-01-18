<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_leave extends pfcCommand
{
  var $usage = "/leave [{recipientid} {reason}]";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];

    $c =& $this->c;
    $u =& $this->u;

    // tab to leave can be passed in the parameters
    // a reason can also be present (used for kick and ban commands)
    $id = ""; $reason = "";
    if (preg_match("/([a-z0-9]*)( (.*)|)/i", $param, $res))
    {
      $id     = $res[1];
      $reason = trim($res[2]);
    }
    if ($id == "") $id = $recipientid; // be default this is the current tab to leave
    
    //    $xml_reponse->addScript("alert('sender=".addslashes($sender)."');");
    //    $xml_reponse->addScript("alert('recipientid=".addslashes($id)."');");

    $leavech = false;
    $leavepv = false;
    $leave_recip = "";
    $leave_id    = "";

    // check into channels
    if ( isset($u->channels[$id]) )
    {
      $leave_recip = $u->channels[$id]["recipient"];
      $leave_id    = $id;
      unset($u->channels[$id]);
      $u->saveInCache();
      $leavech = true;
    }

    // check into private messages
    if ( isset($u->privmsg[$id]) )
    {
      $leave_recip = $u->privmsg[$id]["recipient"];
      $leave_id    = $id;
      unset($u->privmsg[$id]);
      $u->saveInCache();
      $leavepv = true;
    }

    if($leavepv || $leavech)
    {
      if ($leavech)
      {
        // show a leave message with the showing the reason if present
        $cmdp = $p;
        $cmdp["recipient"]   = $leave_recip;
        $cmdp["recipientid"] = $leave_id;
        $cmdp["flag"]        = 2;
        $cmdp["param"] = _pfc("%s quit",$u->nick);
        if ($reason != "") $cmdp["param"] .= " (".$reason.")";
        $cmd =& pfcCommand::Factory("notice");
        $cmd->run($xml_reponse, $cmdp);
      }

      // remove the nickname from the channel/pv
      $ct =& $c->getContainerInstance();
      $ct->removeNick($leave_recip, $u->nickid);

      // reset the sessions indicators
      $chanrecip = $leave_recip;
      $chanid    = $leave_id;
      // reset the fromid flag
      $from_id_sid = "pfc_from_id_".$c->getId()."_".$clientid."_".$chanid;
      $from_id     = $ct->getLastId($chanrecip)-$c->max_msg;
      $_SESSION[$from_id_sid] = ($from_id<0) ? 0 : $from_id;
      // reset the oldmsg flag
      $oldmsg_sid = "pfc_oldmsg_".$c->getId()."_".$clientid."_".$chanid;
      $_SESSION[$oldmsg_sid] = true;
      
      // return ok to the client
      // then the client will remove the channel' tab
      $xml_reponse->addScript("pfc.handleResponse('leave', 'ok', '".$id."');");
    }
    else
    {
      // error
      $cmdp = $p;
      $cmdp["param"] = _pfc("Missing parameter");
      $cmdp["param"] .= " (".$this->usage.")";
      $cmd =& pfcCommand::Factory("error");
      $cmd->run($xml_reponse, $cmdp);
    }
  }
}

?>