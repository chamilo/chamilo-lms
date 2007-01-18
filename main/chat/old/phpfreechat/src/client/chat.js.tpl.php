<?php
require_once(dirname(__FILE__)."/../../lib/json/JSON.php");
$json = new Services_JSON();
?>
<?php $nick = $u->nick != "" ? $json->encode($u->nick) : $json->encode($c->nick); ?>

var pfc_nickname              = <?php echo ($GLOBALS["output_encoding"]=="UTF-8" ? $nick : iconv("UTF-8", $GLOBALS["output_encoding"],$nick)); ?>;
var pfc_nickid                = <?php echo $json->encode($u->nickid); ?>;
var pfc_version               = <?php echo $json->encode($version); ?>;
var pfc_clientid              = <?php echo $json->encode(md5(uniqid(rand(), true))); ?>;
var pfc_title                 = <?php echo $json->encode($title); ?>;
var pfc_refresh_delay         = <?php echo $json->encode($refresh_delay); ?>;
var pfc_max_refresh_delay         = <?php echo $json->encode($max_refresh_delay); ?>;
var pfc_start_minimized       = <?php echo $json->encode($start_minimized); ?>;
var pfc_nickmarker            = <?php echo $json->encode($nickmarker); ?>;
var pfc_clock                 = <?php echo $json->encode($clock); ?>;
var pfc_showsmileys           = <?php echo $json->encode($showsmileys); ?>;
var pfc_showwhosonline        = <?php echo $json->encode($showwhosonline); ?>;
var pfc_focus_on_connect      = <?php echo $json->encode($focus_on_connect); ?>;
var pfc_max_text_len          = <?php echo $json->encode($max_text_len); ?>;
var pfc_quit_on_closedwindow  = <?php echo $json->encode($quit_on_closedwindow); ?>;
var pfc_debug                 = <?php echo $json->encode($debug); ?>;
var pfc_btn_sh_smileys        = <?php echo $json->encode($btn_sh_smileys); ?>;
var pfc_btn_sh_whosonline     = <?php echo $json->encode($btn_sh_whosonline); ?>;
var pfc_displaytabimage       = <?php echo $json->encode($displaytabimage); ?>;
var pfc_displaytabclosebutton = <?php echo $json->encode($displaytabclosebutton); ?>;
var pfc_connect_at_startup    = <?php echo $json->encode($connect_at_startup); ?>;
var pfc_notify_window         = <?php echo $json->encode($notify_window); ?>;
var pfc_defaultchan = <?php echo $json->encode($c->channels); ?>;
var pfc_userchan = <?php $list = array(); foreach($u->channels as $item) {$list[] = $item["name"];} echo $json->encode($list); ?>;
var pfc_defaultprivmsg = <?php echo $json->encode($c->privmsg); ?>;
var pfc_userprivmsg = <?php $list = array(); foreach($u->privmsg as $item) {$list[] = $item["name"];} echo $json->encode($list); ?>;
var pfc_openlinknewwindow = <?php echo $json->encode($openlinknewwindow); ?>;
var pfc_bbcode_color_list = <?php $list = array(); foreach($bbcode_colorlist as $v) {$list[] = substr($v,1);} echo $json->encode($list); ?>;
var pfc_nickname_color_list = <?php echo $json->encode($nickname_colorlist); ?>;
var pfc_proxy_url = '<?php echo $data_public_url."/".$serverid."/proxy.php"; ?>';


// create our client which will do all the work on the client side !
var pfc = new pfcClient();
<?php

$labels_to_load =
array( "Do you really want to leave this room ?", // _pfc
       "Hide nickname marker", // _pfc
       "Show nickname marker", // _pfc
       "Hide dates and hours", // _pfc
       "Show dates and hours", // _pfc
       "Disconnect", // _pfc
       "Connect", // _pfc
       "Magnify", // _pfc
       "Cut down", // _pfc
       "Hide smiley box", // _pfc
       "Show smiley box", // _pfc
       "Hide online users box", // _pfc
       "Show online users box", // _pfc
       "Please enter your nickname", // _pfc
       "Private message", // _pfc
       "Close this tab", // _pfc
       "Enter your message here", // _pfc
       "Enter your nickname here", // _pfc
       "Bold", // _pfc
       "Italics", // _pfc
       "Underline", // _pfc
       "Delete", // _pfc
       "Mail", // _pfc
       "Color", // _pfc
       "PHP FREE CHAT [powered by phpFreeChat-%s]", // _pfc
       "Enter the text to format", // _pfc
       "Configuration has been rehashed", // _pfc
       "A problem occurs during rehash", // _pfc
       "Choosen nickname is allready used", // _pfc
       "phpfreechat current version is %s", // _pfc
       "Maximum number of joined channels has been reached", // _pfc
       "Maximum number of private chat has been reached", // _pfc
       "Click here to send your message", // _pfc
       "Send", // _pfc
       "You are not allowed to speak to yourself", // _pfc
       "Close", // _pfc
       );
foreach($labels_to_load as $l)
{
  echo "pfc.res.setLabel(".$json->encode($l).",".$json->encode(_pfc2($l)).");\n";
}

$fileurl_to_load =
array( 'images/ch.gif',
       'images/pv.gif',
       'images/tab_remove.gif',
       'images/ch-active.gif',
       'images/pv-active.gif',
       'images/user.gif',
       'images/user-me.gif',
       'images/color-on.gif',
       'images/color-off.gif',
       'images/clock-on.gif',
       'images/clock-off.gif',
       'images/logout.gif',
       'images/login.gif',
       'images/maximize.gif',
       'images/minimize.gif',
       'images/smiley-on.gif',
       'images/smiley-off.gif',
       'images/online-on.gif',
       'images/online-off.gif',
       'images/bt_strong.gif',
       'images/bt_em.gif',
       'images/bt_ins.gif',
       'images/bt_del.gif',
       'images/bt_mail.gif',
       'images/bt_color.gif',
       'images/color_transparent.gif',
       'images/close-whoisbox.gif',
       'images/openpv.gif',
       'images/user-admin.gif',
       );

foreach($fileurl_to_load as $f)
{
  echo "pfc.res.setFileUrl(".$json->encode($f).",pfc_proxy_url+'".$c->getFileUrlByProxy($f,false)."');\n";
}

foreach($smileys as $s_file => $s_str) { 
  for($j = 0; $j<count($s_str) ; $j++) {
    echo "pfc.res.setSmiley(".$json->encode($s_str[$j]).",pfc_proxy_url+'".$c->getFileUrlByProxy($s_file,false)."');\n";
  }
}

?>    
pfc.gui.buildChat();
pfc.connectListener();
pfc.refreshGUI();
if (pfc_connect_at_startup) pfc.connect_disconnect();

<?php if ($debugxajax) { ?>
xajax.DebugMessage = function(text)
{
  var s = new String(text);
  text = s.escapeHTML();
  rx  = new RegExp('&lt;','g');
  text = text.replace(rx, '\n&lt;');
  $('pfc_debugxajax').innerHTML += '\n---------------\n' + text;
}
<?php } ?>

<?php if ($debug) { ?>
var pfc_debug_color = true;
function trace(text) {
  var s = new String(text);
  text = s.escapeHTML();
  rx  = new RegExp('&lt;','g');
  text = text.replace(rx, '\n&lt;');
  var color = '';
  if (pfc_debug_color)
  {
    color = '#BBB';
    pfc_debug_color = false;
  }
  else
  {
    color = '#DDD';
    pfc_debug_color = true;
  }
  $('pfc_debug').innerHTML = '<p style="margin:0;border-bottom:1px solid #555;background-color:'+color+'">' + text + '</p>' + $('pfc_debug').innerHTML ;
}
<?php } ?>
