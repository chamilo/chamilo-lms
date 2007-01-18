<?php

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_clear extends pfcCommand
{
  function run(&$xml_reponse, $p)
  {
    $c =& $this->c;
    $u =& $this->u;

    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '');");
  }
}

?>