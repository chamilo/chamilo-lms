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
    <?php if ($action != 'subscribe') { ?>
        &nbsp;<a href="<?php echo api_get_self() ?>?action=subscribe"><?php echo Display::return_icon('view_more_stats.gif', get_lang('SubscribeToCourse')).' '.get_lang('SubscribeToCourse') ?></a>&nbsp;
    <?php } else { ?>
        &nbsp;<strong><?php echo Display::return_icon('view_more_stats.gif', get_lang('SubscribeToCourse')).' '.get_lang('SubscribeToCourse') ?></strong>&nbsp;
    <?php } ?>

    <?php if ($action != 'sortmycourses' && isset($action)) { ?>
        &nbsp;<a href="<?php echo api_get_self() ?>?action=sortmycourses"><?php echo Display::return_icon('deplacer_fichier.gif', get_lang('SortMyCourses')).' '.get_lang('SortMyCourses') ?></a>&nbsp;
    <?php } else { ?>
        &nbsp;<strong><?php echo Display::return_icon('deplacer_fichier.gif', get_lang('SortMyCourses')).' '.get_lang('SortMyCourses') ?></strong>&nbsp;
    <?php } ?>

    <?php if ($action != 'createcoursecategory') { ?>
	&nbsp;<a href="<?php echo api_get_self() ?>?action=createcoursecategory"><?php echo Display::return_icon('folder_new.gif', get_lang('CreateCourseCategory')).' '.get_lang('CreateCourseCategory') ?></a>&nbsp;
    <?php } else { ?>
        &nbsp;<strong><?php echo Display::return_icon('folder_new.gif', get_lang('CreateCourseCategory')).' '.get_lang('CreateCourseCategory') ?></strong>&nbsp;
    <?php } ?>
</div>

<?php
    if (!empty($message)) { Display::display_confirmation_message($message, false); }
    if (!empty($error)) { Display::display_error_message($error, false); }
?>

    <form name="create_course_category" method="post" action="<?php echo api_get_self() ?>?action=createcoursecategory">
        <input type="hidden" name="sec_token" value="<?php echo $stok ?>">
        <input type="text" name="title_course_category" />
        <button type="submit" class="save" name="create_course_category"><?php echo get_lang('Ok') ?></button>
    </form>

<?php

echo get_lang('ExistingCourseCategories');

if (!empty($user_course_categories)) {
?>
    <ul>
    <?php foreach ($user_course_categories as $row) { ?>
            <li><?php echo $row['title'] ?></li>
    <?php } ?>
    </ul>
<?php } ?>
