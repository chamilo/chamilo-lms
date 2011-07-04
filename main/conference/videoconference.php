<?php require_once '../inc/global.inc.php';?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="SHORTCUT ICON" href="http://www.laszlosystems.com/favicon.ico">
<title>Dokeos Videoconference</title>
<style type="text/css">
html, body { margin: 0; padding: 0; height: 100%; }
body { background-color: #eaeaea; }
</style></head>
<body align="center" valign="middle"><object type="application/x-shockwave-flash" data="classroom.swf?lzproxied=false&debug=true&useRtmpt=<?php echo (api_get_setting('service_visio','visio_use_rtmpt')=='true'?'true':'false');?>" width="1005" height="720">
<param name="movie" value="classroom.swf?lzproxied=false&debug=true&useRtmpt=<?php echo (api_get_setting('service_visio','visio_use_rtmpt')=='true'?'true':'false');?>">
<param name="quality" value="high">
<param name="scale" value="noscale">
<param name="salign" value="LT">
<param name="menu" value="false"></object></body>
</html>
