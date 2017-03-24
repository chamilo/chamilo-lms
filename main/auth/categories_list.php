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
    <a href="<?php echo api_get_self() ?>?action=sortmycourses">
        <?php echo Display::return_icon('back.png', get_lang('Back'),'','32'); ?>
    </a>
</div>

<?php
$form = new FormValidator(
    'create_course_category',
    'post',
    api_get_self().'?createcoursecategory'
);
$form->addHidden('sec_token', $stok);
$form->addText('title_course_category', get_lang('Name'));
$form->addButtonSave(get_lang('AddCategory'), 'create_course_category');
$form->display();
