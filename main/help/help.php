<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package chamilo.help
 */
/**
 * Code
 */

// Language file that needs to be included
$language_file = 'help';
require_once '../inc/global.inc.php';
$help_name = Security::remove_XSS($_GET['open']);

Display::display_reduced_header();
?>
<div style="margin:10px;">
    <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq') ?></a>
    <h4>
        <?php echo get_lang('H'.$help_name); ?>
    </h4>
    <?php echo get_lang($help_name.'Content'); ?>
    <br /><br />
    <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq'); ?></a>
</div>
