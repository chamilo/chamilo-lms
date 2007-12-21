<?php
require('../../inc/global.inc.php');
Display::display_header('OpenID', NULL);
?>
<p>
<?php echo get_lang('OpenIDWhatIs');?>
<br />
<?php echo get_lang('OpenIDDescription');?>
</p>
<p>
<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>">Return to the previous page</a>
</p>
<?php
Display::display_footer();
?>