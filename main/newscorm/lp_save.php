<?php
/* For licensing terms, see /license.txt */

/**
 * Script that handles the saving of item status
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Initialization is to be done by lp_controller.php.
 */

/**
 * Switching within the field to update
 */
$msg = $_SESSION['oLP']->get_message();

error_log('New LP - Loaded lp_save : '.$_SERVER['REQUEST_URI'].' from '.$_SERVER['HTTP_REFERER'], 0);
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<script language='javascript'>
<?php
if ($_SESSION['oLP']->mode != 'fullscreen'){
}
?>
</script>

</head>
<body dir="<?php echo api_get_text_direction(); ?>">
</body></html>
