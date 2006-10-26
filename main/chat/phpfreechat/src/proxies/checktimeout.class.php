<?php
/**
 * checktimeout.class.php
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
 * pfcProxyCommand_checktimeout
 * this command disconnect obsolete users (timouted)
 * an obsolete user is an user which didn't update his stats since more than 20 seconds (timeout value)
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_checktimeout extends pfcProxyCommand
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

    // disconnect users from specific channels
    $ct =& $c->getContainerInstance();
    $disconnected_users = $ct->removeObsoleteNick($c->timeout);
    for($i=0; $i<count($disconnected_users["nick"]); $i++)
    {
      $nick = $disconnected_users["nick"][$i];
      for($j=0; $j<count($disconnected_users["channels"][$i]); $j++)
      {
        $chan = $disconnected_users["channels"][$i][$j];
        $online_users = $ct->getOnlineNick($chan);
        if ($chan != 'SERVER' &&
            count($online_users['nickid']))
        {
          $cmdp = $p;
          $cmdp["param"] = _pfc("%s quit (timeout)", $nick);
          $cmdp["flag"] = 2;
          $cmdp["recipient"] = $chan;
          $cmdp["recipientid"] = md5($chan); // @todo: clean the recipient/recipientid notion
          $cmd =& pfcCommand::Factory("notice");
          $cmd->run($xml_reponse, $cmdp);
        }
      }      
    }

    // forward the command to the next proxy or to the final command
    $this->next->run($xml_reponse, $p);
  }
}

?>