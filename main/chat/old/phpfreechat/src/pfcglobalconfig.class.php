<?php
/**
 * pfcglobalconfig.class.php
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

require_once dirname(__FILE__)."/pfctools.php";
require_once dirname(__FILE__)."/pfci18n.class.php";

/**
 * pfcGlobalConfig stock configuration data into sessions and initialize some stuff
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcGlobalConfig
{
  var $serverid            = ""; // this is the chat server id (comparable to the server host in IRC)
  
  // these parameters are dynamic (not cached)
  var $nick                = ""; // the initial nickname ("" means the user will be queried)
  var $nickmeta            = array(); // this is the nickname user's metadata, you can add : sexe, age, real name ... (ex: array('sexe'=>'f') )
  var $nickmeta_private    = array('ip'); // this is the meta that only admins can see

  var $isadmin             = false;
  var $admins              = array("admin" => ""); // the key is the nickname, the value is the password
  var $firstisadmin        = false; // give admin rights to the first connected user on the server

  var $islocked            = false; // set this parameter to true to lock the chat for all users
  var $lockurl             = "http://www.phpfreechat.net"; // this is the url where the users must be redirected when the chat is locked
  
  // these parameters are static (cached)
  var $skip_proxies         = array(); // these proxies will be skiped. ex: append "censor" to the list to disable words censoring
  var $post_proxies         = array(); // these proxies will be handled just before to process commands and just after system proxies
  var $pre_proxies          = array(); // these proxies will be handled before system proxies (at begining)
  var $proxies_cfg          = array("auth"    => array(),
                                   "noflood" => array("charlimit"=>450,"msglimit"=>10,"delay"=>5),
                                   "censor"  => array("words"=>array("fuck","sex","bitch"),"replaceby"=>"*"),
                                   "log"     => array("path"=>""));
  var $proxies_path         = ""; // a custom proxies path
  var $proxies_path_default = ""; // dirname(__FILE__).'/proxies'
  var $title               = ""; // default is _pfc("My Chat")
  var $channels            = array(); // the default joined channels when opening the chat
  var $frozen_channels     = array(); // if empty, allows users to create there own channels
  var $max_channels        = 10; // this the max number of allowed channels by users
  var $privmsg             = array(); // the joined private chat when opening the chat (the nicknames must be online)
  var $max_privmsg         = 5;  // this the max number of allowed privmsg by users
  var $frozen_nick         = false; // set it to true if you don't want the user to be able to change his nickname
  var $max_nick_len        = 15;
  var $max_text_len        = 400;
  var $refresh_delay       = 5000; // in mili-seconds (5 seconds)
  var $max_refresh_delay       = 60000; // in mili-seconds (60 seconds)
  var $timeout             = 20000; // in mili-seconds (20 seconds)
  var $max_msg             = 20;
  var $quit_on_closedwindow = true; // could be annoying because the reload event is the same as a close event
  var $focus_on_connect    = true;
  var $connect_at_startup  = true;
  var $start_minimized     = false;
  var $height              = "440px";
  var $width               = "";
  var $shownotice          = 3; // show: 0 = nothing, 1 = just nickname changes, 2 = join/quit, 3 = 1+2
  var $nickmarker          = true; // show/hide nicknames colors
  var $clock               = true; // show/hide dates and hours
  var $openlinknewwindow   = true; // used to open the links in a new window
  var $notify_window       = true; // true : appends a prefix to the window title with the number of new posted messages

  var $displaytabimage       = true;
  var $displaytabclosebutton = true;
  var $showwhosonline      = true;
  var $showsmileys         = true;
  var $btn_sh_whosonline   = true; // display show/hide button for who is online
  var $btn_sh_smileys      = true; // display show/hide button for smileys
  var $bbcode_colorlist    = array("#FFFFFF","#000000","#000055","#008000","#FF0000","#800000","#800080","#FF5500","#FFFF00","#00FF00","#008080","#00FFFF","#0000FF","#FF00FF","#7F7F7F","#D2D2D2");
  var $nickname_colorlist  = array('#CCCCCC','#000000','#3636B2','#2A8C2A','#C33B3B','#C73232','#80267F','#66361F','#D9A641','#3DCC3D','#1A5555','#2F8C74','#4545E6','#B037B0','#4C4C4C','#959595');
  
  var $theme               = "default";
  var $themepath           = "";
  var $themepath_default   = "";

  var $language            = "";      // could be something in i18n/* directory ("" means the language is guess from the server config)
  var $output_encoding     = "UTF-8"; // could be ISO-8859-1 or anything else (which must be supported by iconv php module)
  var $container_type      = "File";

  var $client_script_path  = "";
  var $client_script_url   = ""; // default is calculated from 'client_script_path'
  var $server_script_path  = "";
  var $server_script_url   = ""; // default is calculated from 'server_script_path'
  var $xajaxpath           = ""; // default is dirname(__FILE__)."/../lib/xajax_0.2.3";
  var $jspath              = ""; // default is dirname(__FILE__)."/../lib/javascript";
  var $data_private_path   = ""; // default is dirname(__FILE__)."/../data/private";
  var $data_public_path    = ""; // default is dirname(__FILE__)."/../data/public";
  var $data_public_url     = ""; // default is calculated from 'data_public_path' path

  var $smileys             = array();
  var $errors              = array();
  var $is_init             = false; // used internaly to know if the chat config is initialized
  var $version             = ""; // the phpfreechat version: taken from the 'version' file content
  var $debugurl            = "";
  var $debug               = false;
  var $debugxajax          = false;

  // private parameters
  var $_sys_proxies          = array("lock", "checktimeout", "checknickchange", "auth", "noflood", "censor", "log");
  var $_proxies              = array(); // will contains proxies to execute on each command (filled in the init step)
  var $_dyn_params          = array("nick","isadmin","islocked","admins","frozen_channels", "channels", "privmsg", "nickmeta");
  var $_params_type         = array();
  
  function pfcGlobalConfig( $params = array() )
  {
    // first of all, save our current state in order to be able to check for variable types later
    $this->_saveParamsTypes();
    
    // setup the local for translated messages
    pfcI18N::Init(isset($params["language"]) ? $params["language"] : "");

    // check the serverid is really defined
    if (!isset($params["serverid"]))
      $this->errors[] = _pfc("'%s' parameter is mandatory by default use '%s' value", "serverid", "md5(__FILE__)");
    $this->serverid = $params["serverid"];

    // _getCacheFile needs data_private_path 
    if (!isset($params["data_private_path"]))
      $this->data_private_path = dirname(__FILE__)."/../data/private";
    else
      $this->data_private_path = $params["data_private_path"];
    if (!isset($params["data_public_path"]))
      $this->data_public_path  = dirname(__FILE__)."/../data/public";
    else
      $this->data_public_path = $params["data_public_path"];

    // delete the cache if no proxy.php file is found
    if (!file_exists($this->_getProxyFile()))
      @unlink($this->_getCacheFile());
    
    // check if a cached configuration allready exists
    // don't load parameters if the cache exists
    $cachefile = $this->_getCacheFile();    
    if (!file_exists($cachefile))
    {
      // load users container or keep default one
      if (isset($params["container_type"]))
        $this->container_type = $params["container_type"];
      
      // load default container's config
      $container =& $this->getContainerInstance();
      $container_cfg = $container->getDefaultConfig();
      foreach( $container_cfg as $k => $v )
      {
        $attr = "container_cfg_".$k;
        if (!isset($this->$attr))
          $this->$attr = $v;
      }
      
      // load all user's parameters which will override default ones
      foreach ( $params as $k => $v )
      {
        if (!isset($this->$k))
          $this->errors[] = _pfc("Error: undefined or obsolete parameter '%s', please correct or remove this parameter", $k);
        if (preg_match('/^_/',$k))
          $this->errors[] = _pfc("Error: '%s' is a private parameter, you are not allowed to change it", $k);
        
        if ($k == "proxies_cfg")
        {
          // don't replace all the proxy_cfg parameters, just replace the specified ones
          foreach ( $params["proxies_cfg"] as $k2 => $v2 )
          {
            if (is_array($v2))
              foreach( $v2 as $k3 => $v3)
                $this->proxies_cfg[$k2][$k3] = $v3;
            else
              $this->proxies_cfg[$k2] = $v2;
          }
        }
        else
          $this->$k = $v;
      }
    }

    // load dynamic parameter even if the config exists in the cache
    foreach ( $this->_dyn_params as $dp )
      if (isset($params[$dp]))
	$this->$dp = $params[$dp];

    // 'channels' is now a dynamic parameter, just check if I need to initialize it or not
    if (is_array($this->channels) &&
        count($this->channels) == 0 &&
        !isset($params['channels']))
      $this->channels = array(_pfc("My room"));
    
    // now load or save the configuration in the cache
    $this->synchronizeWithCache();
  }

  function &Instance( $params = array() )
  {
    static $i;
    
    if (!isset($i))
      $i = new pfcGlobalConfig( $params );
    return $i;
  }

  
  /**
   * Return the selected container instance
   * by default it is the File container
   */
  function &getContainerInstance()
  {
    // bug in php4: cant make a static pfcContainer instance because
    // it make problems with pfcGlobalConfig references (not updated)
    // it works well in php5, maybe there is a workeround but I don't have time to debug this
    // to reproduce the bug: uncomment the next lines and try to change your nickname
    //                       the old nickname will not be removed
    //    static $container;
    //    if (!isset($container))
    //    {
    $container_classname = "pfcContainer_".$this->container_type;
    require_once dirname(__FILE__)."/containers/".strtolower($this->container_type).".class.php";
    $container =& new $container_classname($this);
    //    }
    return $container;
  }

  /**
   * This function saves all the parameters types in order to check later if the types are ok
   */
  function _saveParamsTypes()
  {
    $vars = get_object_vars($this);
    foreach($vars as $k => $v)
    {
      if (is_string($v))                $this->_params_type["string"][]  = $k;
      else if (is_bool($v))             $this->_params_type["bool"][]    = $k;
      else if (is_array($v))            $this->_params_type["array"][]   = $k;
      else if (is_int($v) && $v>=0)     $this->_params_type["positivenumeric"][] = $k;
      else $this->_params_type["misc"][] = $k;
    }
  }
  
  /**
   * Initialize the phpfreechat configuration
   * this initialisation is done once at startup then it is stored into a session cache
   */
  function init()
  {
    $ok = true;

    if ($this->debug) pxlog("pfcGlobalConfig::init()", "chatconfig", $this->getId());
    
    // check the parameters types
    $array_params = $this->_params_type["array"];
    foreach( $array_params as $ap )
    {
      if (!is_array($this->$ap))
        $this->errors[] = _pfc("'%s' parameter must be an array", $ap);
    }
    $numerical_positive_params = $this->_params_type["positivenumeric"];
    foreach( $numerical_positive_params as $npp )
    {
      if (!is_int($this->$npp) || $this->$npp < 0)
        $this->errors[] = _pfc("'%s' parameter must be a positive number", $npp);
    }
    $boolean_params = $this->_params_type["bool"];
    foreach( $boolean_params as $bp )
    {
      if (!is_bool($this->$bp))
        $this->errors[] = _pfc("'%s' parameter must be a boolean", $bp);
    }
    $string_params = $this->_params_type["string"];
    foreach( $string_params as $sp )
    {
      if (!is_string($this->$sp))
        $this->errors[] = _pfc("'%s' parameter must be a charatere string", $sp);
    }

    if ($this->title == "")        $this->title        = _pfc("My Chat");
    if ($this->xajaxpath == "")    $this->xajaxpath    = dirname(__FILE__)."/../lib/xajax_0.2.3";
    if ($this->jspath == "")       $this->jspath       = dirname(__FILE__)."/../lib/javascript";
      
    // first of all, check the used functions
    $f_list["file_get_contents"] = _pfc("You need %s", "PHP 4 >= 4.3.0 or PHP 5");
    $err_session_x = "You need PHP 4 or PHP 5";
    $f_list["session_start"]   = $err_session_x;
    $f_list["session_destroy"] = $err_session_x;
    $f_list["session_id"]      = $err_session_x;
    $f_list["session_name"]    = $err_session_x;    
    $err_preg_x = _pfc("You need %s", "PHP 3 >= 3.0.9 or PHP 4 or PHP 5");
    $f_list["preg_match"]      = $err_preg_x;
    $f_list["preg_replace"]    = $err_preg_x;
    $f_list["preg_split"]      = $err_preg_x;
    $err_ob_x = _pfc("You need %s", "PHP 4 or PHP 5");
    $f_list["ob_start"]        = $err_ob_x;
    $f_list["ob_get_contents"] = $err_ob_x;
    $f_list["ob_end_clean"]    = $err_ob_x;
    $f_list["get_object_vars"] = _pfc("You need %s", "PHP 4 or PHP 5");
    $this->errors = array_merge($this->errors, check_functions_exist($f_list));
    
    $this->errors = array_merge($this->errors, @test_writable_dir($this->data_public_path, "data_public_path"));
    $this->errors = array_merge($this->errors, @test_writable_dir($this->data_private_path, "data_private_path"));
    //    $this->errors = array_merge($this->errors, @install_dir($this->jspath, $this->data_public_path."/javascript"));
    $this->errors = array_merge($this->errors, @test_writable_dir($this->data_private_path."/cache", "data_private_path/cache"));
    
    // ---
    // test xajax lib existance
    $dir = $this->xajaxpath;
    if (!is_dir($dir))
      $this->errors[] = _pfc("%s doesn't exist, %s library can't be found", $dir, "XAJAX");
    if (!file_exists($dir."/xajax.inc.php"))
      $this->errors[] = _pfc("%s not found, %s library can't be found", "xajax.inc.php", "XAJAX");
    // install public xajax js to phpfreechat public directory
    //    $this->errors = array_merge($this->errors, @install_file($this->xajaxpath."/xajax_js/xajax.js",
    //                                                             $this->data_public_path."/xajax_js/xajax.js"));
    //    $this->errors = array_merge($this->errors, @install_file($this->xajaxpath."/xajax_js/xajax_uncompressed.js",
    //                                                             $this->data_public_path."/xajax_js/xajax_uncompressed.js" ));
    // ---
    // test client script
    // try to find the path into server configuration
    if ($this->client_script_path == "")
      $this->client_script_path = getScriptFilename();
    $filetotest = $this->client_script_path;
    // do not take into account the url parameters
    if (preg_match("/(.*)\?(.*)/", $filetotest, $res))
      $filetotest = $res[1];
    if ( !file_exists($filetotest) )
      $this->errors[] = _pfc("%s doesn't exist", $filetotest);   
    if ($this->client_script_url == "")
      $this->client_script_url = "./".basename($filetotest);

    
    // calculate datapublic url
    if ($this->data_public_url == "")
      $this->data_public_url = relativePath($this->client_script_path, $this->data_public_path);
    // ---
    // test server script
    if ($this->server_script_path == "")
    {
      $this->server_script_path = $this->client_script_path;
      if ($this->server_script_url == "")
        $this->server_script_url  = $this->client_script_url;
    }
    $filetotest = $this->server_script_path;
    // do not take into account the url parameters
    if (preg_match("/(.*)\?(.*)/",$this->server_script_path, $res))
      $filetotest = $res[1];
    if ( !file_exists($filetotest) )
      $this->errors[] = _pfc("%s doesn't exist", $filetotest);
    if ($this->server_script_url == "")
      $this->server_script_url = relativePath($this->client_script_path, $this->server_script_path)."/".basename($filetotest);

    // check if the themepath parameter are correctly setup
    if ($this->themepath_default == "" || !is_dir($this->themepath_default))
      $this->themepath_default = realpath(dirname(__FILE__)."/../themes");
    if ($this->themepath == "" || !is_dir($this->themepath))
      $this->themepath = $this->themepath_default;
    // copy the themes into the public directory
    //    $this->errors = array_merge($this->errors, @install_dir($this->themepath_default, $this->data_public_path."/themes"));
    //    $this->errors = array_merge($this->errors, @install_dir($this->themepath,         $this->data_public_path."/themes"));

    
    // ---
    // run specific container initialisation
    $container_classname = "pfcContainer_".$this->container_type;
    require_once dirname(__FILE__)."/containers/".strtolower($this->container_type).".class.php";
    $container = new $container_classname($this);
    $container_errors = $container->init();
    $this->errors = array_merge($this->errors, $container_errors);
    
    // load debug url
    $this->debugurl = relativePath($this->client_script_path, dirname(__FILE__)."/../debug");

    // check the language is known
    $lg_list = pfcI18N::GetAcceptedLanguage();
    if ( $this->language != "" && !in_array($this->language, $lg_list) )
      $this->errors[] = _pfc("'%s' parameter is not valid. Available values are : '%s'", "language", implode(", ", $lg_list));

    // install the proxy file
    if (count($this->errors) == 0)
    {
      $proxyfile = $this->_getProxyFile();
      $allowedpath_string  = "";
      $allowedpath_string .= "\$allowedpath[] = '".realpath(dirname(__FILE__)."/../lib")."';\n";
      $allowedpath_string .= "\$allowedpath[] = '".realpath(dirname(__FILE__)."/../src/client")."';\n";
      $allowedpath_string .= "\$allowedpath[] = '".realpath($this->themepath_default)."';\n";
      $allowedpath_string .= "\$allowedpath[] = '".realpath($this->themepath)."';\n";
      $proxycontent = file_get_contents(dirname(__FILE__)."/client/proxy.php.tpl");
      $proxycontent = str_replace("//%allowedpath%", $allowedpath_string, $proxycontent);
      if (!file_exists(dirname($proxyfile)))
        @mkdir(dirname($proxyfile));
      if (file_exists($proxyfile) &&
          !is_writable($proxyfile))
        $this->errors[] = _pfc("'%s' must be writable", $proxyfile);
      else
        @file_put_contents($proxyfile, $proxycontent);
    }


    // calculate the proxies chaine
    $this->_proxies = array();
    foreach($this->pre_proxies as $px)
    {
      if (!in_array($px,$this->skip_proxies) && !in_array($px,$this->_proxies))
        $this->_proxies[] = $px;
        
    }
    foreach($this->_sys_proxies as $px)
    {
      if (!in_array($px,$this->skip_proxies) && !in_array($px,$this->_proxies))
        $this->_proxies[] = $px;
        
    }
    foreach($this->post_proxies as $px)
    {
      if (!in_array($px,$this->skip_proxies) && !in_array($px,$this->_proxies))
        $this->_proxies[] = $px;
        
    }
    // save the proxies path
    $this->proxies_path_default = dirname(__FILE__).'/proxies';
    // check the customized proxies path
    if ($this->proxies_path != '' && !is_dir($this->proxies_path))
      $this->errors[] = _pfc("'%s' directory doesn't exist", $this->proxies_path);
    
    // load smileys from file
    $this->loadSmileyTheme();
    
    // load version number from file
    $this->version = trim(file_get_contents(dirname(__FILE__)."/../version"));

    $this->is_init = (count($this->errors) == 0);
  }
  
  function isInit()
  {
    return $this->is_init;
  }
  
  function &getErrors()
  {
    return $this->errors;
  }

  function loadSmileyTheme()
  {
    $theme = file($this->getFilePathFromTheme("smileys/theme"));
    $result = array();
    foreach($theme as $line)
    {
      if (preg_match("/^#.*/",$line))
        continue;
      else if (preg_match("/^([a-z_\-0-9]*(\.gif|\.png))(.*)$/i",$line,$res))
      {
        $smiley_file = 'smileys/'.$res[1];
        $smiley_str = trim($res[3])."\n";
        $smiley_str = str_replace("\n", "", $smiley_str);
        $smiley_str = str_replace("\t", " ", $smiley_str);
        $smiley_str_tab = explode(" ", $smiley_str);
        foreach($smiley_str_tab as $str)
          $result[$smiley_file][] = htmlspecialchars(addslashes($str));
      }
    }
    $this->smileys =& $result;
  }

  function getId()
  {
    return $this->serverid;
  }  

  function _getProxyFile($serverid = "", $data_public_path = "")
  {
    if ($serverid == "")          $serverid = $this->getId();
    if ($data_public_path == "") $data_public_path = $this->data_public_path;
    return $data_public_path."/".$serverid."/proxy.php";
  }
  
  function _getCacheFile($serverid = "", $data_private_path = "")
  {
    if ($serverid == "")          $serverid = $this->getId();
    if ($data_private_path == "") $data_private_path = $this->data_private_path;
    return $data_private_path."/cache/pfcglobalconfig_".$serverid;
  }
  
  function destroyCache()
  {
    $cachefile = $this->_getCacheFile();
    if (!file_exists($cachefile))
      return false;
    $this->is_init = false;
    // destroy the cache lock file
    $cachefile_lock = $cachefile."_lock";
    if (file_exists($cachefile_lock)) @unlink($cachefile_lock);
    // destroy the cache file
    return @unlink($cachefile);
  }
  
  /**
   * Save the pfcConfig object into cache if it doesn't exists yet
   * else restore the old pfcConfig object
   */
  function synchronizeWithCache()
  {
    $cachefile = $this->_getCacheFile();
    $cachefile_lock = $cachefile."_lock";

    if (file_exists($cachefile))
    {
      // if a cache file exists, remove the lock file because config has been succesfully stored
      if (file_exists($cachefile_lock)) @unlink($cachefile_lock);

      $pfc_configvar = unserialize(file_get_contents($cachefile));
      foreach($pfc_configvar as $key => $val)
      {
        // the dynamics parameters must not be cached
        if (!in_array($key,$this->_dyn_params))
          $this->$key = $val;
      }
      
      return true; // synchronized
    }
    else
    {
      if (file_exists($cachefile_lock))
      {
        // delete too old lockfiles (more than 15 seconds)
        $locktime = filemtime($cachefile_lock);
        if ($locktime+15 < time())
          unlink($cachefile_lock);
        else
          return false; // do nothing if the lock file exists
      }
      else
        @touch($cachefile_lock); // create the lockfile
      
      if (!$this->isInit())
        $this->init();
      $errors =& $this->getErrors();
      if (count($errors) > 0)
      {
        @unlink($cachefile_lock); // destroy the lock file for the next attempt
        echo "<p>"._pfc("Please correct these errors").":</p><ul>"; foreach( $errors as $e ) echo "<li>".$e."</li>"; echo "</ul>";
        exit;
      }
      // save the validated config in cache
      $this->saveInCache();
      return false; // new cache created
    }
  }
  function saveInCache()
  {
    $cachefile = $this->_getCacheFile();
    file_put_contents($cachefile, serialize(get_object_vars($this)));
    if ($this->debug) pxlog("pfcGlobalConfig::saveInCache()", "chatconfig", $this->getId());
  }

  function isDefaultFile($file)
  {
    $fexists1 = file_exists($this->themepath."/default/".$file);
    $fexists2 = file_exists($this->themepath."/".$this->theme."/".$file);
    return ($this->theme == "default" ? $fexists1 : !$fexists2);
  }
  
  function getFileUrlByProxy($file, $addprefix = true)
  {
    if (file_exists($this->themepath."/".$this->theme."/".$file))
      return ($addprefix ? $this->data_public_url."/".$this->getId()."/proxy.php" : "")."?p=".$this->theme."/".$file;
    else
      if (file_exists($this->themepath_default."/default/".$file))
        return ($addprefix ? $this->data_public_url."/".$this->getId()."/proxy.php" : "")."?p=default/".$file;
      else
	die(_pfc("Error: '%s' could not be found, please check your themepath '%s' and your theme '%s' are correct", $file, $this->themepath, $this->theme));
  }
    
  function getFilePathFromTheme($file)
  {
    if (file_exists($this->themepath."/".$this->theme."/".$file))
      return $this->themepath."/".$this->theme."/".$file;
    else
      if (file_exists($this->themepath_default."/default/".$file))
	return $this->themepath_default."/default/".$file;
      else
	die(_pfc("Error: '%s' could not be found, please check your themepath '%s' and your theme '%s' are correct", $file, $this->themepath, $this->theme));
  }
}

?>
