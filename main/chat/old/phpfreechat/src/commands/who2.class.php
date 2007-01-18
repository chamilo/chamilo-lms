<?php
/**
 * who2.class.php
 *
 * Copyright © 2006 Stephane Gully <stephane.gully@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details. 
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */

require_once(dirname(__FILE__)."/who.class.php");

class pfcCommand_who2 extends pfcCommand_who
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
    
    if ($param != "")
    {
      require_once dirname(__FILE__)."/join.class.php";
      $recipient   = pfcCommand_join::GetRecipient($param);
      $recipientid = pfcCommand_join::GetRecipientId($param);
    }

    $chanmeta = $this->_getChanMeta($recipient, $recipientid);

    //if (preg_match("/^pv_/", $recipient))
    //$this->trace($xml_reponse, 'who2', $recipient);

    // check if info didn't change since last call
    $sid = "pfc_who2_".$c->getId()."_".$clientid."_".$recipientid;
    if (isset($_SESSION[$sid]) && $chanmeta == $_SESSION[$sid])
    {
      // do not send the response to save bandwidth
      //$xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'unchanged', '');");
    }
    else
    {
      $_SESSION[$sid] = $chanmeta;
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', ".$chanmeta.");");
    }
  }
}

?>