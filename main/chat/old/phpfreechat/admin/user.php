<?php

# lang
require_once("../src/pfci18n.class.php");
require_once("inc.conf.php");
pfcI18N::Init($lang,"admin");

# htaccess class
require_once ('htaccess.class.php');
$ht = new htaccess();

# Activate Authentification
if(!empty($_GET['active'])){
 if($_GET['active']==1 && $ht->getNumberOfUsers()>0) $ht->printHtaccess();
 elseif($_GET['active']==1 && $ht->getNumberOfUsers()==0){
     $msg = _pfc("At least one user must be declare to activate authentication.");
     header('Location: '.$_SERVER['PHP_SELF'].'?msg='.rawurlencode($msg));
	 exit;
 }
 elseif($_GET['active']==2 && file_exists($ht->get_file_Htaccess())) $ht->delHtaccess();
}

# Delete a user
if(!empty($_GET['del']) && !empty($_GET['username']) )
{
    if($ht->getNumberOfUsers()==1){
		$msg = _pfc("It is not possible to delete the last user.");
		header('Location: '.$_SERVER['PHP_SELF'].'?msg='.rawurlencode($msg));
		exit;
    }
    else{
		$username = $_GET['username'];
		$ht->delUser($username);
		
		$groups = $ht->getGroupOfUser($username);
		if ($groups!=0){ #User is not in a group
		  for($i=0;$i<count($groups);$i++) {
			$ht->delUserFromGroup($username,$groups[$i]);
		  }
		}

		$msg = _pfc("User %s deleted.",$username);
		header('Location: '.$_SERVER['PHP_SELF'].'?msg='.rawurlencode($msg));
		exit;
	}
}

# Modification or Creation of a user
if(!empty($_POST['username'])){
  $username = $_POST['username'];
  $password = $_POST['password'];
  $create=0;
  
  if(!$ht->isUser($username)){ #Add User
    if(!empty($password)) {
      $ht->addUser($username,$password);
      $create=1;
      }
  }
  else{ #Modify User
    if(!empty($password))
      $ht->setPasswd($username,$password);
  }
  
  $groups= $ht->getGroups();
  for($j=0;$j<count($groups);$j++) {
	  $group = $_POST['group'];
	  if($group==$groups[$j])
	    $ht->addUserInGroup($username,$groups[$j]);
	  else
	    $ht->delUserFromGroup($username,$groups[$j]);
  }
  
  
  if($create==1)
    $msg = _pfc("User %s added.", $username);
  else
    $msg = _pfc("User %s edited.", $username);
  header('Location: '.$_SERVER['PHP_SELF'].'?msg='.rawurlencode($msg));
  exit;
  
}
?>

<?php
// TOP //
include("index_html_top.php");
?>

<div class="content">
<h2><?php echo _pfc("Users management"); ?></h2>

<?php
    if(!file_exists($ht->get_file_Htaccess()))
      echo "<div class=\"ko\"><h3><img src=\"style/check_off.png\" alt=\""._pfc("Authentication disable")."\"> "._pfc("Authentication disable")." - <a href=\"".$_SERVER['PHP_SELF']."?active=1\">"._pfc("Enable here")."</a></h3></div>";
    else{
      echo "<div class=\"ok\"><h3><img src=\"style/check_on.png\" alt=\""._pfc("Authentication enable")."\"> "._pfc("Authentication enable")." - <a href=\"".$_SERVER['PHP_SELF']."?active=2\">"._pfc("Disable here")."</a></h3></div>";
    
    }
      
      
    if(!empty($_GET['msg']))
      echo "<div class=\"message\"><h3>".$_GET['msg']."</h3></div>";
  
	$users= $ht->getUsers();
	if($users!=0) {
		for($i=0;$i<count($users);$i++) {
		  echo "<div class=\"showbox\">";
		  echo "<h4>".$users[$i];
		  echo " [ <a style=\"font-weight: normal;\" href=\"#\" onclick=\"openClose('$users[$i]', 0); return false;\">"._pfc("Edit")."</a> - <a style=\"font-weight: normal;\" href=\"".$_SERVER['PHP_SELF']."?username=$users[$i]&amp;del=1\" onclick=\"return window.confirm('"._pfc("Do you really want to delete %s ?",$users[$i])."')\">"._pfc("Delete")."</a> ]</p>";
		  echo "</h4>";
		  echo "<div id=\"$users[$i]\" style=\"display: none;\">";
		  echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
		  echo "  <input type=\"hidden\" name=\"username\" id=\"username\" value=\"$users[$i]\" />";
		  echo "  <p class=\"field\"><label for=\"password\">"._pfc("Password").": </label><input type=\"text\" size=\"30\" name=\"password\" id=\"password\" /></p>";
		  
		  echo "  <p class=\"field\"><label for=\"group\">"._pfc("Group").": </label><select name=\"group\" id=\"group\">";
		  $groups= $ht->getGroups();
		  for($j=0;$j<count($groups);$j++) {
		    if ($ht->isUserInGroup($users[$i],$groups[$j]))
		      $selected = "selected=\"selected\"";
		    else
		      $selected = "";
		    echo "<option value=\"".$groups[$j]."\" $selected>".$groups[$j]."</option>";
		  }
		  echo "  </select></p>";
		  
		  echo "  <p class=\"field\"><input class=\"submit\" type=\"submit\" value=\"ok\"/></p>";
		  echo "</form>";
		  echo "</div>";
		  echo "</div>";
		}
    }



		  echo "<div class=\"showbox\">";
		  echo "<h4>"._pfc("Add a new user")."</h4>";
		  echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
		  echo "  <p class=\"field\"><label for=\"username\" >"._pfc("Username").": </label><input type=\"text\" size=\"30\" maxlength=\"32\" name=\"username\" id=\"username\" /> </p>";
		  echo "  <p class=\"field\"><label for=\"password\" >"._pfc("Password").": </label><input type=\"text\" size=\"30\" name=\"password\" id=\"password\" /></p>";
		  
		  echo "  <p class=\"field\"><label for=\"group\" >"._pfc("Group").": </label><select name=\"group\" id=\"group\" >";
		  $groups= $ht->getGroups();
		  for($j=0;$j<count($groups);$j++) {
		    echo "<option value=\"".$groups[$j]."\" >".$groups[$j]."</option>";
		  }
		  echo "  </select></p>";
		  
		  echo "  <p class=\"field\"><input class=\"submit\" type=\"submit\" value=\"ok\"/></p>";
		  echo "</form>";
		  echo "</div>";

?>

</div>

<?php
// BOTTOM
include("index_html_bottom.php");
?>