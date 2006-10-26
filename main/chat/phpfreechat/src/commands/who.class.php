<?php
/**
 * who.class.php
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

require_once(dirname(__FILE__)."/../pfccommand.class.php");

class pfcCommand_who extends pfcCommand
{
  var $usage = "/who channel";
  
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
    
    $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', ".$chanmeta.");");
  }

  function _getChanMeta($recipient, $recipientid)
  {
    $c  =& $this->c;
    $ct =& $c->getContainerInstance();
    $chanmeta = array();
    $chanmeta['chan']   = $recipient;
    $chanmeta['chanid'] = $recipientid;
    $chanmeta['meta'] = $ct->getAllUserMeta($chanmeta['chan']);
    $users = $ct->getOnlineNick($chanmeta['chan']);
    $chanmeta['meta']['users'] = array();
    $chanmeta['meta']['users']['nick']   = $users['nick'];
    $chanmeta['meta']['users']['nickid'] = $users['nickid'];

    require_once(dirname(__FILE__)."/../../lib/json/JSON.php");
    $json = new Services_JSON();
    $js = $json->encode($chanmeta);
    return $js;
  }
}

?>