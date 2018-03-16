<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays a help window.
 *
 *	@package chamilo.help
 */
require_once __DIR__.'/../inc/global.inc.php';

$help_name = isset($_GET['open']) ? Security::remove_XSS($_GET['open']) : null;
if (empty($help_name)) {
    api_not_allowed(true);
}

?>
<a class="btn btn-default" href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php">
    <?php echo get_lang('AccessToFaq'); ?>
</a>
<div class="page-header">
    <h3><?php echo get_lang('H'.$help_name); ?></h3>
</div>
<?php echo get_lang($help_name.'Content'); ?>
<hr>
<a class="btn btn-default" href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/faq.php">
    <?php echo get_lang('AccessToFaq'); ?>
</a>
