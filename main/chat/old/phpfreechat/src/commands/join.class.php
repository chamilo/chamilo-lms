<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_join extends pfcCommand
{
  var $usage = "/join {channelname}";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];

    $c =& $this->c;
    $u =& $this->u;

    $channame  = trim($param);
    $chanrecip = pfcCommand_join::GetRecipient($channame);
    $chanid    = pfcCommand_join::GetRecipientId($channame);
    
    if ($channame == "")
    {
      $cmdp = $p;
      $cmdp["param"] = _pfc("Missing parameter");
      $cmdp["param"] .= " (".$this->usage.")";
      $cmd =& pfcCommand::Factory("error");
      $cmd->run($xml_reponse, $cmdp);
      return;
    }
    
    if(!isset($u->channels[$chanid]))
    {
      if ($c->max_channels <= count($u->channels))
      {
        // the maximum number of joined channels has been reached
        $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'max_channels', Array());");
        return;
      }

      $u->channels[$chanid]["recipient"] = $chanrecip;
      $u->channels[$chanid]["name"]      = $channame;
      $u->saveInCache();
      
      // show a join message
      $cmdp = $p;
      $cmdp["param"] = _pfc("%s joins %s",$u->nick, $channame);
      $cmdp["recipient"] = $chanrecip;
      $cmdp["recipientid"] = $chanid;
      $cmdp["flag"] = 2;
      $cmd =& pfcCommand::Factory("notice");
      $cmd->run($xml_reponse, $cmdp);

      // register the user (and his metadata) in the channel
      $ct =& $c->getContainerInstance();
      $ct->createNick($chanrecip, $u->nick, $u->nickid);
    }
    
    // return ok to the client
    // then the client will create a new tab
    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', Array('".$chanid."','".addslashes($channame)."'));");
  }

  function GetRecipient($channame)
  {
    return "ch_".$channame;
  }

  function GetRecipientId($channame)
  {
    return md5(pfcCommand_join::GetRecipient($channame));
  }
  
}

?>