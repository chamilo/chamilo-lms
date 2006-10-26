<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_deop extends pfcCommand
{
  var $usage = "/deop {nickname}";
  
  function run(&$xml_reponse, $p)
  {
    $c =& $this->c;
    $u =& $this->u;

    if (trim($p["param"]) == "")
    {
      // error
      $cmdp = $p;
      $cmdp["param"] = _pfc("Missing parameter");
      $cmdp["param"] .= " (".$this->usage.")";
      $cmd =& pfcCommand::Factory("error");
      $cmd->run($xml_reponse, $cmdp);
      return;
    }

    // just change the "isadmin" meta flag
    $nicktodeop   = trim($p["param"]);
    $container  =& $c->getContainerInstance();
    $nicktodeopid = $container->getNickId($nicktodeop);
    $container->setUserMeta($nicktodeopid, 'isadmin', false);

    $this->forceWhoisReload($nicktodeop);
  }
}

?>