<?php
/* For licensing terms, see /license.txt */

/**
 * Container script for the messages coming from the learnpath object. Initially, this wasn't supposed to be
 * a separate file but rather some text included in lp_view.php, but SCORM involves loading a script that
 * saves the data asynchronously while the SCORM learning path carries on. Having an informational iframe
 * helps not popping up an additional window when saving data.
 *
 * This script is also used to refresh the TOC as sometimes the SCORM JS messages are taken into account
 * only after the TOC is drawn. As such, you might complete an item, browse to the next page, have the
 * TOC drawn with your 'incomplete' status, while the SCORM messages generally arrives just after the TOC
 * is drawn. By updating it here and in lp_save.php, we avoid funny visual effect like having a complete
 * item showing as incomplete.
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Code
 */
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once 'back_compat.inc.php';

$htmlHeadXtra[] = '<script language="javascript">
function cleanlog(){
  if(document.getElementById){
      document.getElementById("log_content").innerHTML = "";
  }
}
</script>';

$scorm_css_header = true;
$display_mode = '';
$lp_theme_log = true;
include_once '../inc/reduced_header.inc.php';
?>
<body dir="<?php echo api_get_text_direction(); ?>">
<div id="log_content">
</div>
<div style="color: white;" onclick="javascript: cleanlog();">.</div>
</body>
</html>
