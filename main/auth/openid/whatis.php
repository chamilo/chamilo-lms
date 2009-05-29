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
<a href="<?php
 if (strpos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST'])) {
 	 echo Security::remove_XSS($_SERVER['HTTP_REFERER']); 
 } 
 ?>">Return to the previous page</a>
</p>
<?php
Display::display_footer();
?>