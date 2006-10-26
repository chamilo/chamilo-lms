<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_asknick extends pfcCommand
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
    
    $nicktochange = phpFreeChat::FilterNickname($param);
    
    if ($c->frozen_nick)
    {
      // assign a random nick
      $cmdp = $p;
      $cmdp["param"] = $nicktochange."".rand(1,1000);
      $cmd =& pfcCommand::Factory("nick");
      $cmd->run($xml_reponse, $cmdp);
    }
    else
    {
      if ($nicktochange == "")
      {
        $nicktochange = $u->nick;
        $msg = _pfc("Please enter your nickname");
      }
      else
        $msg = "'".$nicktochange."' is used, please choose another nickname.";
      $xml_reponse->addScript("var newnick = prompt('".addslashes($msg)."', '".addslashes($nicktochange)."'); if (newnick) pfc.sendRequest('/nick', newnick);");
    }
  }
}

?>