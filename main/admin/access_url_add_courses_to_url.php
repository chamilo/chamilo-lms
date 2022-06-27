<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows platform admins to add users to urls.
 * It displays a list of users and a list of courses;
 * you can select multiple users and courses and then click on.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$first_letter_course = '';
$courses = [];
$url_list = [];
$users = [];

$tbl_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

/*	Header   */
$tool_name = get_lang('AddCoursesToURL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs')];

Display::display_header($tool_name);

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('edit.png', get_lang('EditCoursesToURL'), ''),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php'
);
echo '</div>';

api_display_tool_title($tool_name);

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $courses = is_array($_POST['course_list']) ? $_POST['course_list'] : [];
    $url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : [];
    $first_letter_course = $_POST['first_letter_course'];

    foreach ($users as $key => $value) {
        $users[$key] = intval($value);
    }

    if ($form_sent == 1) {
        if (count($courses) == 0 || count($url_list) == 0) {
            echo Display::return_message(get_lang('AtLeastOneCourseAndOneURL'), 'error');
        } else {
            UrlManager::add_courses_to_urls($courses, $url_list);
            echo Display::return_message(get_lang('CourseBelongURL'), 'confirm');
        }
    }
}

$first_letter_course_lower = Database::escape_string(api_strtolower($first_letter_course));

$sql = "SELECT code, title FROM $tbl_course
		WHERE 
            title LIKE '".$first_letter_course_lower."%' OR 
		    title LIKE '".$first_letter_course_lower."%'
		ORDER BY title, code DESC ";

$result = Database::query($sql);
$db_courses = Database::store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url WHERE active = 1 ORDER BY url";
$result = Database::query($sql);
$db_urls = Database::store_result($result);
unset($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('CourseList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterCourse'); ?> :
     <select name="first_letter_course" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
    <?php
    echo Display::get_alphabet_options($first_letter_course);
    echo Display::get_numeric_options(0, 9, $first_letter_course);
    ?>
     </select>
    </td>
        <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('URLList'); ?> :</b>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="course_list[]" multiple="multiple" size="20" style="width:400px;">
		<?php foreach ($db_courses as $course) {
        ?>
			<option value="<?php echo $course['code']; ?>" <?php if (in_array($course['code'], $courses)) {
            echo 'selected="selected"';
        } ?>>
                <?php echo $course['title'].' ('.$course['code'].')'; ?>
            </option>
        <?php
    } ?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <button type="submit" class="add"> <?php echo get_lang('AddCoursesToThatURL'); ?> </button>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:300px;">
    <?php foreach ($db_urls as $url_obj) {
        ?>
    <option value="<?php echo $url_obj['id']; ?>" <?php if (in_array($url_obj['id'], $url_list)) {
            echo 'selected="selected"';
        } ?>>
        <?php echo $url_obj['url']; ?>
    </option>
    <?php
    } ?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php

Display::display_footer();
