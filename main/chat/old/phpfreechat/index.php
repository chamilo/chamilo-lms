<?php

require_once dirname(__FILE__)."/src/phpfreechat.class.php";
$params = array();
$params["title"] = "Quick chat";
$params["nick"] = "guest".rand(1,1000);  // setup the intitial nickname
$params["isadmin"] = true; // just for debug ;)
$params["serverid"] = md5(__FILE__); // calculate a unique id for this chat
$chat = new phpFreeChat( $params );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
 <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>phpFreeChat- Sources Index</title>
  <link rel="stylesheet" title="classic" type="text/css" href="style/generic.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/header.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/footer.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/menu.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/content.css" />  
  <?php $chat->printJavascript(); ?>
  <?php $chat->printStyle(); ?>  
 </head>
 <body>

<div class="header">
      <h1>phpFreeChat - Sources Index</h1>
      <img alt="logo bulle" src="style/bulle.gif" class="logo2" />
</div>

<div class="menu">
      <ul>
        <li class="sub title">General</li>
        <li>
          <ul class="sub">
            <li class="item">
              <a href="demo/">Demos</a>
            </li>
            <?php if (file_exists(dirname(__FILE__)."/checkmd5.php")) { ?>
            <li>
              <a href="checkmd5.php">Check md5</a>
            </li>
            <?php } ?>
            <!--
            <li class="item">
              <a href="admin/">Administration</a>
            </li>
            -->
          </ul>
        </li>
        <li class="sub title">Documentation</li>
        <li>
          <ul>
            <li class="item">
              <a href="overview.en.html">Overview [en]</a>
            </li>
            <li class="item">
              <a href="overview.fr.html">Overview [fr]</a>
            </li>
            <li class="item">
              <a href="overview.es.html">Overview [es]</a>
            </li>
            <li class="item">
              <a href="overview.ar.html">Overview [zh]</a>
            </li>
            <li class="item">
              <a href="overview.ar.html">Overview [ar]</a>
            </li>
            <li class="item">
              <a href="install.en.html">Install [en]</a>
            </li>
            <li class="item">
              <a href="install.fr.html">Install [fr]</a>
            </li>
            <li class="item">
              <a href="faq.en.html">FAQ [en]</a>
            </li>
            <li class="item">
              <a href="faq.fr.html">FAQ [fr]</a>
            </li>
            <li class="item">
              <a href="customize.en.html">Customize [en]</a>
            </li>
            <li class="item">
              <a href="customize.fr.html">Customize [fr]</a>
            </li>
            <li class="item">
              <a href="changelog.en.html">ChangeLog [en]</a>
            </li>
            <li class="item">
              <a href="changelog.fr.html">ChangeLog [fr]</a>
            </li>
          </ul>
        </li>
      </ul>
      <p class="partner">
        <a href="http://www.phpfreechat.net"><img alt="phpfreechat.net" src="style/logo_88x31.gif" /></a><br/>
        <a href="http://sourceforge.net/projects/phpfreechat"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=158880&amp;type=1" alt="SourceForge.net Logo" height="31px" width="88px" /></a><br/><br/>
        <a href="http://www.hotscripts.com/?RID=N452772">hotscripts.com</a><br/>
        <a href="http://www.jeu-gratuit.net/?refer=phpfreechat">jeu-gratuit.net</a><br/>
        <a href="http://www.pronofun.com/?refer=phpfreechat">pronofun.com</a><br/>
      </p>
</div>

<div class="content">
  <?php $chat->printChat(); ?>
</div>

</body></html>
