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

?>
<a class="btn" href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq'); ?></a>

<div class="page-header">
    <h3><?php echo get_lang('H'.$help_name); ?></h3>
</div>

<?php echo get_lang($help_name.'Content'); ?>    
<hr>    
<a class="btn" href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php"><?php echo get_lang('AccessToFaq'); ?></a>