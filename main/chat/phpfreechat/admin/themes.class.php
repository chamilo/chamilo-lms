<?php

/**
* Class to manage theme of pfc
* @author Fred Delaunay <fred@nemako.net>
*/

class themes{
    var $dir_themes; // directory of themes


    
    function themes(){
       $this->dir_themes = dirname(__FILE__)."/../themes/";
    }

    /**
    * Get the list of themes
    * @return array $themes_list
    */
    function getThemesList(){
       $i=0;
       $dir = opendir($this->dir_themes);
       while ($f = readdir($dir)) {
         if(is_dir($this->dir_themes.$f) && $f!="." && $f!=".." && strpos($f,".")!==0) {
            $themes_list[$i] = $f;
            $i++;
          }
       }
       
       if($i>0)
         return $themes_list;
       else
         return 0;
    }
    
    /**
    * Get the Author of a theme
    * @param string $theme
    * @return string $author
    */
    function getThemeAuthor($theme){
       if(file_exists($this->dir_themes.$theme."/info.php")){
         include($this->dir_themes.$theme."/info.php");
         if(empty($author))
           return 0;
         else  
           return $author;
       }
       else{
         return 0;
       }
    }
    
    
    /**
    * Get the Website of a theme
    * @param string $theme
    * @return string $website
    */
    function getThemeWebsite($theme){
       if(file_exists($this->dir_themes.$theme."/info.php")){
         include($this->dir_themes.$theme."/info.php");
         if(empty($website))
           return 0;
         else  
           return $website;
       }
       else{
         return 0;
       }
    }

    /**
    * Get the info of a theme
    * @param string $theme
    * @return string $info
    */
    function getThemeInfo($theme){
       $author = $this->getThemeAuthor($theme);
       $website = $this->getThemeWebsite($theme);
       $screenshot = $this->getThemeScreenshot($theme);
       
       if ($author!='0') $info = "$author";
       if ($author!='0' && $website!='0') $info .= " - ";
       if ($website!='0') $info .= "<a href=\"$website\">$website</a>";
       if (($author!='0' || $website!='0') && ($screenshot!='0')) $info .= " - ";
       if ($screenshot!='0') $info .= "<a href=\"$screenshot\">"._pfc("Screenshot")."</a>";
       
       if(empty($info))
          return 0;
       else  
          return $info;
    }


    /**
    * Get the screenshot of a theme
    * @param string $theme
    * @return string $screenshot
    */
    function getThemeScreenshot($theme){
       if(file_exists($this->dir_themes.$theme."/info.php")){
         include($this->dir_themes.$theme."/info.php");
         if(empty($screenshot))
           return 0;
         else  
           return $screenshot;
       }
       else{
         return 0;
       }
    }
    
    /**
    * Search if the imagess folder theme is present
    * @param string $theme
    * @return boolean - true if the /themes/name/images folder is present
    */
    function isThemeImages($theme){
       if(is_dir($this->dir_themes.$theme."/images")){
         return true;
       }
       else{
         return false;
       }
    }
            
    /**
    * Search if the smiley theme is present
    * @param string $theme
    * @return boolean - true if the /themes/name/smiley/theme file is present
    */
    function isThemeSmiley($theme){
       if(file_exists($this->dir_themes.$theme."/smileys/theme")){
         return true;
       }
       else{
         return false;
       }
    }
    
    /**
    * Search if the templates folder theme is present
    * @param string $theme
    * @return boolean - true if the /themes/name/templates folder is present
    */
    function isThemeTemplates($theme){
       if(is_dir($this->dir_themes.$theme."/templates")){
         return true;
       }
       else{
         return false;
       }
    }    


    /**
    * Get the file from the templates themes/name/ directory
    * @return array $templates_files_list
    */
    function getThemesTemplatesFilesList($theme){
       $i=0;
       $dir_templates = $this->dir_themes.$theme."/templates/";
       $dir = opendir($dir_templates);
       while ($f = readdir($dir)) {
         if(is_file($dir_templates.$f) && $f!="." && $f!="..") {
            $templates_files_list[$i] = $f;
            $i++;
          }
       }
       
       if($i>0)
         return $templates_files_list;
       else
         return 0;
    }
    
}

?>