<?php
/**
 * phpfreechat.class.php
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

require_once dirname(__FILE__)."/pfccommand.class.php";
require_once dirname(__FILE__)."/pfcglobalconfig.class.php";
require_once dirname(__FILE__)."/pfcuserconfig.class.php";
require_once dirname(__FILE__)."/pfctemplate.class.php";
require_once dirname(__FILE__)."/../lib/utf8/utf8_substr.php";

/**
 * phpFreeChat is the entry point for developpers
 *
 * @example ../demo/demo1_simple.php
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class phpFreeChat
{
  var $xajax;
  
  function phpFreeChat( &$params )
  {
    if (!is_object($params) &&
        isset($params["debug"]) &&
        $params["debug"])
      require_once dirname(__FILE__)."/../debug/log.php";

    // check if the given parameters is a simple array
    // or a allready created phpfreechat object
    $c = NULL;
    if (is_object($params) &&
        get_class($params) == "pfcglobalconfig")
      $c =& $params;
    else
      $c =& pfcGlobalConfig::Instance( $params );

    // need to initiate the user config object here because it uses sessions
    $u =& pfcUserConfig::Instance();
    
    // Xajax doesn't support yet static class methode call
    // I use basic functions to wrap to my statics methodes
    function handleRequest($request)
    {
      return phpFreeChat::HandleRequest($request);
    }
    // then init xajax engine
    if (!class_exists("xajax"))
      if (file_exists($c->xajaxpath."/xajax.inc.php"))
      {
        require_once $c->xajaxpath."/xajax.inc.php";        
        $this->xajax = new xajax($c->server_script_url.(isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] != "" ? "?".$_SERVER["QUERY_STRING"] : ""), 'pfc_');
        if ($c->debugxajax) $this->xajax->debugOn();
        $this->xajax->waitCursorOff(); // do not show a wait cursor during chat updates
        $this->xajax->cleanBufferOff();
        $this->xajax->errorHandlerOn(); // used to have verbose error logs
        $this->xajax->registerFunction("handleRequest");
        $this->xajax->processRequests();
      }
  }

  /**
   * printJavaScript must be called into html header
   * usage:
   * <code>
   *   <?php $chat->printJavascript(); ?>
   * </code>
   */
  function printJavaScript( $return = false )
  {
    $output = '';
    $c =& pfcGlobalConfig::Instance();
    $u =& pfcUserConfig::Instance();

$output .= '<script type="text/javascript">
var xajaxRequestUri="'.$c->server_script_url.(isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] != "" ? "?".$_SERVER["QUERY_STRING"] : "").'";
var xajaxDebug=false;
var xajaxStatusMessages=false;
var xajaxWaitCursor=false;
var xajaxDefinedGet=0;
var xajaxDefinedPost=1;
var xajaxLoaded=false;
function pfc_handleRequest(){return xajax.call("handleRequest", arguments, 1);}
</script>
';
    
    // include javascript libraries
    $js = array();
    $js[] = "xajax_0.2.3/xajax_js/xajax.js";
    $js[] = "javascript/md5.js";
    $js[] = "javascript/cookie.js";
    $js[] = "javascript/image_preloader.js";
    $js[] = "javascript/myprototype.js";
    $js[] = "javascript/regex.js";
    $js[] = "javascript/utf8.js";
    $js[] = "javascript/sprintf2.js";
    $js[] = "javascript/activity.js";
    $js[] = "pfcclient.js";
    $js[] = "pfcgui.js";
    $js[] = "pfcresource.js";
    foreach( $js as $j )
    {
      $output .= "<script type=\"text/javascript\" src=\"".$c->data_public_url."/".$c->getId()."/proxy.php?p=".$j."\"></script>\n";
    }
    $output .= "<script type=\"text/javascript\" src=\"".$c->getFileUrlByProxy("customize.js")."\"></script>\n";
	
    // display output
    if ($return)
      return $output;
    else
      echo $output;
  }

  /**
   * printStyle must be called in the header
   * it inserts CSS in order to style the chat
   * usage:
   * <code>
   *   <?php $chat->printStyle(); ?>
   * </code>
   */
  function printStyle( $return = false )
  {
    $c =& pfcGlobalConfig::Instance();

    $output = '';
    if ($c->isDefaultFile("style.css"))
      $output .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$c->getFileUrlByProxy("style.css")."\" />\n";
    else
    {
      // user has a customized stylesheet
      // first of all include the default stylesheet
      // then the user stylesheet
      $defaultstyle = $c->themepath_default."/default/style.css";
      $output .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$c->data_public_url."/".$c->getId()."/proxy.php?p=default/style.css\" />\n";
      $output .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$c->getFileUrlByProxy("style.css")."\" />\n";
    }
    
    // since php can't be embeded into the css themes files, special styles parameter must be setup here
    if ($c->height != "")
    {
      $output .= "<style type=\"text/css\">";
      $output .= "div#pfc_channels_content { height: ".$c->height."; }";
      $output .= "</style>\n";
    }
    
    if($return)
      return $output;
    else 
      echo $output;
  }

  /**
   * printChat must be called somewhere in the page
   * it inserts necessary html which will receive chat's data
   * usage:
   * <code>
   *   <?php $chat->printChat(); ?>
   * </code>
   */
  function printChat( $return = false )
  {
    $c =& pfcGlobalConfig::Instance();
    $u =& pfcUserConfig::Instance();


    $output = "<div id=\"pfc_container\"".($c->width != "" ? " style=\"width:".$c->width."\"": "").">";

    // Please do not remove these lines,
    // or keep a backling to http://www.phpfreechat on your partner page
    // it helps phpfreechat promotion over the Web.
    // (remember it's a free program)
    $output .= "<p>"._pfc("Error: the chat cannot be loaded! two possibilities: your browser doesn't support javascript or you didn't setup correctly the server directories rights - don't hesitate to ask some help on the forum")." <a href=\"http://www.phpfreechat.net/forum/\">www.phpfreechat.net/forum</a></p>";
    $output .= "<a href=\"http://www.phpfreechat.net\"><img src=\"http://www.phpfreechat.net/pub/logo_80x15.gif\" alt=\"PHP FREE CHAT [powered by phpFreeChat-".$c->version."]\" title=\"PHP FREE CHAT [powered by phpFreeChat-".$c->version."]\" /></a>";
    $output .= "</div>";

    $output .= "<script type=\"text/javascript\">\n";
    $output .= " // <![CDATA[\n";

    pfcI18N::SwitchOutputEncoding($c->output_encoding);

    $t = new pfcTemplate(dirname(__FILE__)."/client/chat.js.tpl.php");
    $t->assignObject($u,"u");
    $t->assignObject($c,"c");
    $output .= $t->getOutput();
    
    pfcI18N::SwitchOutputEncoding();
    
    $output .= " // ]]>\n";
    $output .= "</script>\n";
    
    if($return) 
      return $output;
    else 
      echo $output;
  }
  
  /**
   * Encode special caracteres and remove extra slashes
   */
  function FilterSpecialChar($str)
  {
    //$str = stripslashes($str);
    //    $str = addslashes($str);
    $str = htmlspecialchars($str);
    return $str;
  }
  
  /**
   * Just check the nicknames doesn't start with spaces and is not longer than the max_nick_len
   */
  function FilterNickname($nickname)
  {
    $c =& pfcGlobalConfig::Instance();
    //$nickname = str_replace("\\", "", $nickname); // '\' is a forbidden charactere for nicknames
    $nickname = trim($nickname);
    $nickname = utf8_substr($nickname, 0, $c->max_nick_len);
    return $nickname;
  }
  
  /**
   * search/replace smileys
   */
  function FilterSmiley($msg)
  {
    $c =& pfcGlobalConfig::Instance();
    // build a preg_replace array
    $search = array();
    $replace = array();
    $query = "/(";
    foreach($c->smileys as $s_file => $s_strs)
    {
      foreach ($s_strs as $s_str)
      {
        $s_str = stripslashes($s_str); /* the :'( smileys needs this filter */
      	$query .= preg_quote($s_str,'/')."|";
      	$search[] = "/".preg_quote($s_str,'/')."/";
      	$replace[] = '<img src="'.$s_file.'" alt="'.$s_str.'" title="'.$s_str.'" />';
      }
    }
    $query = substr($query, 0, strlen($query)-1);
    $query .= ")/i";

    $split_words = preg_split($query, $msg, -1, PREG_SPLIT_DELIM_CAPTURE);
    $msg = "";
    foreach($split_words as $word)
      $msg .= preg_replace($search, $replace, $word);
    return $msg;
  }

  
  /**
   * Filter messages before they are sent to container
   */
  function PreFilterMsg($msg)
  {
    $c =& pfcGlobalConfig::Instance();
    if (preg_match("/^\[/i",$msg))
      // add 25 characteres if the message starts with [ : means there is a bbcode
      $msg = utf8_substr($msg, 0, $c->max_text_len+25);
    else
      $msg = utf8_substr($msg, 0, $c->max_text_len);
    $msg = phpFreeChat::FilterSpecialChar($msg);
    
    //    $msg = phpFreeChat::FilterSmiley($msg);

    /*    if ($msg[0] == "\n") $msg = substr($msg, 1); */ // delete the first \n generated by FF
    /* if (strpos($msg,"\n") > 0) $msg  = "<br/>".$msg;
    $msg = str_replace("\r\n", "<br/>", $msg);
    $msg = str_replace("\n", "<br/>", $msg);
    $msg = str_replace("\t", "    ", $msg);*/
    //$msg = str_replace("  ", "&nbsp;&nbsp;", $msg);
    //    $msg = preg_replace('/(http\:\/\/[^\s]*)/i',  "<a href=\"$1\">$1</a>", $msg );
    return $msg;
  }

  /**
   * Filter messages when they are recived from container
   */
  function PostFilterMsg($msg)
  {
    //$c =& pfcGlobalConfig::Instance();
    //    $msg = preg_replace('/('.preg_quote($c->nick,'/').')/i', "<strong>$1</strong>", $msg );
    $msg = preg_replace('/\n/i', "", $msg );
    return $msg;
  }

  function HandleRequest($request)
  {
    $c =& pfcGlobalConfig::Instance();
    $u =& pfcUserConfig::Instance();

    if ($c->debug) ob_start(); // capture output
 
    $xml_reponse = new xajaxResponse();

    // check the command
    $rawcmd    = "";
    $clientid  = "";
    $recipient   = "";
    $recipientid = "";
    $param     = "";
    $sender    = "";
    //if (preg_match("/^\/([a-z]*) ([0-9a-f]*) ([0-9a-f]*)( (.*)|)/", $request, $res))
    //if (preg_match("/^\/([a-z]+) ([0-9a-f]+) ([0-9a-f]+) (.*)/", $request, $res))
    if (preg_match("/^\/([a-zA-Z0-9]+) ([0-9a-f]+) ([0-9a-f]+)( (.*)|)/", $request, $res))
    {
      
      $rawcmd      = strtolower(isset($res[1]) ? $res[1] : "");
      $clientid    = isset($res[2]) ? $res[2] : "";
      $recipientid = isset($res[3]) ? $res[3] : "";
      $param       = isset($res[5]) ? $res[5] : "";
      $sender      = $u->nick;
      //      $recipient   = "home";

      //if ($rawcmd == "join")
      //  trigger_error(var_export($res));

    }

    //if ($rawcmd == "join")
    //trigger_error("channels=".var_export($u->channels));
    //trigger_error("pvs=".var_export($u->privmsg));
    
    // translate the recipientid to the channel name
    if (isset($u->channels[$recipientid]))
    {
      $recipient = $u->channels[$recipientid]["recipient"];
    }
    if (isset($u->privmsg[$recipientid]))
    {
      $recipient = $u->privmsg[$recipientid]["recipient"];


      // @todo: move this code in a proxy
      if ($rawcmd != "update" &&
          $rawcmd != "leave" &&  // do not open the pv tab when other user close the tab
          $rawcmd != "privmsg2")
      {
        // alert the other from the new pv
        // (warn other user that someone talk to him)
        $container =& $c->getContainerInstance();
        $cmdtoplay = $container->getUserMeta($u->privmsg[$recipientid]["pvnickid"], 'cmdtoplay');
        $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);
        $cmdtmp = array("privmsg2",  /* cmdname */
                        $u->nick,    /* param */
                        $sender,     /* sender */
                        $recipient,  /* recipient */
                        $recipientid,/* recipientid */
                        );
        if (!in_array($cmdtmp, $cmdtoplay))
        {
          $cmdtoplay[] = $cmdtmp;
          $container->setUserMeta($u->privmsg[$recipientid]["pvnickid"], 'cmdtoplay', serialize($cmdtoplay));
          //$xml_reponse->addScript("alert('cmdtoplay[]=".serialize($cmdtoplay)."');");
        }
      }

    }

    
    // before playing the wanted command
    // play the found commands into the meta 'cmdtoplay'
    $container =& $c->getContainerInstance();
    $nickid = $u->nickid;
    $morecmd = true;
    while($morecmd)
    {
      // take a command from the list
      $cmdtoplay = $container->getUserMeta($nickid, 'cmdtoplay');
      $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);
      $cmdtmp = array_pop($cmdtoplay);
      if ($cmdtmp != NULL)
      {
        // store the new cmdtoplay list (-1 item)
        $container->setUserMeta($nickid, "cmdtoplay", serialize($cmdtoplay));

        // play the command
        $cmd =& pfcCommand::Factory($cmdtmp[0]);
        $cmdp = array();
        $cmdp["clientid"]    = $clientid;
        $cmdp["param"]       = $cmdtmp[1];
        $cmdp["sender"]      = $cmdtmp[2];
        $cmdp["recipient"]   = $cmdtmp[3];
        $cmdp["recipientid"] = $cmdtmp[4];
        if ($c->debug)
          $cmd->run($xml_reponse, $cmdp);
        else
          @$cmd->run($xml_reponse, $cmdp);

        // if the cmdtoplay is a 'leave' command, then show an alert to the kicked or banished user
        if ($cmdtmp[0] == "leave")
        {
          if (preg_match("/([a-z0-9]*) (.*)/i", $cmdtmp[1], $res))
            $xml_reponse->addScript("alert('".$res[2]."');");
        }
        
        // check if there is other command to play
        $cmdtoplay = $container->getUserMeta($nickid, 'cmdtoplay');
        $cmdtoplay = ($cmdtoplay == NULL) ? array() : unserialize($cmdtoplay);        
      }

      $morecmd = (count($cmdtoplay) > 0);
    }

    
    $cmd =& pfcCommand::Factory($rawcmd);
    $cmdp = array();
    $cmdp["clientid"]    = $clientid;
    $cmdp["param"]       = $param;
    $cmdp["sender"]      = $sender;
    $cmdp["recipient"]   = $recipient;
    $cmdp["recipientid"] = $recipientid;
    if ($cmd != NULL)
    {
      // call the command
      if ($c->debug)
      	$cmd->run($xml_reponse, $cmdp);
      else
      	@$cmd->run($xml_reponse, $cmdp);
    }
    else
    {
      $cmd =& pfcCommand::Factory("error");
      $cmdp = array();
      $cmdp["clientid"]    = $clientid;
      $cmdp["param"]       = _pfc("Unknown command [%s]",stripslashes("/".$rawcmd." ".$param));
      $cmdp["sender"]      = $sender;
      $cmdp["recipient"]   = $recipient;
      $cmdp["recipientid"] = $recipientid;
      if ($c->debug)
        $cmd->run($xml_reponse, $cmdp);
      else
        @$cmd->run($xml_reponse, $cmdp);
    }
    
    // do not update twice
    // do not update when the user just quit
    if ($rawcmd != "update" &&
      	$rawcmd != "quit" &&
      	(!isset($u->nick) || $u->nick != ""))
    {
      // force an update just after a command is sent
      // thus the message user just poster is really fastly displayed
      $cmd =& pfcCommand::Factory("update");
      $cmdp = array();
      $cmdp["clientid"]    = $clientid;
      $cmdp["param"]       = $param;
      $cmdp["sender"]      = $sender;
      $cmdp["recipient"]   = $recipient;
      $cmdp["recipientid"] = $recipientid;
      if ($c->debug)
      	$cmd->run($xml_reponse, $cmdp);
      else
      	@$cmd->run($xml_reponse, $cmdp);
    }
  
    if ($c->debug)
    {
      // capture echoed content
      // if a content not empty is captured it is a php error in the code
      $data = ob_get_contents();
      if ($data != "")
        pxlog("HandleRequest: content=".$data, "chat", $c->getId());
      ob_end_clean();
    }

    // do nothing else if the xml response is empty
    //if ($xml_reponse->xml == "") die();
    
    return $xml_reponse->getXML();
  }
}

?>
