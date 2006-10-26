<?php
/**
 * identify.class.php
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

/**
 * pfcCommand_identify
 * this command will identify the user admin rights
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcCommand_identify extends pfcCommand
{
  var $usage = "/identify {password}";
  
  function run(&$xml_reponse, $p)
  {
    $clientid    = $p["clientid"];
    $param       = $p["param"];
    $sender      = $p["sender"];
    $recipient   = $p["recipient"];
    $recipientid = $p["recipientid"];

    $c =& $this->c;
    $u =& $this->u;

    $password = trim($param);
    $isadmin = false;

//     $xml_reponse->addScript("alert('sender=".$sender."');");
//     $xml_reponse->addScript("alert('password=".$password."');");  
//     $xml_reponse->addScript("alert('admins=".var_export($c->admins, true)."');");  
  
    if( isset($c->admins[$sender]) &&
	$c->admins[$sender] == $password )
      $isadmin = true;

    $msg   = "";
    if ($isadmin)
    {
      // ok the current user is an admin, just save the isadmin flag in the metadata
      $ct =& $c->getContainerInstance();
      $ct->setUserMeta($u->nickid, 'isadmin', $isadmin);
      $this->forceWhoisReload($u->nick);
      
      $msg .= _pfc("Succesfully identified");
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ok', '".$msg."');");
    }
    else
    {
      $msg .= _pfc("Identification failure");
      $xml_reponse->addScript("pfc.handleResponse('".$this->name."', 'ko', '".$msg."');");
    }
  }
}

?>