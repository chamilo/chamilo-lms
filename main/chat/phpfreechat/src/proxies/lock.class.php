<?php
/**
 * lock.class.php
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
 * pfcProxyCommand_lock
 * if the chat is locked, redirect users to a given url
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_lock extends pfcProxyCommand
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

    // check if the chat is locked
    if ($c->islocked)
    {
      $xml_reponse->addRedirect($c->lockurl);
    }
    else
    {
      // forward the command to the next proxy or to the final command
      $p["clientid"]    = $clientid;
      $p["param"]       = $param;
      $p["sender"]      = $sender;
      $p["recipient"]   = $recipient;
      $p["recipientid"] = $recipientid;
      $this->next->run($xml_reponse, $p);      
    }
  }
}

?>