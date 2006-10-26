<?php
/**
 * pfccommand.class.php
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
require_once dirname(__FILE__)."/pfci18n.class.php";
require_once dirname(__FILE__)."/pfcuserconfig.class.php";

/**
 * pfcCommand is an abstract class (interface) which must be inherited by each concrete commands
 * Commands examples : /nick /me /update ...
 *
 * @example ../demo/demo27_customized_command.php
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcCommand
{
  /**
   * Command name (lowercase)
   */
  var $name;

  /**
   * Contains the command syntaxe (how to use the command)
   */
  var $usage;
  
  /**
   * Not used for now
   */
  var $desc;
  var $help;

  /**
   * This is the pfcGlobalConfig instance
   */
  var $c;
  
  /**
   * This is the pfcUserConfig instance
   */
  var $u;

  /**
   * Used to instanciate a command
   * $tag is the command name : "nick", "me", "update" ...
   */
  function &Factory($name)
  {
    $c =& pfcGlobalConfig::Instance();

    // instanciate the real command
    $cmd           = NULL;
    $cmd_name      = strtolower($name);
    $cmd_classname = "pfcCommand_".$name;
    if (!class_exists($cmd_classname))
    {
      $cmd_filename  = dirname(__FILE__)."/commands/".$cmd_name.".class.php";
      if (file_exists($cmd_filename)) require_once($cmd_filename);
    }
    if (class_exists($cmd_classname))
    {
      $cmd =& new $cmd_classname();
      $cmd->name = $cmd_name;
      
      // instanciate the proxies chaine
      $firstproxy =& $cmd;
      for($i = count($c->_proxies)-1; $i >= 0; $i--)
      {
        $proxy_name      = $c->_proxies[$i];
        $proxy_classname = "pfcProxyCommand_" . $proxy_name;
        if (!class_exists($proxy_classname))
        {
          // try to include the proxy class file from the default path or from the customized path
          $proxy_filename  = $c->proxies_path_default.'/'.$proxy_name.".class.php";
          if (file_exists($proxy_filename))
            require_once($proxy_filename);
          else
          {
            $proxy_filename = $c->proxies_path.'/'.$proxy_name.".class.php";
            if (file_exists($proxy_filename)) require_once($proxy_filename);
          }
        }
        if (class_exists($proxy_classname))
        {
          // instanciate the proxy
          $proxy =& new $proxy_classname();
          $proxy->name      = $cmd_name;
          $proxy->proxyname = $proxy_name;
          $proxy->linkTo($firstproxy);
          $firstproxy =& $proxy;
        }
      }
      // return the proxy, not the command (the proxy will forward the request to the real command)
      return $firstproxy;
    }
    return $cmd;
  }

  /**
   * Constructor
   * @private
   */
  function pfcCommand()
  {
    $this->c =& pfcGlobalConfig::Instance();
    $this->u =& pfcUserConfig::Instance();
  }

  /**
   * Virtual methode which must be implemented by concrete commands
   * It is called by the phpFreeChat::HandleRequest function to execute the wanted command
   */
  function run(&$xml_reponse, $p)
  {
    die(_pfc("%s must be implemented", get_class($this)."::".__FUNCTION__));
  }
  
  /**
   * Force whois reloading
   */
  function forceWhoisReload($nicktorewhois)
  {
    $c  = $this->c;
    $u  = $this->u;
    $ct =& $c->getContainerInstance();

    $nickid = $ct->getNickid($nicktorewhois);

    // get the user who have $nicktorewhois in their list
    $channels = $ct->getMeta("nickid-to-channelid", $nickid);
    $channels = $channels['value'];
    $channels = array_diff($channels, array('SERVER'));
    $otherids = array();
    foreach($channels as $chan)
    {
      $ret = $ct->getOnlineNick($ct->decode($chan));
      $otherids = array_merge($otherids, $ret['nickid']);
    }
    
    // alert them that $nicktorewhois user info just changed
    foreach($otherids as $otherid)
    {
      $cmdtoplay = $ct->getUserMeta($otherid, 'cmdtoplay');
      $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);
      $cmdtmp = array("whois2",    /* cmdname */
                      $nicktorewhois,   /* param */
                      NULL,       /* sender */
                      NULL,       /* recipient */
                      NULL,       /* recipientid */
                      );
      if (!in_array($cmdtmp, $cmdtoplay))
      {
        $cmdtoplay[] = $cmdtmp;
        $ct->setUserMeta($otherid, 'cmdtoplay', serialize($cmdtoplay));
      }
    }
  }

  function trace(&$xml_reponse, $msg, $data = NULL)
  {
    if ($data != NULL)
    {
      require_once(dirname(__FILE__)."/../lib/json/JSON.php");
      $json = new Services_JSON();
      $js = $json->encode($data);
      $xml_reponse->addScript("trace('".$msg." -> ".$js."');");
    }
    else
      $xml_reponse->addScript("trace('".$msg."');");

  }
  
}

?>
