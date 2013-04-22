<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for creating course category
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Acces rights: anonymous users can't do anything usefull here.
api_block_anonymous_users();

$stok = Security::get_token();

?>

<!-- Actions: The menu with the different options in cathe course management -->
<div id="actions" class="actions">
    
	&nbsp;<a href="<?php echo api_get_self() ?>?action=sortmycourses"><?php echo Display::return_icon('back.png', get_lang('Back'),'','32'); ?></a>
    
</div>

<?php
    if (!empty($message)) { Display::display_confirmation_message($message, false); }
    if (!empty($error)) { Display::display_error_message($error, false); }
?>
    <form name="create_course_category" method="post" action="<?php echo api_get_self() ?>?action=createcoursecategory">
        <input type="hidden" name="sec_token" value="<?php echo $stok ?>">
        <input type="text" name="title_course_category" />
        <button type="submit" class="save" name="create_course_category"><?php echo get_lang('AddCategory') ?></button>
    </form>