<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

/**
 * This command list the banished users on the given channel
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcCommand_banlist extends pfcCommand
{
  var $desc = "This command list the banished users on the given channel";
  
  function run(&$xml_reponse, $p)
  {
    $c =& $this->c;
    $u =& $this->u;
    
    $container =& $c->getContainerInstance();
    $banlist = $container->getChanMeta($p["recipientid"], 'banlist_nickid');
    if ($banlist == NULL) $banlist = array(); else $banlist = unserialize($banlist);
    $msg  = "";
    $msg .= "<p>"._pfc("The banished user's id list is:")."</p>";
    if (count($banlist)>0)
    {
      $msg .= "<ul>";
      foreach($banlist as $b) $msg .= "<li style=\"margin-left:50px\">".$b."</li>";
      $msg .= "</ul>";
    }
    else
    {
      $msg .= "<p>("._pfc("Empty").")</p>";
    }
    $msg .= "<p>"._pfc("'/unban {id}' will unban the user identified by {id}")."</p>";
    $msg .= "<p>"._pfc("'/unban all'  will unban all the users on this channel")."</p>";
      
    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".addslashes($msg)."');");
  }
}

?>