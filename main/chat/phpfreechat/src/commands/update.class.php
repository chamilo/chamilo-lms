<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_update extends pfcCommand
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
    
    // do not update if user isn't active (didn't connect)
    if ($u->active)
    {
      // check the user has not been disconnected from the server by timeout
      // if I found he has been disconnected, then I reconnect him with /connect command
      $ct =& $c->getContainerInstance();
      if ($ct->isNickOnline(NULL, $u->nickid) < 0)
      {
        $cmd =& pfcCommand::Factory("connect");
        $cmdp = $p;
        $cmdp["getoldmsg"] = false;
        $cmd->run($xml_reponse, $cmdp);
      }

      $cmdp = $p;
      
      // update the user nickname timestamp on the server
      $cmd =& pfcCommand::Factory("updatemynick");
      $cmdp["recipient"]   = NULL;
      $cmdp["recipientid"] = NULL;
      $cmd->run($xml_reponse, $cmdp);

      // get other online users on each channels     
      $cmd =& pfcCommand::Factory("who2");
      foreach( $u->channels as $id => $chan )
      {
        $cmdp["recipient"]   = $chan["recipient"];
        $cmdp["recipientid"] = $id;        
        $cmdp["param"] = ''; // don't forward the parameter because it will be interpreted as a channel name
        $cmd->run($xml_reponse, $cmdp);
      }
      foreach( $u->privmsg as $id => $pv )
      {
        $cmdp["recipient"]   = $pv["recipient"];
        $cmdp["recipientid"] = $id;
        $cmdp["param"] = ''; // don't forward the parameter because it will be interpreted as a channel name
        $cmd->run($xml_reponse, $cmdp);
      }      

      // get new message posted on each channels
      $cmd =& pfcCommand::Factory("getnewmsg");
      foreach( $u->channels as $id => $chan )
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

      $xml_reponse->addScript("pfc.handleResponse('update', 'ok', '');");
    }
    else
      $xml_reponse->addScript("pfc.handleResponse('update', 'ko', '');");

  }
}

?>