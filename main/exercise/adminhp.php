<?php
/* For licensing terms, see /license.txt */

/**
 * HotPotatoes administration.
 *
 * @author Istvan Mandak
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true);

$_course = api_get_course_info();

if (isset($_REQUEST["cancel"])) {
    if ($_REQUEST["cancel"] == get_lang('Cancel')) {
        header("Location: exercise.php");
        exit;
    }
}

$newName = !empty($_REQUEST['newName']) ? $_REQUEST['newName'] : '';
$hotpotatoesName = !empty($_REQUEST['hotpotatoesName']) ? Security::remove_XSS($_REQUEST['hotpotatoesName']) : '';
$is_allowedToEdit = api_is_allowed_to_edit(null, true);

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath = $documentPath.'/images';

// audio path
$audioPath = $documentPath.'/audio';

// Database table definitions
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    "url" => "exercise.php",
    "name" => get_lang('Tests'),
];
$nameTools = get_lang('Hot Potatoes Admin');

Display::display_header($nameTools, "Exercise");

/** @todo probably wrong !!!! */
require_once api_get_path(SYS_CODE_PATH).'/exercise/hotpotatoes.lib.php';
?>
<h4>
  <?php echo $nameTools; ?>
</h4>
<?php
if (isset($newName)) {
    if ($newName != "") {
        //alter database record for that test
        SetComment($hotpotatoesName, $newName);
        echo "<script> window.location='exercise.php'; </script>";
    }
}

echo "<form action=\"".api_get_self()."\" method='post' name='form1'>";
echo "<input type=\"hidden\" name=\"hotpotatoesName\" value=\"$hotpotatoesName\">";
echo "<input type=\"text\" name=\"newName\" value=\"";

$lstrComment = GetComment($hotpotatoesName);
if ($lstrComment == '') {
    $lstrComment = GetQuizName($hotpotatoesName, $documentPath);
}
if ($lstrComment == '') {
    $lstrComment = basename($hotpotatoesName, $documentPath);
}

echo $lstrComment;
echo "\" size=40>&nbsp;";
echo "<button type=\"submit\" class=\"save\" name=\"submit\" value=\"".get_lang('Validate')."\">".get_lang('Validate')."</button>";
echo "<button type=\"button\" class=\"cancel\" name=\"cancel\" value=\"".get_lang('Cancel')."\" onclick=\"javascript:document.form1.newName.value='';\">".get_lang('Cancel')."</button>";
echo "</form>";

Display::display_footer();
