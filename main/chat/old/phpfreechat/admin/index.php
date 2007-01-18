<?php
# lang
require_once("../src/pfci18n.class.php");
require_once("inc.conf.php");
pfcI18N::Init($lang,"admin");

# version class
require_once("version.class.php");
$version = new version();
?>

<?php
// TOP //
include("index_html_top.php");
?>

<div class="content">
  <h2><?php echo _pfc("Administration"); ?></h2>

  <div><h3><?php echo _pfc("Available Languages"); ?></h3>
    <ul>
      <li><form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
          <select name="lang">
        <?php 
        $available_admin_lang = pfcI18N::GetAcceptedLanguage("admin");
        for($i=0;$i<count($available_admin_lang);$i++) {
          if ($lang==$available_admin_lang[$i])
            $selected ="selected=\"selected\"";
          else
            $selected ="";
          echo "<option value=\"$available_admin_lang[$i]\" $selected>$available_admin_lang[$i]</option>";
        }
        ?>
          </select>
          <input type="submit" name="submit" value="Ok"/>
          </form>
      </li>
    </ul>
  </div>


  <div><h3><?php echo _pfc("PFC version verification"); ?></h3>
  <?php
  if ($version->getPFCOfficialCurrentVersion()==0){
  ?>
    <ul>
      <li><?php echo _pfc("Internet connection is not possible"); ?></li>
      <li><?php echo _pfc("PFC version"); ?> : <?php echo $version->getLocalVersion(); ?></li>
    </ul>
  </div>

  <?php
  }
  elseif (($version->getLocalVersion())==($version->getPFCOfficialCurrentVersion())){
  ?>
    <ul>
      <li><span style="color:#339933;"><img src="style/check_on.png" alt="<?php echo _pfc("PFC is update"); ?>"> <?php echo _pfc("PFC is update"); ?></span></li>
      <li><?php echo _pfc("PFC version"); ?> : <?php echo $version->getLocalVersion(); ?></li>
    </ul>

  <?php
  }
  else{
  ?>
    <ul>
      <li><span style="color:#FF0000;"><img src="style/check_off.png" alt="<?php echo _pfc("PFC is not update"); ?>"> <?php echo _pfc("PFC is not update"); ?></span></li>
      <li><?php echo _pfc("Your version"); ?> : <?php echo $version->getLocalVersion(); ?></li>
      <li><?php echo _pfc("The last official version"); ?> : <?php echo $version->getPFCOfficialCurrentVersion(); ?></li>
      <li><?php echo _pfc("Download the last version %s here %s.","<a href=\"http://sourceforge.net/project/showfiles.php?group_id=158880\">","</a>"); ?></li>
    </ul>

<?php
}  
?>
  </div>

</div>

<?php
// BOTTOM
include("index_html_bottom.php");
?>