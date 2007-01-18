<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

/**
 * This command deletes the cached configuration. Uses it to take into account new parameters.
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcCommand_rehash extends pfcCommand
{
  var $desc = "This command deletes the cached configuration. Uses it to take into account new parameters.";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    
    $c =& $this->c;
    // just destroy the cache
    // do not synchronizeWithCache() because it will reload the same parameters as the current one
    // the right way is to wait for the next page reload and the new parameters will be taken into account
    $destroyed = $c->destroyCache();

    if ($destroyed)
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '');");
    else
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ko', '');");
  }
}

?>