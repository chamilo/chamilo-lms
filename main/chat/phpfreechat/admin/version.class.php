<?php

/**
* Class to manage theme of pfc
* @author Fred Delaunay <fred@nemako.net>
*/

class version{
    var $local_version; // file of the user version
    var $pfc_official_current_version; // file of the pfc official current version

    
    function version(){
       $this->local_version = dirname(__FILE__)."/../version";
       $this->pfc_official_current_version = "http://www.phpfreechat.net/version";
    }

    /**
    * Get the local version
    * @return integer version
    */
    function getLocalVersion(){
       return file_get_contents($this->local_version);
    }

    /**
    * Get the pfc official current version
    * @return integer version
    */
    function getPFCOfficialCurrentVersion(){
       $parse = parse_url($this->pfc_official_current_version);
       $host = $parse['host'];
       error_reporting(0); // It's maybe not the best thing to do...
       if (!fsockopen ($host, 80, $errno, $errstr, 2)) {
         return 0;
       }
       else{
         return file_get_contents($this->pfc_official_current_version);
       }
    }
 
}

?>