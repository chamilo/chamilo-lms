<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_help extends pfcCommand
{
  var $usage = "/help";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    
    $c =& $this->c;
    $u =& $this->u;

    $ignore = array("updatemynick", "getnewmsg", "notice", "getonlinenick", "error", "update", "asknick");
    
    $cmdlist = array();
    $dh = opendir(dirname(__FILE__));
    while (false !== ($file = readdir($dh)))
    {
      if (!preg_match("/^([a-z]+).class.php$/i",$file,$res)) continue;
      if (!in_array($res[1],$ignore)) $cmdlist[] = $res[1];
    }
    sort($cmdlist);

    $str = _pfc("Here is the command list:")."<br/>";
    $str .= implode("<br/>", $cmdlist);
    
    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$str."');");
  }
}

?>