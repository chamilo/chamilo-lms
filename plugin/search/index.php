<?php
/* See license terms in /license.txt */
/**
 * This script is used as the entrance to the search plugin page. Due to its short form,
 * it can be included as a plugin in the Chamilo menu bar.
 * No process is operated here. Just displaying the HTML form.
 * @package chamilo.search
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
/**
 * Variables
 */
include('client/client.conf.php');
?>
<form method="get" style='margin:0px;padding:0px;width:70px;background-color:transparent;height:20px;display:inline;margin-left: 20px;' action="<?php echo $search_url; ?>"><input
type="hidden" name="ps" value="1000"/><input
type="hidden" name="o" value="0"/><input
type="hidden" name="m" value="any"/><input
type="hidden" name="wm" value="sub"/><input
type="hidden" name="wf" value="2221"/><input
type="hidden" name="s" value="RDP"/><input
type="hidden" name="sy" value="1"/><input
type="text" name="q" value="<?php echo $lang_search_button; ?>..." size="10" style="margin: 0px; border: 1px solid #B6BB8C; color:#4D4F3A; height: 15px;padding:0px;" onclick="javascript:this.value='';"><!--input
type="submit" name="submit" value="<?php echo $lang_search_button; ?>" style="margin: 4px 6px; border: 1px solid #B6BB8C; color:#4D4F3A; height:17px;vertical-align:top;padding:0px"--></form>
