<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use ChamiloSession as Session;

/**
 * Copy resources from one course in a session to another one.
 *
 * UI updated to TailwindCSS classes (legacy page).
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> Lots of bug fixes/improvements
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;

api_protect_admin_script();
api_protect_limit_for_session_admin();
api_set_more_memory_and_time_limits();

if (!defined('COURSE_COPY_TRACE_ENABLED')) {
    define('COURSE_COPY_TRACE_ENABLED', false);
}

/**
 * A short correlation id to group logs from the same request.
 */
if (!isset($GLOBALS['course_copy_trace_id'])) {
    try {
        $GLOBALS['course_copy_trace_id'] = bin2hex(random_bytes(6));
    } catch (\Throwable $e) {
        $GLOBALS['course_copy_trace_id'] = uniqid('cc_', true);
    }
}

/**
 * Lightweight logger (messages must be in English by convention).
 */
function course_copy_log(string $message, array $context = []): void
{
    if (!COURSE_COPY_TRACE_ENABLED) {
        return;
    }

    $traceId = $GLOBALS['course_copy_trace_id'] ?? 'no-trace';
    $payload = '';

    if (!empty($context)) {
        $payload = ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    error_log('[COURSE_COPY]['.$traceId.'] '.$message.$payload);
}

/**
 * Push a notice HTML block that will be rendered below the back button.
 * This keeps messages consistent and avoids scattering echo() messages everywhere.
 */
function course_copy_push_notice(string $html): void
{
    if (empty($html)) {
        return;
    }

    if (!isset($GLOBALS['course_copy_notice_html']) || !is_string($GLOBALS['course_copy_notice_html'])) {
        $GLOBALS['course_copy_notice_html'] = '';
    }

    $GLOBALS['course_copy_notice_html'] .= $html;
}

/**
 * Consume and clear current notices.
 */
function course_copy_consume_notice(): string
{
    $notice = $GLOBALS['course_copy_notice_html'] ?? '';
    unset($GLOBALS['course_copy_notice_html']);

    return is_string($notice) ? $notice : '';
}

/**
 * Page container to keep a consistent max-width (professional layout).
 */
function course_copy_page_open(): string
{
    return '<div class="mx-auto w-full space-y-6">';
}

function course_copy_page_close(): string
{
    return '</div>';
}

/**
 * Action bar
 */
function course_copy_actions_bar(): string
{
    $backUrl = api_get_path(WEB_CODE_PATH).'admin/index.php';

    return '
        <div class="flex items-center justify-between">
            <a href="'.$backUrl.'" class="inline-flex items-center gap-2 rounded-md bg-gray-10 px-3 py-2 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-20 focus:outline-none focus:ring-2 focus:ring-gray-30">
                <i class="mdi mdi-arrow-left"></i>
                <span>'.get_lang('Back to').' '.get_lang('Administration').'</span>
            </a>
        </div>
    ';
}

/**
 * Helper to keep selects consistent.
 */
function tw_select_base_classes(): string
{
    return 'w-full rounded-md border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90 shadow-sm '
        .'focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
}

/**
 * Wrap a field + help text consistently to avoid vertical "jumping" after XAJAX updates.
 */
function tw_field_wrapper(string $fieldHtml, string $helpText): string
{
    return '<div class="space-y-1">'
        .$fieldHtml
        .'<p class="text-xs text-gray-50">'.api_htmlentities($helpText, ENT_QUOTES).'</p>'
        .'</div>';
}

course_copy_log('Request received', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'user_id' => api_get_user_id(),
]);

$xajax = new xajax();
$xajax->registerFunction('search_courses');

if (!api_is_allowed_to_edit() && !api_is_session_admin()) {
    course_copy_log('Access denied: user is not allowed to edit and not session admin', ['user_id' => api_get_user_id()]);
    api_not_allowed(true);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$this_section = SECTION_PLATFORM_ADMIN;

$nameTools = get_lang('Copy course');
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];

// Database Table Definitions
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

/**
 * @param string $name
 * @param array  $sessions
 * @param array  $attr
 *
 * @return string
 */
function make_select_session_list($name, $sessions, $attr = [])
{
    $attributes = '';
    if (!empty($attr)) {
        foreach ($attr as $key => $value) {
            $attributes .= ' '.$key.'="'.$value.'"';
        }
    }

    $id = !empty($attr['id']) ? $attr['id'] : $name;
    $class = !empty($attr['class']) ? $attr['class'] : tw_select_base_classes();

    // Ensure we do not duplicate class/id if passed through $attr
    $attributes = preg_replace('/\sclass="[^"]*"/', '', $attributes);
    $attributes = preg_replace('/\sid="[^"]*"/', '', $attributes);

    $output = '<select id="'.htmlspecialchars($id, ENT_QUOTES).'" class="'.htmlspecialchars($class, ENT_QUOTES).'" name="'.$name.'" '.$attributes.'>';

    if (0 === count($sessions)) {
        $output .= '<option value="0">'.get_lang('There are no sessions available').'</option>';
    } else {
        $output .= '<option value="0">'.get_lang('Select a session').'</option>';
    }

    if (is_array($sessions)) {
        foreach ($sessions as $session) {
            $categoryName = '';
            if (!empty($session['category_name'])) {
                $categoryName = ' ('.$session['category_name'].')';
            }

            $output .= '<option value="'.$session['id'].'">'
                .$session['title'].' '.$categoryName
                .'</option>';
        }
    }
    $output .= '</select>';

    return $output;
}

/**
 * @return void
 */
function display_form()
{
    course_copy_log('Rendering form');

    $sessions = SessionManager::get_sessions_list([], ['title', 'ASC']);

    $infoBox = '
        <div class="rounded-md border border-support-3 bg-support-1 p-4 text-sm text-support-4">
            '.get_lang('If you want to copy a course from one session to another course in another session, you must first select a course in the courses list from the original session. You can then choose to copy contents from the course description, documents, glossary, links, tests and learning path tools directly or by selecting the course components.').'
        </div>
    ';

    $html = '';
    $html .= course_copy_page_open();

    // Back
    $html .= course_copy_actions_bar();

    // Notices (below back button)
    $notice = course_copy_consume_notice();
    if (!empty($notice)) {
        $html .= $notice;
    }

    $html .= $infoBox;

    $html .= '<form name="formulaire" method="post" action="'.api_get_self().'" class="space-y-6">';

    // Card: Sessions / Courses
    $html .= '<div class="rounded-lg border border-gray-30 bg-white p-5 shadow-sm">';
    $html .= '  <h2 class="mb-4 text-base font-semibold text-gray-90">'.get_lang('Copy course').'</h2>';

    // Origin row
    $html .= '  <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-start">';
    $html .= '    <div class="md:col-span-3">';
    $html .= '      <label for="sessions_list_origin" class="block text-sm font-medium text-gray-90">'.get_lang('Courses from the original session').'</label>';
    $html .= '    </div>';
    $html .= '    <div class="md:col-span-4">';
    $originSessionSelect = make_select_session_list(
        'sessions_list_origin',
        $sessions,
        [
            'id' => 'sessions_list_origin',
            'onchange' => 'javascript: xajax_search_courses(this.value,\'origin\');',
        ]
    );
    $html .= tw_field_wrapper($originSessionSelect, get_lang('Select a session to load its courses.'));
    $html .= '    </div>';
    $html .= '    <div class="md:col-span-5" id="ajax_list_courses_origin">';
    $originCourseSelect = '<select id="origin" class="'.tw_select_base_classes().'" name="SessionCoursesListOrigin[]"></select>';
    $html .= tw_field_wrapper($originCourseSelect, get_lang('Select the source course.'));
    $html .= '    </div>';
    $html .= '  </div>';

    $html .= '  <div class="my-6 h-px w-full bg-gray-20"></div>';

    // Destination row
    $html .= '  <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-start">';
    $html .= '    <div class="md:col-span-3">';
    $html .= '      <label for="sessions_list_destination" class="block text-sm font-medium text-gray-90">'.get_lang('Courses from the destination session').'</label>';
    $html .= '    </div>';

    $html .= '    <div class="md:col-span-4" id="ajax_sessions_list_destination">';
    $destSessionSelect = '
        <select id="sessions_list_destination" class="'.tw_select_base_classes().'" name="sessions_list_destination" onchange="javascript: xajax_search_courses(this.value,\'destination\');">
            <option value="0">'.get_lang('Select a session').'</option>
        </select>
    ';
    $html .= tw_field_wrapper($destSessionSelect, get_lang('Select a destination session.'));
    $html .= '    </div>';

    $html .= '    <div class="md:col-span-5" id="ajax_list_courses_destination">';
    $destCourseSelect = '<select id="destination" class="'.tw_select_base_classes().'" name="SessionCoursesListDestination[]"></select>';
    $html .= tw_field_wrapper($destCourseSelect, get_lang('Select the destination course.'));
    $html .= '    </div>';
    $html .= '  </div>';

    $html .= '</div>'; // card

    // Card: Copy type
    $html .= '<div class="rounded-lg border border-gray-30 bg-white p-5 shadow-sm">';
    $html .= '  <h3 class="mb-3 text-sm font-semibold text-gray-90">'.get_lang('Type of copy').'</h3>';
    $html .= '  <div class="space-y-3">';

    // Radio 1
    $html .= '    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-30 p-3 hover:bg-gray-10">';
    $html .= '      <input type="radio" id="copy_option_1" name="copy_option" value="full_copy" checked="checked" class="mt-1 h-4 w-4">';
    $html .= '      <div>';
    $html .= '        <div class="text-sm font-medium text-gray-90">'.get_lang('Full copy').'</div>';
    $html .= '        <div class="text-xs text-gray-50">'.get_lang('Copy the full course content from the selected session course.').'</div>';
    $html .= '      </div>';
    $html .= '    </label>';

    // Radio 2 (enabled)
    $html .= '    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-30 p-3 hover:bg-gray-10">';
    $html .= '      <input type="radio" id="copy_option_2" name="copy_option" value="select_items" class="mt-1 h-4 w-4">';
    $html .= '      <div>';
    $html .= '        <div class="text-sm font-medium text-gray-90">'.get_lang('Let me select learning objects').'</div>';
    $html .= '        <div class="text-xs text-gray-50">'.get_lang('Select which tools/items to copy.').'</div>';
    $html .= '      </div>';
    $html .= '    </label>';

    // Checkbox
    $html .= '    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-gray-30 p-3 hover:bg-gray-10">';
    $html .= '      <input type="checkbox" id="copy_base_content_id" name="copy_only_session_items" class="mt-1 h-4 w-4">';
    $html .= '      <div>';
    $html .= '        <div class="text-sm font-medium text-gray-90">'.get_lang('Copy only session items').'</div>';
    $html .= '        <div class="text-xs text-gray-50">'.get_lang('Do not include the base (non-session) course content.').'</div>';
    $html .= '      </div>';
    $html .= '    </label>';

    $html .= '  </div>';
    $html .= '</div>'; // card

    // Actions
    $confirmText = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));

    // Use CSS vars instead of hardcoded hex (primary palette).
    $primaryBg = 'rgb(var(--color-primary-base, 37 99 235))';
    $primaryText = 'rgb(var(--color-primary-button-text, 255 255 255))';

    $html .= '<div class="flex items-center justify-end gap-3">';
    $html .= '  <input type="hidden" value="'.Security::get_token().'" name="sec_token">';
    $html .= '  <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm text-white font-semibold shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    style="background:'.$primaryBg.';color:'.$primaryText.';"
                    onclick="javascript: if(!confirm(\''.$confirmText.'\')) return false;">';
    $html .= '    <i class="mdi mdi-content-copy"></i>';
    $html .= '    <span>'.get_lang('Copy course').'</span>';
    $html .= '  </button>';
    $html .= '</div>';

    $html .= '</form>';
    $html .= course_copy_page_close();

    echo $html;
}

/**
 * @param int    $id_session
 * @param string $type
 *
 * @return xajaxResponse
 */
function search_courses($id_session, $type)
{
    global $tbl_course, $tbl_session_rel_course;

    $xajax_response = new xajaxResponse();

    if (!empty($type)) {
        $id_session = (int) $id_session;

        course_copy_log('XAJAX search_courses called', [
            'type' => $type,
            'session_id' => $id_session,
        ]);

        if ('origin' === $type) {
            $course_list = SessionManager::get_course_list_by_session_id($id_session);
            $temp_course_list = [];

            course_copy_log('Origin session courses loaded', [
                'session_id' => $id_session,
                'courses_count' => is_array($course_list) ? count($course_list) : 0,
            ]);

            $selectHtml  = '<select id="origin" name="SessionCoursesListOrigin[]" class="'.tw_select_base_classes().'" ';
            $selectHtml .= 'onclick="javascript: checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\');">';

            foreach ($course_list as $course) {
                $temp_course_list[] = "'{$course['code']}'";
                $selectHtml .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'
                    .$course['title'].' ('.$course['visual_code'].')'
                    .'</option>';
            }

            $selectHtml .= '</select>';

            $return = tw_field_wrapper($selectHtml, get_lang('Select the source course.'));

            Session::write('course_list', $temp_course_list);
            Session::write('session_origin', $id_session);

            // Build select for destination sessions (excluding the origin session)
            $sessions = SessionManager::get_sessions_list([], ['title', 'ASC']);
            $destSelectHtml  = '<select id="sessions_list_destination" name="sessions_list_destination" class="'.tw_select_base_classes().'" onchange="javascript: xajax_search_courses(this.value,\'destination\');">';
            $destSelectHtml .= '<option value="0">-- '.get_lang('Select a session').' --</option>';

            foreach ($sessions as $session) {
                if ($id_session == $session['id']) {
                    continue;
                }
                $categoryName = '';
                if (!empty($session['category_name'])) {
                    $categoryName = ' ('.$session['category_name'].')';
                }
                $destSelectHtml .= '<option value="'.$session['id'].'">'.$session['title'].' '.$categoryName.'</option>';
            }

            $destSelectHtml .= '</select>';

            $select_destination = tw_field_wrapper($destSelectHtml, get_lang('Select a destination session.'));
            $xajax_response->addAssign('ajax_sessions_list_destination', 'innerHTML', api_utf8_encode($select_destination));

            // Reset destination courses
            $emptyDestSelect = '<select id="destination" name="SessionCoursesListDestination[]" class="'.tw_select_base_classes().'"></select>';
            $select_multiple_empty = tw_field_wrapper($emptyDestSelect, get_lang('Select the destination course.'));

            $xajax_response->addAssign('ajax_list_courses_origin', 'innerHTML', api_utf8_encode($return));
            $xajax_response->addAssign('ajax_list_courses_destination', 'innerHTML', api_utf8_encode($select_multiple_empty));
        } else {
            // Destination courses list for selected destination session
            $sql = "SELECT c.code, c.visual_code, c.title, src.session_id
                    FROM $tbl_course c, $tbl_session_rel_course src
                    WHERE src.c_id = c.id
                    AND src.session_id = '".intval($id_session)."'";

            $rs = Database::query($sql);

            $course_list_destination = [];

            $selectHtml  = '<select id="destination" name="SessionCoursesListDestination[]" class="'.tw_select_base_classes().'">';

            while ($course = Database::fetch_array($rs)) {
                $course_list_destination[] = $course['code'];
                $selectHtml .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'
                    .$course['title'].' ('.$course['visual_code'].')'
                    .'</option>';
            }

            $selectHtml .= '</select>';

            $return = tw_field_wrapper($selectHtml, get_lang('Select the destination course.'));

            course_copy_log('Destination session courses loaded', [
                'session_id' => $id_session,
                'courses_count' => count($course_list_destination),
            ]);

            Session::write('course_list_destination', $course_list_destination);

            $xajax_response->addAssign('ajax_list_courses_destination', 'innerHTML', api_utf8_encode($return));
        }
    }

    return $xajax_response;
}
$xajax->processRequests();

/**
 * Improve the selective copy page (CourseSelectForm) without breaking it:
 */
$htmlHeadXtra[] = '
<style>
.course-copy-selective {
  width: 100%;
  margin: 0 auto;
}
.course-copy-selective h2, .course-copy-selective h3, .course-copy-selective h4 {
  color: #333333;
  font-weight: 600;
}
.course-copy-selective .alert {
  border-radius: 8px;
  margin-bottom: 12px;
}
.course-copy-selective input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: rgb(var(--color-primary-base, 37 99 235));
}
.course-copy-selective input[type="submit"],
.course-copy-selective button,
.course-copy-selective input[type="button"] {
  border-radius: 8px !important;
  padding: 8px 14px !important;
  font-weight: 600 !important;
}
.course-copy-selective input[type="submit"] {
  background: rgb(var(--color-primary-base, 37 99 235)) !important;
  color: rgb(var(--color-primary-button-text, 255 255 255)) !important;
  border: 1px solid rgba(0,0,0,0.08) !important;
}
.course-copy-selective input[type="submit"]:hover {
  opacity: 0.92;
}
.course-copy-selective .btn,
.course-copy-selective .btn-default,
.course-copy-selective .btn-secondary {
  background: #fafafa !important;
  border: 1px solid rgba(0,0,0,0.12) !important;
  color: #333333 !important;
}
.course-copy-selective .btn:hover,
.course-copy-selective .btn-default:hover,
.course-copy-selective .btn-secondary:hover {
  background: #edf0f2 !important;
}
</style>
';

/* HTML head extra */
$htmlHeadXtra[] = $xajax->getJavascript(api_get_path(WEB_LIBRARY_PATH).'xajax/');
$htmlHeadXtra[] = '<script>
function checkSelected(id_select,id_radio,id_title,id_destination) {
    var obj_origin = document.getElementById(id_select);
    var obj_destination = document.getElementById(id_destination);

    if (!obj_origin || !obj_destination) {
        return;
    }

    for (var x = 0; x < obj_origin.options.length; x++) {
        if (obj_origin.options[x].selected) {
            if (obj_destination.options.length > 0) {
                for (var y = 0; y < obj_destination.options.length; y++) {
                    if (obj_origin.options[x].value == obj_destination.options[y].value) {
                        obj_destination.options[y].selected = true;
                    }
                }
            }
        } else {
            if (obj_destination.options.length > 0) {
                for (var y2 = 0; y2 < obj_destination.options.length; y2++) {
                    if (obj_origin.options[x].value == obj_destination.options[y2].value) {
                        obj_destination.options[y2].selected = false;
                    }
                }
            }
        }
    }
}
</script>';

Display::display_header($nameTools);

$with_base_content = true;
if (isset($_POST['copy_only_session_items']) && $_POST['copy_only_session_items']) {
    $with_base_content = false;
}

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && !Security::check_token('post')) {
    course_copy_log('POST received but CSRF token is invalid or missing', [
        'post_keys' => array_keys($_POST),
    ]);
}

/* MAIN CODE */
if (Security::check_token('post') && (
        ('course_select_form' === $action) || (
            isset($_POST['copy_option']) &&
            'full_copy' == $_POST['copy_option']
        )
    )
) {
    Security::clear_token();

    course_copy_log('Copy flow triggered', [
        'action' => $action,
        'with_base_content' => $with_base_content,
    ]);

    $destination_course = $origin_course = $destination_session = $origin_session = '';
    if ('course_select_form' === $action) {
        $destination_course = $_POST['destination_course'] ?? '';
        $origin_course = $_POST['origin_course'] ?? '';
        $destination_session = $_POST['destination_session'] ?? '';
        $origin_session = $_POST['origin_session'] ?? '';

        course_copy_log('CourseSelectForm submit received', [
            'origin_course' => $origin_course,
            'destination_course' => $destination_course,
            'origin_session' => $origin_session,
            'destination_session' => $destination_session,
        ]);

        $sameTarget = ($destination_course === $origin_course) && ((int) $destination_session === (int) $origin_session);

        if (!$sameTarget && !empty($destination_course) && !empty($origin_course) && !empty($destination_session) && !empty($origin_session)) {
            try {
                // This may trigger CourseBuilder internally; fix for the warning is provided separately (see patch).
                $course = CourseSelectForm::get_posted_course(
                    'copy_course',
                    $origin_session,
                    $origin_course
                );

                $cr = new CourseRestorer($course);
                $cr->restore($destination_course, $destination_session);

                course_copy_push_notice(Display::return_message(get_lang('Copying is finished'), 'success'));
                display_form();
            } catch (\Throwable $e) {
                course_copy_log('Restore failed (CourseSelectForm)', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                error_log($e->getTraceAsString());

                course_copy_push_notice(Display::return_message(get_lang('An error occurred while copying the course'), 'error'));
                display_form();
            }
        } else {
            course_copy_push_notice(Display::return_message(get_lang('Please select a different destination'), 'warning'));
            display_form();
        }
    } else {
        $arr_course_origin = [];
        $arr_course_destination = [];
        $destination_session = '';
        $origin_session = '';

        if (isset($_POST['SessionCoursesListOrigin'])) {
            $arr_course_origin = $_POST['SessionCoursesListOrigin'];
        }
        if (isset($_POST['SessionCoursesListDestination'])) {
            $arr_course_destination = $_POST['SessionCoursesListDestination'];
        }
        if (isset($_POST['sessions_list_destination'])) {
            $destination_session = $_POST['sessions_list_destination'];
        }
        if (isset($_POST['sessions_list_origin'])) {
            $origin_session = $_POST['sessions_list_origin'];
        }

        if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
            if (count($arr_course_origin) > 1 || count($arr_course_destination) > 1) {
                course_copy_push_notice(Display::return_message(get_lang('You must select a course from the original session and select a destination session'), 'error'));
                display_form();
            } else {
                $course_code = $arr_course_origin[0] ?? '';
                $course_destinatination = $arr_course_destination[0] ?? '';
                $sameTarget = ($course_code === $course_destinatination) && ((int) $origin_session === (int) $destination_session);

                if (!empty($course_code) && !empty($course_destinatination) && !$sameTarget) {
                    try {
                        $course_origin = api_get_course_info($course_code);
                        $cb = new CourseBuilder('', $course_origin);
                        $course = $cb->build($origin_session, $course_code, $with_base_content);

                        $cr = new CourseRestorer($course);
                        $cr->restore($course_destinatination, $destination_session);

                        course_copy_push_notice(Display::return_message(get_lang('Copying is finished'), 'success'));
                        display_form();
                    } catch (\Throwable $e) {
                        course_copy_log('Restore failed (Full copy)', [
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                        ]);
                        error_log($e->getTraceAsString());

                        course_copy_push_notice(Display::return_message(get_lang('An error occurred while copying the course'), 'error'));
                        display_form();
                    }
                } else {
                    course_copy_push_notice(Display::return_message(get_lang('Please select a different destination'), 'warning'));
                    display_form();
                }
            }
        } else {
            course_copy_push_notice(Display::return_message(get_lang('You must select a course from the original session and select a destination session'), 'error'));
            display_form();
        }
    }
} elseif (Security::check_token('post') && (
        isset($_POST['copy_option']) &&
        'select_items' == $_POST['copy_option']
    )
) {
    Security::clear_token();

    course_copy_log('Selective copy flow triggered (select_items)', [
        'with_base_content' => $with_base_content,
    ]);

    // Notices for selective copy page (displayed below the back button).
    if ('none' != api_get_setting('show_glossary_in_documents')) {
        course_copy_push_notice(Display::return_message(
            get_lang('To export a document that has glossary terms with its references to the glossary, you have to make sure you include the glossary tool in the export'),
            'info'
        ));
    }

    $arr_course_origin = $_POST['SessionCoursesListOrigin'] ?? [];
    $arr_course_destination = $_POST['SessionCoursesListDestination'] ?? [];
    $destination_session = $_POST['sessions_list_destination'] ?? '';
    $origin_session = $_POST['sessions_list_origin'] ?? '';

    $originCourseCode = is_array($arr_course_origin) ? ($arr_course_origin[0] ?? '') : '';
    $destinationCourseCode = is_array($arr_course_destination) ? ($arr_course_destination[0] ?? '') : '';

    $sameTarget = ($originCourseCode === $destinationCourseCode)
        && ((int) $origin_session === (int) $destination_session);

    // Validate required inputs for selective copy
    if (empty($originCourseCode) || empty($origin_session) || empty($destination_session) || empty($destinationCourseCode)) {
        course_copy_push_notice(Display::return_message(
            get_lang('You must select a course from the original session and select a destination session'),
            'error'
        ));
        course_copy_push_notice(Display::return_message(
            get_lang('You must select a destination course'),
            'error'
        ));
        display_form();
    } elseif ($sameTarget) {
        course_copy_push_notice(Display::return_message(get_lang('Please select a different destination'), 'warning'));
        display_form();
    } else {
        course_copy_push_notice(Display::return_message(
            get_lang('If you want to export a course containing a test, you have to make sure the corresponding tests are included in the export, so you have to select them in the list of tests.'),
            'info'
        ));

        echo course_copy_page_open();
        echo course_copy_actions_bar();

        $notice = course_copy_consume_notice();
        if (!empty($notice)) {
            echo $notice;
        }

        // Wrap CourseSelectForm output for scoped styles
        echo '<div class="course-copy-selective">';

        $course_origin = api_get_course_info($originCourseCode);
        $cb = new CourseBuilder('', $course_origin);
        $course = $cb->build($origin_session, $originCourseCode, $with_base_content);
        $hiddenFields['destination_course'] = $destinationCourseCode;
        $hiddenFields['origin_course'] = $originCourseCode;
        $hiddenFields['destination_session'] = $destination_session;
        $hiddenFields['origin_session'] = $origin_session;
        $hiddenFields['copy_only_session_items'] = !empty($_POST['copy_only_session_items']) ? '1' : '0';
        $hiddenFields['sec_token'] = Security::get_token();

        CourseSelectForm::display_form($course, $hiddenFields, true);

        echo '</div>'; // .course-copy-selective

        echo '<div class="mt-4 text-right">
            <a href="javascript:window.history.go(-1);" class="inline-flex items-center gap-2 rounded-md bg-gray-10 px-3 py-2 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-20 focus:outline-none focus:ring-2 focus:ring-gray-30">
                <i class="mdi mdi-arrow-left"></i>
                <span>'.get_lang('Back').' '.get_lang('To').' '.get_lang('Administration').'</span>
            </a>
          </div>';

        echo course_copy_page_close();
    }
} else {
    display_form();
}

Display::display_footer();
