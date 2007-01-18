<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
 <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <title>phpFreeChat - Administration</title>
  <link rel="stylesheet" title="classic" type="text/css" href="style/generic.css">
  <link rel="stylesheet" title="classic" type="text/css" href="style/header.css">
  <link rel="stylesheet" title="classic" type="text/css" href="style/footer.css">
  <link rel="stylesheet" title="classic" type="text/css" href="style/menu.css">
  <link rel="stylesheet" title="classic" type="text/css" href="style/content.css">
  <script type="text/javascript" src="style/show.js"></script>
 </head>
 <body>

<div class="header">
      <h1>phpFreeChat - Administration</h1>
      <img alt="logo bulle" src="style/bulle.png" class="logo2">
</div>

<div class="menu">
      <ul>
        <li class="sub title">General</li>
        <li>
          <ul class="sub">
            <li class="item">
              <a href="index.php">Administration Index</a>
            <li class="item">
              <a href="../index.php">PFC Index</a>
            </li>
            </li>
            <li class="item">
              <a href="user.php">Users</a>
            </li>
            <li class="item">
              <a href="configuration.php">Configuration</a>
            </li>
            <li class="item">
              <a href="themes.php">Themes</a>
            </li>
          </ul>
        </li>
        <li class="sub title">Other</li>
        <li>
          <ul>
            <li class="item">
              <a href="#">other</a>
            </li>
            <li class="item">
              <a href="#">other</a>
            </li>
          </ul>
        </li>
        <li class="sub title">Connected User</li>
        <li>
          <ul>
            <li class="item">
              <a href="#"><?php echo empty($_SERVER['REMOTE_USER']) ?  "No user connected" : $_SERVER['REMOTE_USER']; ?></a>
            </li>
          </ul>
        </li>
        
      </ul>
      <p class="partner">
        <a href="http://www.phpfreechat.net"><img alt="logo big" src="style/logo_88x31.gif"></a>
      </p>
</div>
