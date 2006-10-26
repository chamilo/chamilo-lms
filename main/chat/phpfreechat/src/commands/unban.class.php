<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_unban extends pfcCommand
{
  var $usage = "/unban {id}";
  
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
    
    $updated = false;
    $msg = "<p>"._pfc("Nobody has been unbanished")."</p>";
    
    // update the recipient banlist
    $banlist = $container->getChanMeta($recipient, 'banlist_nickid');
    if ($banlist == NULL)
      $banlist = array();
    else
      $banlist = unserialize($banlist);
    $nb = count($banlist);

    if (in_array($param, $banlist))
    {
      $banlist = array_diff($banlist, array($param));
      $container->setChanMeta($recipient, 'banlist_nickid', serialize($banlist));
      $updated = true;
      $msg = "<p>"._pfc("%s has been unbanished", $param)."</p>";
    }
    else if ($param == "all")
    {
      $banlist = array();
      $container->setChanMeta($recipient, 'banlist_nickid', serialize($banlist));
      $updated = true;
      $msg = "<p>"._pfc("%s users have been unbanished", $nb)."</p>";
    }
    
    if ($updated)
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$msg."');");
    else
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ko', '".$msg."');");
  }
}

?>