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

// Name of the language file that needs to be included.
$language_file = 'learnpath';

require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'scorm.class.php';

if (empty($debug)) { $debug = 0; }
$error = '';
$display_mode = '';
if (isset($_SESSION['lpobject'])) {
    $temp = $_SESSION['lpobject'];
    $_SESSION['oLP'] = unserialize($temp);
    $error = $_SESSION['oLP']->error;
    $display_mode = $_SESSION['oLP']->mode;
}
if ($debug > 0) { error_log('New LP - Loaded lp_message : '.$_SERVER['REQUEST_URI'].' from '.$_SERVER['HTTP_REFERER'], 0); }

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
  var dokeos_xajax_handler = window.parent.oxajax;
</script>';
$lp_theme_css=$_SESSION['oLP']->get_theme();
$scorm_css_header = true;
include_once '../inc/reduced_header.inc.php';
// Close the session immediately to avoid concurrent access problems.
session_write_close();
?>
<body dir="<?php echo api_get_text_direction(); ?>">
<div id="msg_div_id">
<?php
echo $error;
?>
</div>
</body></html>
