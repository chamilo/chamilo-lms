<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = new AppPlugin();
$pluginList = $plugin->getInstalledPlugins();
$capturePluginInstalled = in_array('jcapture', $pluginList);
if (!$capturePluginInstalled) {
    exit;
}

$capturePath = api_get_path(WEB_PLUGIN_PATH).'jcapture/';
$hostName = api_get_path(WEB_PATH);

define('DOKU_BASE', '/tmp');
function getSecurityToken()
{
}

//close sesseion
session_write_close();

header('Content-Type: text/html; charset=utf-8');
$imageFormat = "PNG";
$cookies = null;
foreach (array_keys($_COOKIE) as $cookieName) {
    $cookies .= bin2hex($cookieName)."=".bin2hex($_COOKIE[$cookieName]).";";
}

$pageName = 'file';
$edid = '1';
?>
<script>
function insertAtCarret() {
    location.reload();
}
</script>
<object type="application/x-java-applet">
    <param name="ID" value="jCaptureApplet"  />
    <param name="scriptable" value="true" />
    <param name="mayscript" value="true" />
    <param name="JAVA_CODEBASE" value="/somenonexistingcodebase" />
    <param name="WIDTH" value="1" />
    <param name="HEIGHT" value="1" />
    <param name="CODE" value="com.hammurapi.jcapture.JCaptureApplet.class" />
    <param name="ARCHIVE" value="<?php echo $capturePath; ?>lib/jcapture.jar" />
    <param name="NAME" value="jCapture" />
    <param name="dokuBase" value="<?php echo bin2hex(DOKU_BASE); ?>" />
    <param name="sectok" value="<?php echo getSecurityToken(); ?>" />
    <param name="cookies" value="<?php echo $cookies; ?>" />
    <param name="pageName" value="<?php echo $pageName; ?>" />
    <param name="edid" value="<?php echo $edid; ?>" />
    <param name="host" value="<?php echo $hostName; ?>" />
    <param name="uploadUrl" value="<?php echo $capturePath.'upload.php'; ?>" />
    <param name="scriptable" value="false" />
    <param name="pluginspage" value="http://java.sun.com/products/plugin/index.html#download" />

    <embed type="application/x-java-applet;jpi-version=1.7.0"
           ID = "jCaptureApplet"
           scriptable = "true"
           mayscript = "true"
           JAVA_CODEBASE = "/somenonexistingcodebase"
           WIDTH = "1"
           HEIGHT = "1"
           CODE = "com.hammurapi.jcapture.JCaptureApplet.class"
           ARCHIVE = "<?php echo $capturePath; ?>lib/jcapture.jar"
           NAME = "jCapture"
           dokuBase ="<?php echo bin2hex(DOKU_BASE); ?>"
           sectok ="<?php echo getSecurityToken(); ?>"
           cookies ="<?php echo Security::remove_XSS($cookies); ?>"
           pageName = "<?php echo $pageName; ?>"
           edid = "<?php echo $edid; ?>"
           host ="<?php echo $hostName; ?>"
           uploadUrl = "<?php echo $capturePath.'upload.php'; ?>"
           scriptable = "false"
           pluginspage="http://java.sun.com/products/plugin/index.html#download">
    </embed>
    <?php echo get_lang('NoJava'); ?>

</object>



