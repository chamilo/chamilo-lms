<?php
/**
 * checknickchange.class.php
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
 * pfcProxyCommand_checknickchange
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_checknickchange extends pfcProxyCommand
{
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];
    $owner       = isset($p["owner"]) ? $p["owner"] : '';
    $c =& $this->c;
    $u =& $this->u;

    if ( $this->name == 'nick' )
    {
      $newnick = phpFreeChat::FilterNickname($param);
      $oldnick = $u->nick;
      
      // if the user want to change his nickname but the frozen_nick is enable
      // then send him a warning
      if ( $this->name == "nick" && 
           $u->nick != "" &&
           $param != $u->nick &&
           $c->frozen_nick == true &&
           $owner != $this->proxyname )
      {
          $msg = _pfc("You are not allowed to change your nickname");
          $xml_reponse->addScript("pfc.handleResponse('".$this->proxyname."', 'nick', '".addslashes($msg)."');");
          return;      
      }

      $container =& $c->getContainerInstance();
      $newnickid = $container->getNickId($newnick);
      $oldnickid = $u->nickid;

      if ($newnick == $oldnick &&
          $newnickid == $oldnickid)
      {
        $xml_reponse->addScript("pfc.handleResponse('nick', 'notchanged', '".addslashes($newnick)."');");
        if ($c->debug)
          pxlog("/nick ".$newnick." (user just reloded the page so let him keep his nickname without any warnings)", "chat", $c->getId());
        return;
      }

      // now check the nickname is not yet used (unsensitive case)
      // 'BoB' and 'bob' must be considered same nicknames
      $nick_in_use = false;
      $online_users = $container->getOnlineNick(NULL);
      if (isset($online_users["nickid"]))
        foreach($online_users["nickid"] as $nid)
        {
          if (preg_match("/^".preg_quote($container->getNickname($nid))."$/i",$newnick))
          {
            // the nick match
            // just allow the owner to change his capitalised letters
            if ($nid != $oldnickid)
              $nick_in_use = true;
          }
        }
      if ($nick_in_use || $newnickid != '')
      {
        $xml_reponse->addScript("pfc.handleResponse('nick', 'isused', '".addslashes($newnick)."');");
        if ($c->debug)
          pxlog("/nick ".$newnick." (wanted nick is allready in use -> wantednickid=".$newnickid.")", "chat", $c->getId());
        return;
      }
    }

    // allow nick changes only from the parameters array (server side)
    if ($this->name != 'connect' && // don't check anything on the connect process or it could block the periodic refresh
        $c->frozen_nick == true &&
        $u->nick != $c->nick &&
        $c->nick != "" && // don't change the nickname to empty or the asknick popup will loop indefinatly
        $owner != $this->proxyname)
    {
      // change the user nickname
      $cmdp = $p;
      $cmdp["param"] = $c->nick;
      $cmdp["owner"] = $this->proxyname;
      $cmd =& pfcCommand::Factory("nick");
      $cmd->run($xml_reponse, $cmdp);
      return;
    }

    // forward the command to the next proxy or to the final command
    $this->next->run($xml_reponse, $p);
  }
}

?>