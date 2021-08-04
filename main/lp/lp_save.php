<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script that handles the saving of item status.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Initialization is to be done by lp_controller.php.
 * Switching within the field to update.
 */
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<script language='javascript'>
<?php
/** @var learnpath $lp */
$lp = Session::read('oLP');
if ('fullscreen' != $lp->mode) {
}
?>
</script>

</head>
<body dir="<?php echo api_get_text_direction(); ?>">
</body></html>
