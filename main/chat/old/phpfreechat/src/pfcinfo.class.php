<?php

require_once dirname(__FILE__)."/pfcglobalconfig.class.php";
require_once dirname(__FILE__)."/pfci18n.class.php";
require_once dirname(__FILE__)."/commands/join.class.php";

class pfcInfo extends pfcGlobalConfig
{
  var $container;
  var $errors = array();
  
  function pfcInfo( $serverid, $data_private_path = "" )
  {
    // check if the cache allready exists
    // if it doesn't exists, just stop the process
    // because we can't initialize the chat from the external API
    if ($data_private_path == "") $data_private_path = dirname(__FILE__)."/../data/private";
    $cachefile = $this->_getCacheFile( $serverid, $data_private_path );
    if (!file_exists($cachefile))
    {
      $this->errors[] = _pfc("Error: the cached config file doesn't exists");
      return;
    }    
    // then intitialize the pfcglobalconfig
    $params["serverid"]          = $serverid;
    $params["data_private_path"] = $data_private_path;
    pfcGlobalConfig::pfcGlobalConfig($params);    
  }
  
  /**
   * @return array(string) a list of errors
   */
  function getErrors()
  {
    return $this->errors;
  }
  
  /**
   * @param $channel the returned list is the list of nicknames present on the given channel (NULL for the whole server)
   * @param $timeout is the time to wait before a nickname is considered offline
   * @return array(string) a list of online nicknames
   */
  function getOnlineNick($channel = NULL, $timeout = 20)
  {
    $container =& $this->getContainerInstance();
    $res = $container->getOnlineNick($channel);
    $users = array();
    if (isset($res["nickid"]))
    {
      for($i = 0; $i < count($res["nickid"]); $i++)
      {
        if (time()-$timeout < $res["timestamp"][$i])
          $users[] = $container->getNickname($res["nickid"][$i]);
      }
    }
    return $users;
  }

  /**
   * Return the last $nb message from the $channel room.
   * The messages format is very basic, it's raw data (it will certainly change in future)
   */
  function getLastMsg($channel, $nb)
  {
    // to be sure the $nb params is a positive number
    if ( !( $nb >= 0 ) ) $nb = 10;
    
    // to get the channel recipient name
    // @todo must use another function to get a private message last messages
    $channel = pfcCommand_join::GetRecipient($channel);
    
    $container   =& $this->getContainerInstance();    
    $lastmsg_id  = $container->getLastId($channel);
    $lastmsg_raw = $container->read($channel, $lastmsg_id-$nb);
    return $lastmsg_raw;
  }

  /**
   * Rehash the server config (same as /rehash command)
   * Usefull to take into account new server's parameters
   */
  function rehash()
  {
    $destroyed = $this->destroyCache();
    return $destroyed;
  }
}

?>