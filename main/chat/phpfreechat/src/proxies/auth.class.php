<?php
/**
 * auth.class.php
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
require_once dirname(__FILE__)."/../pfci18n.class.php";
require_once dirname(__FILE__)."/../pfcuserconfig.class.php";
require_once dirname(__FILE__)."/../pfcproxycommand.class.php";

/**
 * pfcProxyCommand_auth
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_auth extends pfcProxyCommand
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

    // protect admin commands
    $admincmd = array("kick", "ban", "unban", "op", "deop", "debug", "rehash");
    if ( in_array($this->name, $admincmd) )
    {
      $container =& $c->getContainerInstance();
      $nickid = $u->nickid;
      $isadmin = $container->getUserMeta($nickid, 'isadmin');
      if (!$isadmin)
      {
        $xml_reponse->addScript("alert('".addslashes(_pfc("You are not allowed to run '%s' command", $this->name))."');");
        return;
      }
    }    
    
    // channels protection
    if ($this->name == "join")
    {
      $container   =& $c->getContainerInstance();
      $channame    = $param;
      
      // check the user is not listed in the banished channel list
      $chan        = pfcCommand_join::GetRecipient($channame);
      $chanid      = pfcCommand_join::GetRecipientId($channame);
      $banlist     = $container->getChanMeta($chan, 'banlist_nickid');
      if ($banlist == NULL) $banlist = array(); else $banlist = unserialize($banlist);
      $nickid = $u->nickid;
      if (in_array($nickid,$banlist))
      {
        // the user is banished, show a message and don't forward the /join command
        $msg = _pfc("Can't join %s because you are banished", $param);
        $xml_reponse->addScript("pfc.handleResponse('".$this->proxyname."', 'ban', '".addslashes($msg)."');");
        return;
      }

      if (count($c->frozen_channels)>0)
      {
        if (!in_array($channame,$c->frozen_channels))
        {
          // the user is banished, show a message and don't forward the /join command
          $msg = _pfc("Can't join %s because the channels list is restricted", $param);
          $xml_reponse->addScript("pfc.handleResponse('".$this->proxyname."', 'frozen', '".addslashes($msg)."');");
          return;
        }
      }
    }
    
    // forward the command to the next proxy or to the final command
    $p["clientid"]    = $clientid;
    $p["param"]       = $param;
    $p["sender"]      = $sender;
    $p["recipient"]   = $recipient;
    $p["recipientid"] = $recipientid;
    $this->next->run($xml_reponse, $p);
  }
}

?>