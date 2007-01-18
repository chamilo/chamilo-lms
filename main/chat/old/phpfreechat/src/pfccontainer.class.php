<?php
/**
 * pfccontainer.class.php
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

/**
 * pfcContainer is an abstract class which define interface
 * to be implemented by concrete container (example: File)
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 * @abstract
 */
class pfcContainer
{
  var $c;
  function pfcContainer(&$config) { $this->c =& $config; }  
  function getDefaultConfig()     { return array(); }
  function init()                 { return array(); }  

  /**
   * Create (connect/join) the nickname into the server or the channel locations
   * Notice: the caller must take care to update all channels the users joined (use stored channel list into metadata)
   * @param $chan if NULL then create the user on the server (connect), otherwise create the user on the given channel (join)
   * @param $nick the nickname to create
   * @param $nickid is the corresponding nickname id (taken from session)
   */
  function createNick($chan, $nick, $nickid)
  {
    $c =& $this->c;

    if ($chan == NULL) $chan = 'SERVER';

    $this->setMeta("nickid-to-metadata",  $nickid, 'nick', $nick);
    $this->setMeta("metadata-to-nickid",  'nick', $this->encode($nick), $nickid);

    $this->setMeta("nickid-to-channelid", $nickid, $this->encode($chan));
    $this->setMeta("channelid-to-nickid", $this->encode($chan), $nickid);

    // update the SERVER channel
    if ($chan != 'SERVER') $this->updateNick($nickid);
    
    return true;
  }

  /**
   * Remove (disconnect/quit) the nickname from the server or from a channel
   * Notice: when a user quit, the caller must take care removeNick from each channels ('SERVER' included)
   * This function takes care to remove all users metadata when he his disconnected from all channels
   * @param $chan if NULL then remove the user from the 'SERVER' channel, otherwise just remove the user from the given channel (quit)
   * @param $nickid the nickname id to remove
   * @return array which contains removed user infos ('nickid', 'nick', 'timestamp')
   */
  function removeNick($chan, $nickid)
  {
    $c =& $this->c;

    if ($chan == NULL) $chan = 'SERVER';

    $deleted_user = array();
    $deleted_user["nick"]      = array();
    $deleted_user["nickid"]    = array();
    $deleted_user["timestamp"] = array();

    $timestamp = $this->getMeta("channelid-to-nickid", $this->encode('SERVER'), $nickid);
    if (count($timestamp["timestamp"]) == 0) return $deleted_user;
    $timestamp = $timestamp["timestamp"][0];
    
    $deleted_user["nick"][]      = $this->getNickname($nickid);
    $deleted_user["nickid"][]    = $nickid;
    $deleted_user["timestamp"][] = $timestamp;

    // remove the nickid from the channel list
    $this->rmMeta('channelid-to-nickid', $this->encode($chan), $nickid);
    $this->rmMeta('nickid-to-channelid', $nickid, $this->encode($chan));

    // if the user is the last one to quit this room,
    // and this room is not a default room,
    // then clean the room history
    $channels = array();
    foreach($c->channels as $cc)
      $channels[] = 'ch_'.$cc; // @todo clean this piece of code when the chan and chanid will be refactored
    if (!in_array($chan, $channels))
    {
      $ret = $this->getOnlineNick($chan);
      if (count($ret['nickid']) == 0)
      {
        $this->rmMeta('channelid-to-msg',   $this->encode($chan));
        $this->rmMeta('channelid-to-msgid', $this->encode($chan));
      }
    }

    // get the current user's channels list
    $channels = $this->getMeta("nickid-to-channelid",$nickid);
    $channels = $channels["value"];
    // no more joined channel, just remove the user's metadata
    if (count($channels) == 0)
    {
      // remove the nickname to nickid correspondance
      $this->rmMeta('metadata-to-nickid', 'nick', $this->encode($this->getNickname($nickid)));
      // remove disconnected nickname metadata
      $this->rmMeta('nickid-to-metadata', $nickid);
    }

    return $deleted_user;
  }

  /**
   * Store/update the alive user status on the 'SERVER' channel
   * The default File container will just touch (update the date) of the nickname file in the 'SERVER' channel.
   * @param $nickid the nickname id to keep alive
   */
  function updateNick($nickid)
  {
    $c =& $this->c;

    $chan = 'SERVER';

    $this->setMeta("nickid-to-channelid", $nickid, $this->encode($chan));
    $this->setMeta("channelid-to-nickid", $this->encode($chan), $nickid);
    return true;
  }

  /**
   * Change the user's nickname
   * As nickname value are stored in user's metadata, this function just update the 'nick' metadata
   * @param $newnick
   * @param $oldnick
   * @return true on success, false on failure (if the oldnick doesn't exists)
   */
  function changeNick($newnick, $oldnick)
  {
    $c =& $this->c;

    $oldnickid = $this->getNickId($oldnick);
    $newnickid = $this->getNickId($newnick);
    if ($oldnickid == "") return false; // the oldnick must be connected
    if ($newnickid != "") return false; // the newnick must not be inuse
    
    // remove the oldnick to oldnickid correspondance
    $this->rmMeta("metadata-to-nickid", 'nick', $this->encode($oldnick));

    // update the nickname
    $this->setMeta("nickid-to-metadata", $oldnickid, 'nick', $newnick);
    $this->setMeta("metadata-to-nickid", 'nick', $this->encode($newnick), $oldnickid);
    return true;
  }

  /**
   * Returns the nickid corresponding to the given nickname
   * The nickid is a unique id used to identify a user (generated from the browser sessionid)
   * The nickid is stored in the container when createNick is called.
   * @param $nick
   * @return string the nick id
   */
  function getNickId($nick)
  {
    $nickid = $this->getMeta("metadata-to-nickid", 'nick', $this->encode($nick), true);
    $nickid = isset($nickid["value"][0]) ? $nickid["value"][0] : "";
    return $nickid;
  }

  /**
   * Returns the nickname corresponding the the given nickid
   * @param $nickid
   * @return string the corresponding nickname
   */
  function getNickname($nickid)
  {
    $nick = $this->getMeta("nickid-to-metadata", $nickid, 'nick', true);
    $nick = isset($nick["value"][0]) ? $this->decode($nick["value"][0]) : "";
    return $nick;
  }

  /**
   * Remove (disconnect/quit) the timeouted nicknames
   * Notice: this function will remove all nicknames which are not uptodate from all his joined channels 
   * @param $timeout
   * @return array("nickid"=>array("nickid1", ...),"timestamp"=>array(timestamp1, ...)) contains all disconnected nickids and there timestamp
   */
  function removeObsoleteNick($timeout)
  {
    $c =& $this->c;

    $deleted_user = array('nick'=>array(),
                          'nickid'=>array(),
                          'timestamp'=>array(),
                          'channels'=>array());
    $ret = $this->getMeta("channelid-to-nickid", $this->encode('SERVER'));
    for($i = 0; $i<count($ret['timestamp']); $i++)
    {
      $timestamp = $ret['timestamp'][$i];
      $nickid    = $ret['value'][$i];
      if (time() > ($timestamp+$timeout/1000) ) // user will be disconnected after 'timeout' secondes of inactivity
      {
        // get the current user's channels list
        $channels = array();
        $ret2 = $this->getMeta("nickid-to-channelid",$nickid);
        foreach($ret2["value"] as $userchan)
        {
          $userchan = $this->decode($userchan);
          if ($userchan != 'SERVER')
          {
            // disconnect the user from each joined channels
            $this->removeNick($userchan, $nickid);
            $channels[] = $userchan;
          }
        }
        // now disconnect the user from the server
        // (order is important because the SERVER channel has timestamp informations)
        $du = $this->removeNick('SERVER', $nickid);
        $channels[] = 'SERVER';
        
        $deleted_user["nick"]      = array_merge($deleted_user["nick"],      $du["nick"]);
        $deleted_user["nickid"]    = array_merge($deleted_user["nickid"],    $du["nickid"]);
        $deleted_user["timestamp"] = array_merge($deleted_user["timestamp"], $du["timestamp"]);       
        $deleted_user["channels"]  = array_merge($deleted_user["channels"],  array($channels));
      }
    }

    return $deleted_user;
  }

  /**
   * Returns the nickname list on the given channel or on the whole server
   * @param $chan if NULL then returns all connected user, otherwise just returns the channel nicknames
   * @return array("nickid"=>array("nickid1", ...),"timestamp"=>array(timestamp1, ...)) contains the nickid list with the associated timestamp (laste update time)
   */
  function getOnlineNick($chan)
  {
    $c =& $this->c;
    
    if ($chan == NULL) $chan = 'SERVER';

    $online_user = array('nick'=>array(),'nickid'=>array(),'timestamp'=>array());
    $ret = $this->getMeta("channelid-to-nickid", $this->encode($chan));
    for($i = 0; $i<count($ret['timestamp']); $i++)
    {
      $nickid = $ret['value'][$i];

      // get timestamp from the SERVER channel
      $timestamp = $this->getMeta("channelid-to-nickid", $this->encode('SERVER'), $nickid);
      if (count($timestamp['timestamp']) == 0) continue;
      $timestamp = $timestamp['timestamp'][0];

      $online_user["nick"][]      = $this->getNickname($nickid);
      $online_user["nickid"][]    = $nickid;
      $online_user["timestamp"][] = $timestamp;
    }
    return $online_user;
  }
  
  /**
   * Returns returns a positive number if the nick is online
   * @param $chan if NULL then check if the user is online on the server, otherwise check if the user has joined the channel
   * @return -1 if the user is off line, a positive (>=0) if the user is online
   */
  function isNickOnline($chan, $nickid)
  {
    if ($chan == NULL) $chan = 'SERVER';

    $ret = $this->getMeta("channelid-to-nickid", $this->encode($chan));
    for($i = 0; $i<count($ret['timestamp']); $i++)
    {
      if ($ret['value'][$i] == $nickid) return $i;
    }
    return -1;
  }

  /**
   * Write a command to the given channel or to the server
   * Notice: a message is very generic, it can be a misc command (notice, me, ...)
   * @param $chan if NULL then write the message on the server, otherwise just write the message on the channel message pool
   * @param $nick is the sender nickname
   * @param $cmd is the command name (ex: "send", "nick", "kick" ...)
   * @param $param is the command' parameters (ex: param of the "send" command is the message)
   * @return $msg_id the created message identifier
   */
  function write($chan, $nick, $cmd, $param)
  {
    $c =& $this->c;
    if ($chan == NULL) $chan = 'SERVER';
    
    $msgid = $this->_requestMsgId($chan);

    // format message
    $data = "\n";
    $data .= $msgid."\t";
    $data .= date("d/m/Y")."\t";
    $data .= date("H:i:s")."\t";
    $data .= $nick."\t";
    $data .= $cmd."\t";
    $data .= $param;

    // write message
    $this->setMeta("channelid-to-msg", $this->encode($chan), $msgid, $data);

    // delete the obsolete message
    $old_msgid = $msgid - $c->max_msg - 20;
    if ($old_msgid > 0)
      $this->rmMeta("channelid-to-msg", $this->encode($chan), $old_msgid);

    return $msgid;
  }

  /**
   * Read the last posted commands from a channel or from the server
   * Notice: the returned array is ordered by id
   * @param $chan if NULL then read from the server, otherwise read from the given channel
   * @param $from_id read all message with a greater id
   * @return array() contains the formated command list
   */
  function read($chan, $from_id)
  {
    $c =& $this->c;
    if ($chan == NULL) $chan = 'SERVER';

    // read new messages id
    $new_msgid_list = array();
    $new_from_id = $from_id;   
    $msgid_list = $this->getMeta("channelid-to-msg", $this->encode($chan));
    for($i = 0; $i<count($msgid_list["value"]); $i++)
    {
      $msgidtmp = $msgid_list["value"][$i];
      
      if ($msgidtmp > $from_id)
      {
        if ($msgidtmp > $new_from_id) $new_from_id = $msgidtmp;
        $new_msgid_list[] = $msgidtmp;
      }
    }

    // read messages content and parse content
    $datalist = array();
    foreach ( $new_msgid_list as $mid )
    {
      $line = $this->getMeta("channelid-to-msg", $this->encode($chan), $mid, true);
      $line = $line["value"][0];
      if ($line != "" && $line != "\n")
      {
        $formated_line = explode( "\t", $line );
        $data = array();
        $data["id"]    = trim($formated_line[0]);
        $data["date"]  = $formated_line[1];
        $data["time"]  = $formated_line[2];
        $data["sender"]= $formated_line[3];
        $data["cmd"]   = $formated_line[4];
        $data["param"] = $formated_line[5];
        $datalist[$data["id"]] = $data;
      }
    }
    ksort($datalist);
    
    return array("data" => $datalist,
                 "new_from_id" => $new_from_id );
  }

  /**
   * Returns the last message id
   * Notice: the default file container just returns the messages.index file content
   * @param $chan if NULL then read if from the server, otherwise read if from the given channel
   * @return int is the last posted message id
   */
  function getLastId($chan)
  {
    if ($chan == NULL) $chan = 'SERVER';
    
    $lastmsgid = $this->getMeta("channelid-to-msgid", $this->encode($chan), 'lastmsgid', true);
    if (count($lastmsgid["value"]) == 0)
      $lastmsgid = 0;
    else
      $lastmsgid = $lastmsgid["value"][0];
    return $lastmsgid;
  }

  
  /**
   * Return a unique id. Each time this function is called, the last id is incremented.
   * used internaly
   * @private
   */ 
  function _requestMsgId($chan)
  {
    if ($chan == NULL) $chan = 'SERVER';
    
    $lastmsgid = $this->getLastId($chan);
    $lastmsgid++;
    $this->setMeta("channelid-to-msgid", $this->encode($chan), 'lastmsgid', $lastmsgid);
    
    return $lastmsgid;
  }


  /**
   * Remove all created data for this server (identified by serverid)
   * Notice: for the default File container, it's just a recursive directory remove
   */
  function clear()
  {
    $this->rmMeta(NULL);
  }
  
  /**
   * In the default File container: used to encode UTF8 strings to ASCII filenames
   * This method can be overridden by the concrete container
   */  
  function encode($str)
  {
    return $str;
  }
  
  /**
   * In the default File container: used to decode ASCII filenames to UTF8 strings
   * This method can be overridden by the concrete container
   */  
  function decode($str)
  {
    return $str;
  }


  function getAllUserMeta($nickid)
  {
    $result = array();
    $ret = $this->getMeta("nickid-to-metadata", $nickid);
    foreach($ret["value"] as $k)
      $result[$k] = $this->getUserMeta($nickid, $k);
    //    $result['chanid'] = $this->getMeta("nickid-to-channelid", $nickid);
    //    $result['chanid'] = $result['chanid']['value'];
    return $result;
  }
  
  function getUserMeta($nickid, $key = NULL)
  {
    $ret = $this->getMeta("nickid-to-metadata", $nickid, $key, true);
    return isset($ret['value'][0]) ? $ret['value'][0] : NULL;
  }

  function setUserMeta($nickid, $key, $value)
  {
    $ret = $this->setMeta("nickid-to-metadata", $nickid, $key, $value);
    return $ret;
  }

  function getAllChanMeta($chan)
  {
    $result = array();
    $ret = $this->getMeta("channelid-to-metadata", $this->encode($chan));
    foreach($ret["value"] as $k)
      $result[$k] = $this->getChanMeta($chan, $k);
    return $result;
  }

  function getChanMeta($chan, $key = NULL)
  {
    $ret = $this->getMeta("channelid-to-metadata", $this->encode($chan), $key, true);
    return isset($ret['value'][0]) ? $ret['value'][0] : NULL;
  }

  function setChanMeta($chan, $key, $value)
  {
    $ret = $this->setMeta("channelid-to-metadata", $this->encode($chan), $key, $value);
    return $ret;
  }

  /**
   * Write a meta data value identified by a group / subgroup / leaf [with a value]
   * As an example in the default file container this  arborescent structure is modelised by simple directories
   * group1/subgroup1/leaf1
   *                 /leaf2
   *       /subgroup2/...
   * Each leaf can contain or not a value.
   * However each leaf and each group/subgroup must store the lastmodified time (timestamp).
   * @param $group root arborescent element
   * @param $subgroup is the root first child which contains leafs
   * @param $leaf is the only element which can contain values
   * @param $leafvalue NULL means the leaf will not contain any values
   * @return 1 if the old leaf has been overwritten, 0 if a new leaf has been created
   */
  function setMeta($group, $subgroup, $leaf, $leafvalue = NULL)
  { die(_pfc("%s must be implemented", get_class($this)."::".__FUNCTION__)); }

  
  /**
   * Read meta data identified by a group [/ subgroup [/ leaf]]
   * @param $group is mandatory, it's the arborescence's root
   * @param $subgroup if null then the subgroup list names are returned
   * @param $leaf if null then the leaf names are returned
   * @param $withleafvalue if set to true the leaf value will be returned
   * @return array which contains two subarray 'timestamp' and 'value'
   */
  function getMeta($group, $subgroup = null, $leaf = null, $withleafvalue = false)
  { die(_pfc("%s must be implemented", get_class($this)."::".__FUNCTION__)); }
  

  /**
   * Remove a meta data or a group of metadata
   * @param $group if null then it will remove all the possible groups (all the created metadata)
   * @param $subgroup if null then it will remove the $group's childs (all the subgroup contained by $group)
   * @param $leaf if null then it will remove all the $subgroup's childs (all the leafs contained by $subgroup)
   * @return true on success, false on error
   */
  function rmMeta($group, $subgroup = null, $leaf = null)
  { die(_pfc("%s must be implemented", get_class($this)."::".__FUNCTION__)); }

}

?>