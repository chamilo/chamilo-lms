<?php
    require_once '../inc/global.inc.php';
    $type = $_REQUEST['type'];
    $src  = Security::remove_XSS($_REQUEST['src']);
    if ($type == 'youtube')  {
        $src = 'http://www.youtube.com/embed/'.$src;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
      <div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;">
        <br />
        <iframe class="youtube-player" type="text/html" width="640" height="385" src="<?php echo $src; ?>" frameborder="0">
        </iframe>
      </div>
  </body>
</html>

<?php 
    } else {
        api_not_allowed();
    }