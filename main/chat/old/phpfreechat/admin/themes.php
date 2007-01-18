<?php
# lang
require_once("../src/pfci18n.class.php");
require_once("inc.conf.php");
pfcI18N::Init($lang,"admin");

# themes class
require_once("themes.class.php");
$themes = new themes();

?>

<?php
// TOP //
include("index_html_top.php");
?>

<div class="content">
  <h2><?php echo _pfc("Available themes"); ?></h2>
<?php

  $themes_list = $themes->getThemesList();
  for($i=0;$i<count($themes_list);$i++) {

    echo "<div class=\"showbox\">";
    echo "<h4><a href=\"#\" onclick=\"openClose('$themes_list[$i]', 0); return false;\">".$themes_list[$i]."</a>";
    $info = $themes->getThemeInfo($themes_list[$i]);
    if ($info!='0') echo " ( $info ) ";
    echo "</h4>";
    
    echo "<div id=\"$themes_list[$i]\" style=\"display: none;\">";
    echo "<ul>";
    
    if($themes->isThemeImages($themes_list[$i]))
       echo "<li>Images <img src=\"style/check_on.png\" alt=\"On\" /></li>";
    else
       echo "<li>Images <img src=\"style/check_off.png\" alt=\"Off\" /></li>";
    
    if($themes->isThemeSmiley($themes_list[$i]))
       echo "<li>Smiley <img src=\"style/check_on.png\" alt=\"On\" /></li>";
    else
       echo "<li>Smiley <img src=\"style/check_off.png\" alt=\"Off\" /></li>";
       
    if($themes->isThemeTemplates($themes_list[$i])){
       echo "<li>Templates <img src=\"style/check_on.png\" alt=\"On\" /></li>";
       $templates_files_list = $themes->getThemesTemplatesFilesList($themes_list[$i]);
       echo "<ul>";
       for($j=0;$j<count($templates_files_list);$j++) {
         echo "<li>$templates_files_list[$j]</li>";
       }
       echo "</ul>";
    }
    else
       echo "<li>Templates <img src=\"style/check_off.png\" alt=\"Off\" /></li>";
    echo "</ul>";          
    echo "</div>";
    echo "</div>";
  }

?>
</div>

<?php
// BOTTOM
include("index_html_bottom.php");
?>