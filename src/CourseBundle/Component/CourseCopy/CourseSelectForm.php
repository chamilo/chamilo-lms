<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Resource;
use Database;
use Display;

/**
 * Class to show a form to select resources.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CourseSelectForm
{
    /** @var string */
    private static $docCourseCode = '';

    /** @var int */
    private static $docCourseRealId = 0;

    /**
     * Small Tailwind helpers to keep UI consistent.
     */
    private static function twCard(string $extra = ''): string
    {
        return trim('rounded-lg border border-gray-30 bg-white p-4 shadow-sm '.$extra);
    }

    private static function twSectionHeader(): string
    {
        return 'flex items-center justify-between gap-3 rounded-md border border-gray-30 bg-gray-10 px-3 py-2 hover:bg-gray-15 cursor-pointer select-none';
    }

    private static function twBtnNeutral(): string
    {
        return 'inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10 focus:outline-none focus:ring-2 focus:ring-primary/20';
    }

    private static function twCheckbox(): string
    {
        return 'h-4 w-4 rounded border-gray-30 text-primary focus:ring-primary/20';
    }

    private static function traceEnabled(): bool
    {
        return defined('COURSE_COPY_TRACE_ENABLED') && true === constant('COURSE_COPY_TRACE_ENABLED');
    }

    /**
     * Lightweight tracing to PHP error log.
     * Enable by defining COURSE_COPY_TRACE_ENABLED = true.
     */
    private static function trace(string $message, array $context = []): void
    {
        if (!self::traceEnabled()) {
            return;
        }

        $ctx = '';
        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $ctx = $json ? ' '.$json : '';
        }

        error_log('[COURSE_COPY] '.$message.$ctx);
    }

    /**
     * Normalize a raw document path-ish string into a course-relative path.
     *
     * Examples:
     * - "/document/localhost/CS001/H002" => "/H002"
     * - "/document/document/localhost/CS001/file.pdf" => "/file.pdf"
     * - "/document/H001" => "/H001"
     */
    private static function normalizeDocumentPathString(string $raw): string
    {
        $s = str_replace('\\', '/', $raw);
        $s = preg_replace('#/+#', '/', $s) ?: $s;

        if ($s === '') {
            return '/';
        }

        // Ensure leading slash
        if ($s[0] !== '/') {
            $s = '/'.$s;
        }

        // Remove any repeated "/document" prefixes
        // e.g. "/document/document/localhost/CS001/..." => "/localhost/CS001/..."
        while (preg_match('#^/document(/|$)#', $s) === 1) {
            $s = substr($s, strlen('/document'));
            if ($s === '') {
                $s = '/';
                break;
            }
            if ($s[0] !== '/') {
                $s = '/'.$s;
            }
        }

        $s = preg_replace('#/+#', '/', $s) ?: $s;

        // Remove "/{host}/{courseCode}/" when it clearly matches
        $trim = trim($s, '/');
        if ($trim === '') {
            return '/';
        }

        $parts = explode('/', $trim);
        if (count($parts) >= 3) {
            $host = $parts[0];
            $course = $parts[1];

            $looksLikeHost = ($host === 'localhost')
                || (false !== strpos($host, '.'))
                || (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host) === 1);

            if ((self::$docCourseCode !== '' && $course === self::$docCourseCode) || $looksLikeHost) {
                $rest = array_slice($parts, 2);
                $s = '/'.implode('/', $rest);
            }
        }

        $s = preg_replace('#/+#', '/', $s) ?: $s;

        // Always return a rooted path
        return '/'.ltrim($s, '/');
    }

    /**
     * Try to extract a stable numeric document id from the resource object.
     */
    private static function extractDocumentNumericId($resource, $fallbackKey): ?int
    {
        // Already numeric key
        if (is_int($fallbackKey) || (is_string($fallbackKey) && ctype_digit($fallbackKey))) {
            $id = (int) $fallbackKey;
            return $id > 0 ? $id : null;
        }

        // Direct properties
        foreach (['iid', 'id', 'document_id'] as $prop) {
            if (is_object($resource) && isset($resource->{$prop}) && (is_int($resource->{$prop}) || ctype_digit((string) $resource->{$prop}))) {
                $id = (int) $resource->{$prop};
                return $id > 0 ? $id : null;
            }
        }

        // Under ->obj
        if (is_object($resource) && isset($resource->obj)) {
            $obj = $resource->obj;

            if (is_object($obj)) {
                if (method_exists($obj, 'getIid')) {
                    $id = (int) $obj->getIid();
                    return $id > 0 ? $id : null;
                }
                if (method_exists($obj, 'getId')) {
                    $id = (int) $obj->getId();
                    return $id > 0 ? $id : null;
                }
                foreach (['iid', 'id'] as $prop) {
                    if (isset($obj->{$prop}) && (is_int($obj->{$prop}) || ctype_digit((string) $obj->{$prop}))) {
                        $id = (int) $obj->{$prop};
                        return $id > 0 ? $id : null;
                    }
                }
            }

            if (is_array($obj)) {
                foreach (['iid', 'id'] as $prop) {
                    if (isset($obj[$prop]) && (is_int($obj[$prop]) || ctype_digit((string) $obj[$prop]))) {
                        $id = (int) $obj[$prop];
                        return $id > 0 ? $id : null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Normalize legacy-like document paths for UI display (course-relative).
     */
    private static function normalizeDocumentLabel(Document $doc): string
    {
        $path = (string) ($doc->path ?? '');
        $title = trim((string) ($doc->title ?? ''));

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: $path;

        // Build a label candidate
        $label = $path;

        if ($title !== '') {
            $p = rtrim($label, '/');

            // If it's a pseudo root bucket, display title as folder
            if ($p === '' || $p === '/' || $p === '/document' || $p === '/document/document') {
                $label = '/'.$title;
            } else {
                // Otherwise, append title if not already present
                if (!str_ends_with($label, '/'.$title) && !str_ends_with($label, $title)) {
                    $label = rtrim($label, '/').'/'.$title;
                }
            }
        }

        $label = self::normalizeDocumentPathString($label);

        return $label;
    }

    /**
     * Try to resolve a document iid/id by course and normalized path.
     */
    private static function resolveDocumentIdByPath(int $courseRealId, string $normalizedPath): ?int
    {
        if ($courseRealId <= 0) {
            return null;
        }

        $p1 = $normalizedPath;
        $p2 = ltrim($normalizedPath, '/');

        // Try with leading slash, then without
        $pathsToTry = array_values(array_unique([$p1, $p2]));

        foreach ($pathsToTry as $p) {
            if ($p === '') {
                continue;
            }

            $pEsc = Database::escape_string($p);
            $sql = "SELECT iid, id
                    FROM c_document
                    WHERE c_id = ".(int) $courseRealId."
                      AND path = '".$pEsc."'
                    LIMIT 1";
            $res = Database::query($sql);
            if ($res) {
                $row = Database::fetch_array($res, 'ASSOC');
                if (!empty($row)) {
                    if (isset($row['iid']) && ctype_digit((string) $row['iid']) && (int) $row['iid'] > 0) {
                        return (int) $row['iid'];
                    }
                    if (isset($row['id']) && ctype_digit((string) $row['id']) && (int) $row['id'] > 0) {
                        return (int) $row['id'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Normalize posted document selection keys to numeric ids (iid/id),
     * because CourseBuilder filtering commonly casts keys to int.
     */
    private static function normalizePostedDocumentSelection(array $selection, array $courseInfo): array
    {
        $courseRealId = (int) ($courseInfo['real_id'] ?? 0);
        if ($courseRealId <= 0 || empty($selection)) {
            return $selection;
        }

        $normalized = [];

        foreach ($selection as $k => $v) {
            // Keep numeric keys
            if (is_int($k) || (is_string($k) && ctype_digit($k))) {
                $id = (int) $k;
                if ($id > 0) {
                    $normalized[$id] = $v;
                }
                continue;
            }

            // Try to resolve path-like keys
            $rawKey = (string) $k;
            $path = self::normalizeDocumentPathString($rawKey);
            $id = self::resolveDocumentIdByPath($courseRealId, $path);

            if ($id) {
                $normalized[$id] = $v;
                self::trace('POST: document key resolved to numeric id.', [
                    'rawKey' => $rawKey,
                    'path' => $path,
                    'id' => $id,
                ]);
            } else {
                self::trace('POST: unable to resolve document key to numeric id.', [
                    'rawKey' => $rawKey,
                    'path' => $path,
                ]);
            }
        }

        return $normalized;
    }

    /**
     * @return array
     */
    public static function getResourceTitleList()
    {
        $list = [];
        $list[RESOURCE_LEARNPATH_CATEGORY] = get_lang('Courses').' '.get_lang('Category');
        $list[RESOURCE_ASSET] = get_lang('Assets');
        $list[RESOURCE_GRADEBOOK] = get_lang('Assessments');
        $list[RESOURCE_EVENT] = get_lang('Events');
        $list[RESOURCE_ANNOUNCEMENT] = get_lang('Announcements');
        $list[RESOURCE_DOCUMENT] = get_lang('Documents');
        $list[RESOURCE_LINK] = get_lang('Links');
        $list[RESOURCE_COURSEDESCRIPTION] = get_lang('Course Description');
        $list[RESOURCE_FORUM] = get_lang('Forums');
        $list[RESOURCE_FORUMCATEGORY] = get_lang('Forum category');
        $list[RESOURCE_QUIZ] = get_lang('Tests');
        $list[RESOURCE_TEST_CATEGORY] = get_lang('Questions category');
        $list[RESOURCE_LEARNPATH] = get_lang('Learning path');
        $list[RESOURCE_LEARNPATH_CATEGORY] = get_lang('Learning path categories');
        $list[RESOURCE_SCORM] = 'SCORM';
        $list[RESOURCE_TOOL_INTRO] = get_lang('Tool introduction');
        $list[RESOURCE_SURVEY] = get_lang('Survey');
        $list[RESOURCE_GLOSSARY] = get_lang('Glossary');
        $list[RESOURCE_WIKI] = get_lang('Group wiki');
        $list[RESOURCE_THEMATIC] = get_lang('Thematic');
        $list[RESOURCE_ATTENDANCE] = get_lang('Attendance');
        $list[RESOURCE_WORK] = get_lang('Assignments');

        return $list;
    }

    /**
     * Display the form.
     *
     * @param array $course
     * @param array $hidden_fields     hidden fields to add to the form
     * @param bool  $avoidSerialize    the document array will be serialize.
     *                                 This is used in the course_copy.php file
     * @param bool  $avoidCourseInForm
     */
    public static function display_form(
        $course,
        $hidden_fields = null,
        $avoidSerialize = false,
        $avoidCourseInForm = false
    ) {
        global $charset;

        // These need to be global because parseResources() fills them.
        global $forum_categories, $forums, $forum_topics;
        $forum_categories = [];
        $forums = [];
        $forum_topics = [];

        // Cache course metadata for document normalization
        self::$docCourseCode = isset($course->code) ? (string) $course->code : '';
        self::$docCourseRealId = 0;
        if (isset($course->info) && is_array($course->info) && isset($course->info['real_id'])) {
            self::$docCourseRealId = (int) $course->info['real_id'];
        }

        self::trace('UI: display_form() start.', [
            'courseCode' => self::$docCourseCode,
            'courseRealId' => self::$docCourseRealId,
            'avoidSerialize' => (bool) $avoidSerialize,
            'avoidCourseInForm' => (bool) $avoidCourseInForm,
            'hasResources' => isset($course->resources) && is_array($course->resources),
        ]);

        ?>
        <script>
            function exp(item) {
                var el = document.getElementById('div_' + item);
                if (!el) {
                    return;
                }

                if (el.style.display === 'none') {
                    el.style.display = '';
                    $('#img_' + item).removeClass().addClass('fa fa-minus-square-o fa-lg');
                } else {
                    el.style.display = 'none';
                    $('#img_' + item).removeClass().addClass('fa fa-plus-square-o fa-lg');
                }
            }

            function setCheckboxForum(type, value, item_id) {
                var d = document.course_select_form;
                for (var i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type === "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf(type) > 0 || type === 'all') {
                            if ($(d.elements[i]).attr('rel') == item_id) {
                                d.elements[i].checked = value;
                            }
                        }
                    }
                }
            }

            function setCheckbox(type, value) {
                var d = document.course_select_form;
                for (var i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type === "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf(type) > 0 || type === 'all') {
                            d.elements[i].checked = value;
                        }
                    }
                }
            }

            function checkLearnPath(message) {
                var d = document.course_select_form;
                for (var i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type === "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf('learnpath') > 0) {
                            if (d.elements[i].checked) {
                                setCheckbox('document', true);
                                alert(message);
                                break;
                            }
                        }
                    }
                }
            }

            function check_forum(obj) {
                var id = $(obj).attr('rel');
                var my_id = $(obj).attr('my_rel');
                var checked = $('#resource_forum_' + my_id).prop('checked') === true;

                setCheckboxForum('thread', checked, my_id);
                $('#resource_Forum_Category_' + id).prop('checked', true);
            }

            function check_category(obj) {
                var my_id = $(obj).attr('my_rel');
                var checked = $('#resource_Forum_Category_' + my_id).prop('checked') === true;

                $('.resource_forum').each(function(index, value) {
                    if ($(value).attr('rel') == my_id) {
                        $(value).prop('checked', checked);
                    }
                });

                $('.resource_topic').each(function(index, value) {
                    if ($(value).attr('cat_id') == my_id) {
                        $(value).prop('checked', checked);
                    }
                });
            }

            function check_topic(obj) {
                var my_id = $(obj).attr('cat_id');
                var forum_id = $(obj).attr('forum_id');
                $('#resource_Forum_Category_' + my_id).prop('checked', true);
                $('#resource_forum_' + forum_id).prop('checked', true);
            }
        </script>
        <?php

        echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';

        echo '<div class="space-y-4">';

        // Destination course header
        if (!empty($hidden_fields['destination_course'])) {
            $sessionTitle = !empty($hidden_fields['destination_session'])
                ? ' ('.api_get_session_name($hidden_fields['destination_session']).')'
                : '';

            $courseInfo = api_get_course_info($hidden_fields['destination_course']);

            echo '<div class="'.self::twCard().'">';
            echo '  <div class="flex items-start justify-between gap-3">';
            echo '    <div>';
            echo '      <h2 class="text-lg font-semibold text-gray-90">'.get_lang('Target course').'</h2>';
            echo '      <p class="text-sm text-gray-50">';
            echo            htmlspecialchars($courseInfo['title'].' ('.$courseInfo['code'].')'.$sessionTitle, ENT_QUOTES, api_get_system_encoding());
            echo '      </p>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';
        }

        echo '<div class="'.self::twCard().'">';
        echo '  <p class="text-sm font-medium text-gray-90">'.get_lang('Select resources').'</p>';
        echo '  <div class="mt-2">'.Display::return_message(get_lang('Don\'t forget to select the media files if your resource need it'), 'info').'</div>';
        echo '</div>';

        echo '<div class="tool-backups-options">';
        echo '<form method="post" id="upload_form" name="course_select_form" class="space-y-4">';
        echo '<input type="hidden" name="action" value="course_select_form"/>';

        // Hidden fields (single pass, avoid duplicates)
        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo '<input type="hidden" name="'.htmlspecialchars((string) $key, ENT_QUOTES, api_get_system_encoding()).'" value="'.htmlspecialchars((string) $value, ENT_QUOTES, api_get_system_encoding()).'"/>';
            }
        }

        $resource_titles = self::getResourceTitleList();

        // Main list
        $element_count = self::parseResources($resource_titles, $course->resources, true, true);

        self::trace('UI: parseResources() completed.', [
            'elementCount' => (int) $element_count,
            'avoidSerialize' => (bool) $avoidSerialize,
        ]);

        /**
         * Forums special ordering (kept for legacy behavior).
         * parseResources() fills global arrays ($forum_categories/$forums/$forum_topics).
         */
        if (!empty($forum_categories)) {
            $type = RESOURCE_FORUMCATEGORY;

            echo '<div class="'.self::twCard('p-0').'">';
            echo '  <div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$type'".');">';
            echo '    <div class="flex items-center gap-2">';
            echo '      <em id="img_'.$type.'" class="fa fa-minus-square-o fa-lg"></em>';
            echo '      <span class="text-sm font-semibold text-gray-90">'.$resource_titles[RESOURCE_FORUM].'</span>';
            echo '    </div>';
            echo '  </div>';

            echo '  <div class="p-4" id="div_'.$type.'">';
            echo '    <ul class="space-y-2">';

            foreach ($forum_categories as $forum_category_id => $forum_category) {
                echo '<li class="space-y-2">';
                echo '  <label class="flex items-start gap-2">';
                echo '    <input type="checkbox"
                                class="'.self::twCheckbox().'"
                                id="resource_'.RESOURCE_FORUMCATEGORY.'_'.$forum_category_id.'"
                                my_rel="'.$forum_category_id.'"
                                onclick="javascript:check_category(this);"
                                name="resource['.RESOURCE_FORUMCATEGORY.']['.$forum_category_id.']" /> ';
                echo '    <span class="text-sm text-gray-90">';
                $forum_category->show();
                echo '    </span>';
                echo '  </label>';

                if (isset($forums[$forum_category_id])) {
                    $my_forums = $forums[$forum_category_id];
                    echo '<ul class="ml-6 space-y-2">';

                    foreach ($my_forums as $forum_id => $forum) {
                        echo '<li class="space-y-2">';
                        echo '  <label class="flex items-start gap-2">';
                        echo '    <input type="checkbox"
                                        class="resource_forum '.self::twCheckbox().'"
                                        id="resource_'.RESOURCE_FORUM.'_'.$forum_id.'"
                                        onclick="javascript:check_forum(this);"
                                        my_rel="'.$forum_id.'"
                                        rel="'.$forum_category_id.'"
                                        name="resource['.RESOURCE_FORUM.']['.$forum_id.']" />';
                        echo '    <span class="text-sm text-gray-90">';
                        $forum->show();
                        echo '    </span>';
                        echo '  </label>';

                        if (isset($forum_topics[$forum_id])) {
                            $my_forum_topics = $forum_topics[$forum_id];
                            if (!empty($my_forum_topics)) {
                                echo '<ul class="ml-6 space-y-2">';
                                foreach ($my_forum_topics as $topic_id => $topic) {
                                    echo '<li>';
                                    echo '<label class="flex items-start gap-2">';
                                    echo '  <input
                                                type="checkbox"
                                                class="resource_topic '.self::twCheckbox().'"
                                                id="resource_'.RESOURCE_FORUMTOPIC.'_'.$topic_id.'"
                                                onclick="javascript:check_topic(this);"
                                                forum_id="'.$forum_id.'"
                                                rel="'.$forum_id.'"
                                                cat_id="'.$forum_category_id.'"
                                                name="resource['.RESOURCE_FORUMTOPIC.']['.$topic_id.']" />';
                                    echo '  <span class="text-sm text-gray-90">';
                                    $topic->show();
                                    echo '  </span>';
                                    echo '</label>';
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }

                        echo '</li>';
                    }

                    echo '</ul>';
                }

                echo '<div class="mt-3 h-px w-full bg-gray-20"></div>';
                echo '</li>';
            }

            echo '    </ul>';
            echo '  </div>';
            echo '</div>';
            echo '<script type="text/javascript">exp('."'$type'".')</script>';
        }

        if ($avoidSerialize) {
            /**
             * Documents are avoided due to memory usage when serializing huge folder trees.
             * Known limitation of PHP serialize on very large arrays.
             */
            if (isset($course->resources) && is_array($course->resources)) {
                $course->resources[RESOURCE_DOCUMENT] = null;
                self::trace('UI: documents bucket nulled before serializing course snapshot.');
            }
        }

        if (false === $avoidCourseInForm) {
            /** @var Course $course */
            $courseSerialized = base64_encode(Course::serialize($course));
            echo '<input type="hidden" name="course" value="'.$courseSerialized.'"/>';
        }

        $recycleOption = isset($_POST['recycle_option']) ? true : false;
        if (empty($element_count)) {
            echo '<div class="mt-4">'.Display::return_message(get_lang('No data available'), 'warning').'</div>';
        } else {
            $confirm = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));
            echo '<div class="mt-4 flex items-center justify-end gap-2">';
            $btnStyle = 'background: rgb(var(--color-primary-base)); color: rgb(var(--color-primary-button-text));';
            if (!empty($hidden_fields['destination_session'])) {
                echo '<button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                        style="'.$btnStyle.'"
                        onclick="javascript:if(!confirm(\''.$confirm.'\')) return false;">'
                    .get_lang('Validate')
                    .'</button>';
            } else {
                if ($recycleOption) {
                    echo '<button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                            style="'.$btnStyle.'">'
                        .get_lang('Validate')
                        .'</button>';
                } else {
                    echo '<button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                            style="'.$btnStyle.'"
                            onclick="checkLearnPath(\''.addslashes(get_lang('Documents will be added too')).'\')">'
                        .get_lang('Validate')
                        .'</button>';
                }
            }

            echo '</div>';
        }

        self::display_hidden_quiz_questions($course);
        self::display_hidden_scorm_directories($course);
        echo '</form>';
        echo '</div>';
        echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
        echo '</div>'; // space-y-4 wrapper
    }

    /**
     * @param array $resource_titles
     * @param array $resourceList
     * @param bool  $showHeader
     * @param bool  $showItems
     *
     * @return int
     */
    public static function parseResources(
        $resource_titles,
        $resourceList,
        $showHeader = true,
        $showItems = true
    ) {
        global $forum_categories, $forums, $forum_topics;

        $element_count = 0;

        if (!is_array($resourceList)) {
            return 0;
        }

        foreach ($resourceList as $type => $resources) {
            if (empty($resources) || !is_array($resources) || count($resources) === 0) {
                continue;
            }

            switch ($type) {
                case RESOURCE_FORUMCATEGORY:
                    foreach ($resources as $id => $resource) {
                        $forum_categories[$id] = $resource;
                    }
                    $element_count++;
                    break;

                case RESOURCE_FORUM:
                    foreach ($resources as $id => $resource) {
                        $forums[$resource->obj->forum_category][$id] = $resource;
                    }
                    $element_count++;
                    break;

                case RESOURCE_FORUMTOPIC:
                    foreach ($resources as $id => $resource) {
                        $forum_topics[$resource->obj->forum_id][$id] = $resource;
                    }
                    $element_count++;
                    break;

                // Skip these types in selector UI
                case RESOURCE_LINKCATEGORY:
                case RESOURCE_FORUMPOST:
                case RESOURCE_QUIZQUESTION:
                case RESOURCE_SURVEYQUESTION:
                case RESOURCE_SURVEYINVITATION:
                case RESOURCE_SCORM:
                    break;

                default:
                    if ($showHeader) {
                        $title = isset($resource_titles[$type]) ? $resource_titles[$type] : (string) $type;

                        echo '<div class="'.self::twCard('p-0').'">';
                        echo '  <div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$type'".');">';
                        echo '    <div class="flex items-center gap-2">';
                        echo '      <em id="img_'.$type.'" class="fa fa-plus-square-o fa-lg"></em>';
                        echo '      <span class="text-sm font-semibold text-gray-90">'.$title.'</span>';
                        echo '    </div>';
                        echo '  </div>';
                        echo '  <div class="p-4" id="div_'.$type.'" style="display:none;">';
                    }

                    // Contextual warnings (kept)
                    if (RESOURCE_LEARNPATH == $type) {
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('ToExportCoursesWithQuizYouHaveToSelectQuiz'),
                                'warning'
                            ).'</div>';
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('IfYourLPsHaveAudioFilesIncludedYouShouldSelectThemFromTheDocuments'),
                                'warning'
                            ).'</div>';
                    }

                    if (RESOURCE_QUIZ == $type) {
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('IfYourQuizHaveHotspotQuestionsIncludedYouShouldSelectTheImagesFromTheDocuments'),
                                'warning'
                            ).'</div>';
                    }

                    if ($showItems) {
                        // All / None actions
                        echo '<div class="mb-3 flex items-center justify-end gap-2">';
                        echo '  <button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$type.'\',true);">'.get_lang('All').'</button>';
                        echo '  <button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$type.'\',false);">'.get_lang('none').'</button>';
                        echo '</div>';

                        echo '<ul class="space-y-2">';
                        foreach ($resources as $id => $resource) {
                            if (!$resource) {
                                continue;
                            }

                            // Ensure class type consistency for legacy resources
                            Resource::setClassType($resource);

                            $inputKey = $id;

                            if ($type === RESOURCE_DOCUMENT && $resource instanceof Document) {
                                $numericId = self::extractDocumentNumericId($resource, $id);
                                if ($numericId) {
                                    $inputKey = (string) $numericId;
                                } else {
                                    $inputKey = (string) $id;
                                }
                            }

                            $inputKeyStr = (string) $inputKey;

                            echo '<li>';
                            echo '  <label class="flex items-start gap-2">';
                            echo '    <input
                                        type="checkbox"
                                        class="'.self::twCheckbox().'"
                                        name="resource['.$type.']['.$inputKeyStr.']"
                                        id="resource_'.$type.'_'.$inputKeyStr.'" />';

                            echo '    <span class="text-sm text-gray-90">';

                            if ($type === RESOURCE_DOCUMENT && $resource instanceof Document) {
                                echo htmlspecialchars(self::normalizeDocumentLabel($resource), ENT_QUOTES, api_get_system_encoding());
                            } else {
                                $resource->show();
                            }

                            echo '    </span>';
                            echo '  </label>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    }

                    if ($showHeader) {
                        echo '  </div>'; // div_type
                        echo '</div>'; // card

                        // Default collapsed; keep the behavior consistent with exp()
                        echo '<script type="text/javascript">exp('."'$type'".')</script>';
                    }

                    $element_count++;
                    break;
            }
        }

        return $element_count;
    }

    /**
     * @param $course
     */
    public static function display_hidden_quiz_questions($course)
    {
        if (!isset($course->resources) || !is_array($course->resources)) {
            return;
        }

        foreach ($course->resources as $type => $resources) {
            if (empty($resources) || !is_array($resources)) {
                continue;
            }

            if (RESOURCE_QUIZQUESTION === $type) {
                foreach ($resources as $id => $resource) {
                    echo '<input
                        type="hidden"
                        name="resource['.RESOURCE_QUIZQUESTION.']['.$id.']"
                        id="resource['.RESOURCE_QUIZQUESTION.']['.$id.']" value="On" />';
                }
            }
        }
    }

    /**
     * @param $course
     */
    public static function display_hidden_scorm_directories($course)
    {
        if (!isset($course->resources) || !is_array($course->resources)) {
            return;
        }

        foreach ($course->resources as $type => $resources) {
            if (empty($resources) || !is_array($resources)) {
                continue;
            }

            if (RESOURCE_SCORM === $type) {
                foreach ($resources as $id => $resource) {
                    echo '<input
                        type="hidden"
                        name="resource['.RESOURCE_SCORM.']['.$id.']"
                        id="resource['.RESOURCE_SCORM.']['.$id.']" value="On" />';
                }
            }
        }
    }

    /**
     * Get the posted course (selected resources only).
     * @param string $from
     * @param int    $session_id
     * @param string $course_code
     * @param Course $postedCourse
     *
     * @return Course|false
     */
    public static function get_posted_course(
        $from = '',
        $session_id = 0,
        $course_code = '',
        $postedCourse = null
    ) {
        $postResource = isset($_POST['resource']) && is_array($_POST['resource']) ? $_POST['resource'] : [];
        if (empty($postResource)) {
            self::trace('get_posted_course(): empty selection map.');
            return false;
        }

        if (empty($course_code)) {
            self::trace('get_posted_course(): missing course_code.');
            return false;
        }

        $course_info = api_get_course_info($course_code);
        if (empty($course_info) || empty($course_info['real_id'])) {
            self::trace('get_posted_course(): invalid course_code or missing real_id.', ['course_code' => (string) $course_code]);
            return false;
        }

        // Trace selection counts early
        $selCounts = [];
        foreach ($postResource as $t => $ids) {
            $selCounts[$t] = is_array($ids) ? count($ids) : 0;
        }
        self::trace('get_posted_course(): selection counts.', $selCounts);

        // CRITICAL FIX: normalize document selection keys to numeric ids
        if (isset($postResource[RESOURCE_DOCUMENT]) && is_array($postResource[RESOURCE_DOCUMENT]) && !empty($postResource[RESOURCE_DOCUMENT])) {
            $beforeKeys = array_slice(array_keys($postResource[RESOURCE_DOCUMENT]), 0, 10);

            $postResource[RESOURCE_DOCUMENT] = self::normalizePostedDocumentSelection($postResource[RESOURCE_DOCUMENT], $course_info);

            // Re-write $_POST too, because later filters rely on $_POST selection
            $_POST['resource'][RESOURCE_DOCUMENT] = $postResource[RESOURCE_DOCUMENT];

            $afterKeys = array_slice(array_keys($postResource[RESOURCE_DOCUMENT]), 0, 10);

            self::trace('get_posted_course(): document selection normalized.', [
                'beforeSampleKeys' => $beforeKeys,
                'afterSampleKeys' => $afterKeys,
                'afterCount' => count($postResource[RESOURCE_DOCUMENT]),
            ]);
        }

        // Build only what the user selected (types are keys of the selection map).
        $typesToExport = array_keys($postResource);

        self::trace('get_posted_course(): start build.', [
            'from' => (string) $from,
            'session_id' => (int) $session_id,
            'course_code' => (string) $course_code,
            'typesToExport' => $typesToExport,
        ]);

        $cb = new CourseBuilder('partial', $course_info);
        $course = $cb->build((int) $session_id, $course_code, false, $typesToExport, $postResource);

        if (empty($course) || !isset($course->resources) || !is_array($course->resources)) {
            self::trace('get_posted_course(): builder returned empty course/resources.');

            // Fallback to postedCourse if available (legacy safety)
            if ($postedCourse instanceof Course && isset($postedCourse->resources) && is_array($postedCourse->resources)) {
                self::trace('get_posted_course(): falling back to postedCourse snapshot.');
                $course = $postedCourse;
            } else {
                return false;
            }
        }

        // Trace resource counts by type
        $counts = [];
        foreach ($course->resources as $t => $list) {
            $counts[$t] = is_array($list) ? count($list) : 0;
        }
        self::trace('get_posted_course(): after build resource counts.', $counts);

        return $course;
    }

    /**
     * Display the form session export.
     *
     * @param array $list_course
     * @param array $hidden_fields
     * @param bool  $avoidSerialize
     */
    public static function display_form_session_export(
        $list_course,
        $hidden_fields = null,
        $avoidSerialize = false
    ) {
        ?>
        <script>
            function exp(item) {
                var el = document.getElementById('div_' + item);
                if (!el) {
                    return;
                }
                if (el.style.display === 'none') {
                    el.style.display = '';
                    if (document.getElementById('img_' + item)) {
                        document.getElementById('img_' + item).className = 'fa fa-minus-square-o fa-lg';
                    }
                } else {
                    el.style.display = 'none';
                    if (document.getElementById('img_' + item)) {
                        document.getElementById('img_' + item).className = 'fa fa-plus-square-o fa-lg';
                    }
                }
            }

            function setCheckbox(type, value) {
                var d = document.course_select_form;
                for (var i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type === "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf(type) > 0 || type === 'all') {
                            d.elements[i].checked = value;
                        }
                    }
                }
            }

            function checkLearnPath(message) {
                var d = document.course_select_form;
                for (var i = 0; i < d.elements.length; i++) {
                    if (d.elements[i].type === "checkbox") {
                        var name = d.elements[i].attributes.getNamedItem('name').nodeValue;
                        if (name.indexOf('learnpath') > 0) {
                            if (d.elements[i].checked) {
                                setCheckbox('document', true);
                                alert(message);
                                break;
                            }
                        }
                    }
                }
            }
        </script>
        <?php

        if (!empty($hidden_fields['destination_course'])) {
            $sessionTitle = null;
            if (!empty($hidden_fields['destination_session'])) {
                $sessionTitle = ' ('.api_get_session_name($hidden_fields['destination_session']).')';
            }
            $courseInfo = api_get_course_info($hidden_fields['destination_course']);
            echo '<div class="'.self::twCard().'">';
            echo '<h3 class="text-lg font-semibold text-gray-90">'.get_lang('Target course').' : '
                .htmlspecialchars($courseInfo['title'].$sessionTitle, ENT_QUOTES, api_get_system_encoding())
                .'</h3>';
            echo '</div>';
        }

        echo '<script src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/upload.js" type="text/javascript"></script>';

        echo '<div class="tool-backups-options">';
        echo '<form method="post" id="upload_form" name="course_select_form">';
        echo '<input type="hidden" name="action" value="course_select_form"/>';

        foreach ($list_course as $course) {
            foreach ($course->resources as $type => $resources) {
                if (!is_array($resources) || count($resources) === 0) {
                    continue;
                }

                echo '<div class="'.self::twCard('p-0').'">';
                echo '<div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$course->code'".');">';
                echo '<em id="img_'.$course->code.'" class="fa fa-minus-square-o fa-lg"></em>';
                echo '<span class="text-sm font-semibold text-gray-90"> '.$course->code.'</span>';
                echo '</div>';

                echo '<div class="p-4" id="div_'.$course->code.'">';
                echo '<div class="mb-3 flex items-center justify-end gap-2">';
                echo '<button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$course->code.'\',true);">'.get_lang('All').'</button>';
                echo '<button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$course->code.'\',false);">'.get_lang('none').'</button>';
                echo '</div>';

                echo '<div class="space-y-2">';
                foreach ($resources as $id => $resource) {
                    echo '<label class="flex items-start gap-2" for="resource_'.$course->code.'_'.$id.'">';
                    echo '<input class="'.self::twCheckbox().'" type="checkbox" name="resource['.$course->code.']['.$id.']" id="resource_'.$course->code.'_'.$id.'"/>';
                    echo '<span class="text-sm text-gray-90">';
                    $resource->show();
                    echo '</span>';
                    echo '</label>';
                }
                echo '</div>';

                echo '</div>';
                echo '</div>';

                echo '<script type="text/javascript">exp('."'$course->code'".')</script>';
            }
        }

        if ($avoidSerialize) {
            if (isset($course->resources) && is_array($course->resources)) {
                $course->resources[RESOURCE_DOCUMENT] = null;
                self::trace('UI: session export form nulled documents bucket before serializing (legacy behavior).');
            }
        }

        echo '<input type="hidden" name="course" value="'.base64_encode(Course::serialize($course)).'"/>';

        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo "\n";
                echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            }
        }

        $btnStyle = 'background: rgb(var(--color-primary-base)); color: rgb(var(--color-primary-button-text));';

        echo '<div class="mt-4 flex items-center justify-end">';
        echo '<button class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                style="'.$btnStyle.'"
                type="submit"
                onclick="checkLearnPath(\''.addslashes(get_lang('Documents will be added too')).'\')">'
            .get_lang('Validate')
            .'</button>';
        echo '</div>';

        self::display_hidden_quiz_questions($course);
        self::display_hidden_scorm_directories($course);

        echo '</form>';
        echo '</div>';
        echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
    }
}
