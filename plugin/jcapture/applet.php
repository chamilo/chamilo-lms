<?php

//fix for Opera XMLHttpRequests
if (!count($_POST) && $HTTP_RAW_POST_DATA) {
    parse_str($HTTP_RAW_POST_DATA, $_POST);
}

if (!defined('DOKU_INC')) {
    define('DOKU_INC', __DIR__.'/../../../');
}
require_once DOKU_INC.'inc/init.php';
require_once DOKU_INC.'inc/common.php';
require_once DOKU_INC.'inc/pageutils.php';
require_once DOKU_INC.'inc/auth.php';
//close sesseion
session_write_close();
header('Content-Type: text/html; charset=utf-8');
$hostName = "http".($_SERVER['HTTPS'] ? 's' : null).'://'.$_SERVER['HTTP_HOST'];
$imageFormat = "PNG";
$cookies = '';
foreach (array_keys($_COOKIE) as $cookieName) {
    $cookies .= bin2hex($cookieName)."=".bin2hex($_COOKIE[$cookieName]).";";
}

$pageName = $_GET["pageName"];
$edid = $_GET["edid"];
?>
<script language="JavaScript" type="text/javascript">
    var _info = navigator.userAgent;
    var _ns = false;
    var _ns6 = false;
    var _ie = (_info.indexOf("MSIE") > 0 && _info.indexOf("Win") > 0 && _info.indexOf("Windows 3.1") < 0);
</script>
<comment>
    <script language="JavaScript" type="text/javascript">
    var _ns = (navigator.appName.indexOf("Netscape") >= 0 && ((_info.indexOf("Win") > 0 && _info.indexOf("Win16") < 0 && java.lang.System.getProperty("os.version").indexOf("3.5") < 0) || (_info.indexOf("Sun") > 0) || (_info.indexOf("Linux") > 0) || (_info.indexOf("AIX") > 0) || (_info.indexOf("OS/2") > 0) || (_info.indexOf("IRIX") > 0)));
    var _ns6 = ((_ns == true) && (_info.indexOf("Mozilla/5") >= 0));
    </script>
</comment>
<script language="JavaScript" type="text/javascript"><!--
    if (_ie == true) document.writeln('<object classid="clsid:CAFEEFAC-0017-0000-0020-ABCDEFFEDCBA" NAME = "jCapture"  WIDTH = "1" HEIGHT = "1" codebase="http://java.sun.com/update/1.7.0/jinstall-1_7_0-windows-i586.cab#Version=7,0,0,0"><xmp>');
    else if (_ns == true && _ns6 == false) document.writeln('<embed ' +
	    'type="application/x-java-applet;jpi-version=1.7.0" \
            ID = "jCaptureApplet" \
            scriptable = "true" \
            mayscript = "true" \
            WIDTH = "1"
            JAVA_CODEBASE = "/somenonexistingcodebase" \
            HEIGHT = "1"
            CODE = "com.hammurapi.jcapture.JCaptureApplet.class" \
            ARCHIVE = "<?php echo DOKU_BASE; ?>lib/plugins/jcapture/lib/jcapture.jar" \
            NAME = "jCapture" \
            dokuBase ="<?php echo bin2hex(DOKU_BASE); ?>" \
            sectok ="<?php echo getSecurityToken(); ?>" \
            cookies ="<?php echo $cookies; ?>" \
            authtok = "<?php echo auth_createToken(); ?>" \
            pageName = "<?php echo $pageName; ?>" \
            edid = "<?php echo $edid; ?>" \
            host ="<?php echo $hostName; ?>" ' +
	    'scriptable=true ' +
	    'pluginspage="http://java.sun.com/products/plugin/index.html#download"><xmp>');
//--></script>
<applet id="jCaptureApplet" CODE = "com.hammurapi.jcapture.JCaptureApplet.class" WIDTH="1" HEIGHT="1" ARCHIVE = "<?php echo DOKU_BASE; ?>/lib/plugins/jcapture/lib/jcapture.jar" NAME = "jCapture"></xmp>
    <PARAM NAME = CODE VALUE = "com.hammurapi.jcapture.JCaptureApplet.class" >
    <PARAM NAME = ARCHIVE VALUE = "<?php echo DOKU_BASE; ?>lib/plugins/jcapture/lib/jcapture.jar" >
    <PARAM NAME = NAME VALUE = "jCapture" >
    <PARAM NAME="type" value="application/x-java-applet;jpi-version=1.7.0">
    <PARAM NAME="scriptable" value="true">
    <PARAM NAME="mayscript" value="true">
    <PARAM NAME = "dokuBase" VALUE="<?php echo bin2hex(DOKU_BASE); ?>">
    <PARAM NAME = "sectok" VALUE="<?php echo getSecurityToken(); ?>">
    <PARAM NAME = "cookies" VALUE="<?php echo $cookies; ?>">
    <PARAM NAME = "host" VALUE="<?php echo $hostName; ?>">
    <PARAM NAME = "pageName" VALUE="<?php echo $pageName; ?>">
    <PARAM NAME = "edid" VALUE="<?php echo $edid; ?>">
    <PARAM NAME = CODEBASE VALUE = "/somenonexistingcodebase" >
    <PARAM NAME = "authtok" VALUE="<?php echo auth_createToken(); ?>">

Java 2 Standard Edition v 1.7 or above is required for this applet.<br/>
		Download it from <a href="http://java.sun.com">http://java.sun.com</a>.
</applet>
</embed>
</object>

<!--
<APPLET CODE = "com.hammurapi.jcapture.JCaptureApplet.class" ARCHIVE = "<?php echo DOKU_BASE; ?>/lib/plugins/jcapture/lib/jcapture.jar" NAME = "jCapture">
<PARAM NAME = "dokuBase" VALUE="<?php echo bin2hex(DOKU_BASE); ?>">
<PARAM NAME = "sectok" VALUE="<?php echo getSecurityToken(); ?>">
<PARAM NAME = "cookies" VALUE="<?php echo Security::remove_XSS($cookies); ?>">
<PARAM NAME = "host" VALUE="<?php echo $hostName; ?>">
Java 2 Standard Edition v 1.7 or above is required for this applet.<br/>
		Download it from <a href="http://java.sun.com">http://java.sun.com</a>.

</APPLET>
-->
