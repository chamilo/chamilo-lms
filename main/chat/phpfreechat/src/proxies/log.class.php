<?php
/**
 * log.class.php
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
 * pfcProxyCommand_log
 * this proxy will log "everything" from the chat
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand_log extends pfcProxyCommand
{
  function run(&$xml_reponse, $p)
  {
    $cmdtocheck = array("send", "me", "notice");
    if ( in_array($this->name, $cmdtocheck) )
    {
      $clientid    = $p["clientid"];
      $param       = $p["param"];
      $sender      = $p["sender"];
      $recipient   = $p["recipient"];
      $recipientid = $p["recipientid"];   
      $c =& $this->c;
      $u =& $this->u;
    
      $logpath = ($c->proxies_cfg[$this->proxyname]["path"] == "" ? $c->data_private_path."/logs" :
                  $c->proxies_cfg[$this->proxyname]["path"]);
      $logpath .= "/".$c->getId();
      
      if (!file_exists($logpath)) @mkdir_r($logpath);
      if (file_exists($logpath) && is_writable($logpath))
      {
        $logfile = $logpath."/chat.log";
        $f_exists = file_exists($logfile);
        if (($f_exists && is_writable($logpath)) ||
            !$f_exists)
        {
          $fp = fopen($logfile, $f_exists ? 'a' : 'w');
          // @todo write logs in a cleaner structured language (xml, html ... ?)
          $log = $recipient."\t";
          $log .= date("d/m/Y")."\t";
          $log .= date("H:i:s")."\t";
          $log .= $sender."\t";
          $log .= $param."\n";         
          fwrite($fp, $log);
          fclose($fp);
        }
      }
    }

    // forward the command to the next proxy or to the final command
    $this->next->run($xml_reponse, $p);
  }
}

?>