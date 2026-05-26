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

    private static function twBtnPrimary(): string
    {
        return 'inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/20';
    }

    private static function twBtnPrimaryStyle(): string
    {
        return 'background: rgb(var(--color-primary-base, 37 99 235)); color: rgb(var(--color-primary-button-text, 255 255 255));';
    }

    private static function twCheckbox(): string
    {
        return 'h-4 w-4 rounded border-gray-30 text-primary focus:ring-primary/20';
    }

    private static function twItemRow(): string
    {
        return 'rounded-md border border-gray-20 bg-white px-3 py-2 hover:bg-gray-10';
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
     * Compatibility helper for PHP < 8 (avoid fatal if str_ends_with() is not available).
     */
    private static function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $len = strlen($needle);
        if ($len > strlen($haystack)) {
            return false;
        }

        return substr($haystack, -$len) === $needle;
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

        if ($s[0] !== '/') {
            $s = '/'.$s;
        }

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

            if (self::$docCourseCode !== '' && $course === self::$docCourseCode) {
                $rest = array_slice($parts, 2);
                $s = '/'.implode('/', $rest);
            } elseif ($looksLikeHost && self::$docCourseCode !== '' && $course === self::$docCourseCode) {
                $rest = array_slice($parts, 2);
                $s = '/'.implode('/', $rest);
            }
        }

        $s = preg_replace('#/+#', '/', $s) ?: $s;

        return '/'.ltrim($s, '/');
    }

    /**
     * Try to extract a stable numeric document id from the resource object.
     */
    private static function extractDocumentNumericId($resource, $fallbackKey): ?int
    {
        if (is_int($fallbackKey) || (is_string($fallbackKey) && ctype_digit($fallbackKey))) {
            $id = (int) $fallbackKey;
            return $id > 0 ? $id : null;
        }

        foreach (['iid', 'id', 'document_id'] as $prop) {
            if (is_object($resource) && isset($resource->{$prop}) && (is_int($resource->{$prop}) || ctype_digit((string) $resource->{$prop}))) {
                $id = (int) $resource->{$prop};
                return $id > 0 ? $id : null;
            }
        }

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

        $label = $path;

        if ($title !== '') {
            $p = rtrim($label, '/');

            if ($p === '' || $p === '/' || $p === '/document' || $p === '/document/document') {
                $label = '/'.$title;
            } else {
                if (!self::endsWith($label, '/'.$title) && !self::endsWith($label, $title)) {
                    $label = rtrim($label, '/').'/'.$title;
                }
            }
        }

        $label = self::normalizeDocumentPathString($label);

        return $label;
    }

    private static function documentDepth(string $normalizedPath): int
    {
        $trim = trim($normalizedPath, '/');
        if ($trim === '') {
            return 0;
        }
        return substr_count($trim, '/');
    }

    private static function safeStr($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, api_get_system_encoding());
    }

    /**
     * Extract a best-effort title from generic resource wrappers (stdClass, arrays, etc.).
     * This is especially useful for RESOURCE_WORK where the objects can be stdClass with ->params.
     */
    private static function extractGenericTitle($resource, $fallbackId = ''): string
    {
        // params['title']
        if (is_object($resource) && isset($resource->params) && is_array($resource->params) && !empty($resource->params['title'])) {
            $t = trim((string) $resource->params['title']);
            if ($t !== '') {
                return $t;
            }
        }

        if (is_array($resource) && !empty($resource['params']['title'])) {
            $t = trim((string) $resource['params']['title']);
            if ($t !== '') {
                return $t;
            }
        }

        // direct common fields
        if (is_object($resource)) {
            foreach (['title', 'name', 'tool', 'path'] as $prop) {
                if (isset($resource->{$prop}) && (string) $resource->{$prop} !== '') {
                    return trim((string) $resource->{$prop});
                }
            }
        }

        if (is_array($resource)) {
            foreach (['title', 'name', 'tool', 'path'] as $k) {
                if (!empty($resource[$k])) {
                    return trim((string) $resource[$k]);
                }
            }
        }

        // fallback id
        $fallbackId = trim((string) $fallbackId);
        if ($fallbackId !== '') {
            return $fallbackId;
        }

        return get_lang('Untitled');
    }

    /**
     * Render a consistent label block for the selection list.
     */
    private static function renderLabelHtml(int $type, $resource, $id): string
    {
        // Documents: show basename + parent path in monospace.
        if ($type === RESOURCE_DOCUMENT && $resource instanceof Document) {
            $full = self::normalizeDocumentLabel($resource);
            $full = $full !== '' ? $full : '/';

            $base = basename($full);
            $dir = dirname($full);

            // If it is a folder-like path ending with '/', basename() can be empty.
            if ($base === '' || $base === '/' || $base === '.') {
                $base = trim($full, '/');
                if ($base === '') {
                    $base = '/';
                }
                $dir = '';
            } else {
                if ($dir === '/' || $dir === '.') {
                    $dir = '';
                }
            }

            $depth = self::documentDepth($full);
            $pad = min(7, max(0, $depth)) * 14; // px
            $padStyle = 'style="padding-left: '.$pad.'px"';

            $html = '<div class="flex min-w-0 flex-col" '.$padStyle.'>';
            $html .= '  <span class="truncate text-sm font-medium text-gray-90">'.self::safeStr($base).'</span>';
            if ($dir !== '') {
                $html .= '  <span class="truncate text-xs font-mono text-gray-50">'.self::safeStr($dir).'</span>';
            }
            $html .= '</div>';

            return $html;
        }

        // Works: stdClass wrapper -> use params['title'] when possible.
        if ($type === RESOURCE_WORK) {
            $t = self::extractGenericTitle($resource, (string) $id);

            $html = '<div class="flex min-w-0 flex-col">';
            $html .= '  <span class="truncate text-sm font-medium text-gray-90">'.self::safeStr($t).'</span>';

            // Optional: show deadline hints if present.
            $expires = null;
            if (is_object($resource) && isset($resource->params) && is_array($resource->params) && !empty($resource->params['expires_on'])) {
                $expires = (string) $resource->params['expires_on'];
            } elseif (is_array($resource) && !empty($resource['params']['expires_on'])) {
                $expires = (string) $resource['params']['expires_on'];
            }

            if ($expires) {
                $html .= '  <span class="truncate text-xs text-gray-50">'.self::safeStr(get_lang('Deadline')).': '.self::safeStr($expires).'</span>';
            }

            $html .= '</div>';

            return $html;
        }

        // If resource has show(), capture it (it often echoes HTML).
        if (is_object($resource) && method_exists($resource, 'show')) {
            ob_start();
            $resource->show();
            $out = trim((string) ob_get_clean());

            if ($out !== '') {
                return '<div class="min-w-0 truncate text-sm text-gray-90">'.$out.'</div>';
            }
        }

        // Generic fallback.
        $label = self::extractGenericTitle($resource, (string) $id);

        return '<div class="min-w-0 truncate text-sm text-gray-90">'.self::safeStr($label).'</div>';
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
            if (is_int($k) || (is_string($k) && ctype_digit($k))) {
                $id = (int) $k;
                if ($id > 0) {
                    $normalized[$id] = $v;
                }
                continue;
            }

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
     */
    public static function display_form(
        $course,
        $hidden_fields = null,
        $avoidSerialize = false,
        $avoidCourseInForm = false
    ) {
        global $charset;

        global $forum_categories, $forums, $forum_topics;
        $forum_categories = [];
        $forums = [];
        $forum_topics = [];

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
            function normalizeKey(key) {
                return String(key).trim().replace(/[^A-Za-z0-9_-]/g, '_');
            }

            function exp(item) {
                var safe = normalizeKey(item);

                var el = document.getElementById('div_' + safe);
                var icon = document.getElementById('img_' + safe);

                if (!el) {
                    return;
                }

                var isHidden = (el.style.display === 'none');
                el.style.display = isHidden ? '' : 'none';

                if (icon) {
                    icon.className = isHidden ? 'fa fa-minus-square-o fa-lg' : 'fa fa-plus-square-o fa-lg';
                }
            }

            function setCheckboxForum(type, value, item_id) {
                var d = document.forms['course_select_form'];
                if (!d) {
                    return;
                }

                var elems = d.elements;
                for (var i = 0; i < elems.length; i++) {
                    var el = elems[i];

                    if (!el || el.type !== 'checkbox') {
                        continue;
                    }

                    var name = el.getAttribute('name') || '';
                    if (name.indexOf(type) > 0 || type === 'all') {
                        var rel = el.getAttribute('rel');
                        if (String(rel) === String(item_id)) {
                            el.checked = !!value;
                        }
                    }
                }
            }

            function setCheckbox(type, value) {
                var d = document.forms['course_select_form'];
                if (!d) {
                    return;
                }

                var elems = d.elements;
                for (var i = 0; i < elems.length; i++) {
                    var el = elems[i];

                    if (!el || el.type !== 'checkbox') {
                        continue;
                    }

                    var name = el.getAttribute('name') || '';
                    if (name.indexOf(type) > 0 || type === 'all') {
                        el.checked = !!value;
                    }
                }
            }

            function checkLearnPath(message) {
                var d = document.forms['course_select_form'];
                if (!d) {
                    return;
                }

                var elems = d.elements;
                for (var i = 0; i < elems.length; i++) {
                    var el = elems[i];

                    if (!el || el.type !== 'checkbox') {
                        continue;
                    }

                    var name = el.getAttribute('name') || '';
                    if (name.indexOf('learnpath') > 0 && el.checked) {
                        setCheckbox('document', true);
                        alert(message);
                        break;
                    }
                }
            }

            function check_forum(obj) {
                if (!obj) {
                    return;
                }

                var id = obj.getAttribute('rel');
                var my_id = obj.getAttribute('my_rel');

                var forumCheckbox = document.getElementById('resource_forum_' + my_id);
                var checked = forumCheckbox ? forumCheckbox.checked === true : false;

                setCheckboxForum('thread', checked, my_id);

                var catCheckbox = document.getElementById('resource_Forum_Category_' + id);
                if (catCheckbox) {
                    catCheckbox.checked = true;
                }
            }

            function check_category(obj) {
                if (!obj) {
                    return;
                }

                var my_id = obj.getAttribute('my_rel');
                var catCheckbox = document.getElementById('resource_Forum_Category_' + my_id);
                var checked = catCheckbox ? catCheckbox.checked === true : false;

                var forums = document.querySelectorAll('.resource_forum');
                for (var i = 0; i < forums.length; i++) {
                    var f = forums[i];
                    if (String(f.getAttribute('rel')) === String(my_id)) {
                        f.checked = checked;
                    }
                }

                var topics = document.querySelectorAll('.resource_topic');
                for (var j = 0; j < topics.length; j++) {
                    var t = topics[j];
                    if (String(t.getAttribute('cat_id')) === String(my_id)) {
                        t.checked = checked;
                    }
                }
            }

            function check_topic(obj) {
                if (!obj) {
                    return;
                }

                var my_id = obj.getAttribute('cat_id');
                var forum_id = obj.getAttribute('forum_id');

                var catCheckbox = document.getElementById('resource_Forum_Category_' + my_id);
                if (catCheckbox) {
                    catCheckbox.checked = true;
                }

                var forumCheckbox = document.getElementById('resource_forum_' + forum_id);
                if (forumCheckbox) {
                    forumCheckbox.checked = true;
                }
            }
        </script>
        <?php

        echo '<div class="space-y-4">';

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

        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo '<input type="hidden" name="'.htmlspecialchars((string) $key, ENT_QUOTES, api_get_system_encoding()).'" value="'.htmlspecialchars((string) $value, ENT_QUOTES, api_get_system_encoding()).'"/>';
            }
        }

        $resource_titles = self::getResourceTitleList();

        $resourcesArray = (isset($course->resources) && is_array($course->resources)) ? $course->resources : [];
        $element_count = self::parseResources($resource_titles, $resourcesArray, true, true);

        self::trace('UI: parseResources() completed.', [
            'elementCount' => (int) $element_count,
            'avoidSerialize' => (bool) $avoidSerialize,
        ]);

        // Forums special ordering (kept for legacy behavior).
        global $forum_categories, $forums, $forum_topics;

        if (!empty($forum_categories)) {
            $type = RESOURCE_FORUMCATEGORY;

            $typeDom = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $type);

            echo '<div class="'.self::twCard('p-0').'">';
            echo '  <div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$type'".');">';
            echo '    <div class="flex items-center gap-2">';
            echo '      <em id="img_'.$typeDom.'" class="fa fa-minus-square-o fa-lg"></em>';
            echo '      <span class="text-sm font-semibold text-gray-90">'.$resource_titles[RESOURCE_FORUM].'</span>';
            echo '    </div>';
            echo '  </div>';

            echo '  <div class="p-4" id="div_'.$typeDom.'">';
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
                if (is_object($forum_category) && method_exists($forum_category, 'show')) {
                    $forum_category->show();
                } else {
                    $title = (string) ($forum_category->cat_title ?? $forum_category->title ?? $forum_category->name ?? '');
                    echo api_htmlentities($title !== '' ? $title : get_lang('Untitled'), ENT_QUOTES);
                }
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
                        if (is_object($forum) && method_exists($forum, 'show')) {
                            $forum->show();
                        } else {
                            $title = (string) ($forum->cat_title ?? $forum->title ?? $forum->name ?? '');
                            echo api_htmlentities($title !== '' ? $title : get_lang('Untitled'), ENT_QUOTES);
                        }
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
                                    if (is_object($topic) && method_exists($topic, 'show')) {
                                        $topic->show();
                                    } else {
                                        $tTitle = (string) ($topic->title ?? $topic->name ?? '');
                                        echo api_htmlentities($tTitle !== '' ? $tTitle : get_lang('Untitled'), ENT_QUOTES);
                                    }
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
            if (isset($course->resources) && is_array($course->resources)) {
                $course->resources[RESOURCE_DOCUMENT] = null;
                self::trace('UI: documents bucket nulled before serializing course snapshot.');
            }
        }

        if (false === $avoidCourseInForm) {
            $courseSerialized = base64_encode(Course::serialize($course));
            echo '<input type="hidden" name="course" value="'.$courseSerialized.'"/>';
        }

        $recycleOption = isset($_POST['recycle_option']) ? true : false;
        if (empty($element_count)) {
            echo '<div class="mt-4">'.Display::return_message(get_lang('No data available'), 'warning').'</div>';
        } else {
            $confirm = addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES));

            echo '<div class="mt-4 flex items-center justify-end gap-2">';

            if (!empty($hidden_fields['destination_session'])) {
                echo '<button
                        type="submit"
                        class="'.self::twBtnPrimary().'"
                        style="'.self::twBtnPrimaryStyle().'"
                        onclick="javascript:if(!confirm(\''.$confirm.'\')) return false;">'
                    .get_lang('Validate')
                    .'</button>';
            } else {
                if ($recycleOption) {
                    echo '<button
                            type="submit"
                            class="'.self::twBtnPrimary().'"
                            style="'.self::twBtnPrimaryStyle().'">'
                        .get_lang('Validate')
                        .'</button>';
                } else {
                    echo '<button
                            type="submit"
                            class="'.self::twBtnPrimary().'"
                            style="'.self::twBtnPrimaryStyle().'"
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
        echo '</div>';
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

        $domKey = static function ($raw): string {
            return preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $raw);
        };

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
                        $catId = null;
                        if (is_object($resource) && isset($resource->obj) && is_object($resource->obj) && isset($resource->obj->forum_category)) {
                            $catId = $resource->obj->forum_category;
                        }
                        if ($catId === null) {
                            self::trace('parseResources(): forum missing forum_category, skipping.', ['forumId' => $id]);
                            continue;
                        }
                        $forums[$catId][$id] = $resource;
                    }
                    $element_count++;
                    break;

                case RESOURCE_FORUMTOPIC:
                    foreach ($resources as $id => $resource) {
                        $forumId = null;
                        if (is_object($resource) && isset($resource->obj) && is_object($resource->obj) && isset($resource->obj->forum_id)) {
                            $forumId = $resource->obj->forum_id;
                        }
                        if ($forumId === null) {
                            self::trace('parseResources(): topic missing forum_id, skipping.', ['topicId' => $id]);
                            continue;
                        }
                        $forum_topics[$forumId][$id] = $resource;
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
                    $typeDom = $domKey($type);

                    if ($showHeader) {
                        $title = isset($resource_titles[$type]) ? $resource_titles[$type] : (string) $type;
                        $count = is_array($resources) ? count($resources) : 0;

                        echo '<div class="'.self::twCard('p-0').'">';
                        echo '  <div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$type'".');">';
                        echo '    <div class="flex items-center gap-2">';
                        echo '      <em id="img_'.$typeDom.'" class="fa fa-plus-square-o fa-lg"></em>';
                        echo '      <span class="text-sm font-semibold text-gray-90">'.$title.'</span>';
                        echo '      <span class="ml-2 inline-flex items-center rounded-full bg-gray-20 px-2 py-0.5 text-xs font-semibold text-gray-70">'.$count.'</span>';
                        echo '    </div>';
                        echo '  </div>';
                        echo '  <div class="p-4" id="div_'.$typeDom.'" style="display:none;">';
                    }

                    if (RESOURCE_LEARNPATH == $type) {
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('To export courses with quiz you have to select quiz'),
                                'warning'
                            ).'</div>';
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('If your LPs have audio files included you should select them from the documents'),
                                'warning'
                            ).'</div>';
                    }

                    if (RESOURCE_QUIZ == $type) {
                        echo '<div class="mb-3">'.Display::return_message(
                                get_lang('If your quiz have hotspot questions included you should select the images from the documents'),
                                'warning'
                            ).'</div>';
                    }

                    if ($showItems) {
                        echo '<div class="mb-3 flex items-center justify-end gap-2">';
                        echo '  <button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$type.'\',true);">'.get_lang('All').'</button>';
                        echo '  <button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$type.'\',false);">'.get_lang('None').'</button>';
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
                            } else {
                                $inputKey = (string) $id;
                            }

                            $inputKeyStr = (string) $inputKey;
                            $inputKeyDom = $domKey($inputKeyStr);

                            echo '<li class="'.self::twItemRow().'">';
                            echo '  <label class="flex items-start gap-3">';
                            echo '    <input
                                    type="checkbox"
                                    class="'.self::twCheckbox().' mt-0.5"
                                    name="resource['.$type.']['.$inputKeyStr.']"
                                    id="resource_'.$typeDom.'_'.$inputKeyDom.'" />';
                            echo '    <div class="min-w-0 flex-1">';
                            echo          self::renderLabelHtml((int) $type, $resource, $id);
                            echo '    </div>';
                            echo '  </label>';
                            echo '</li>';
                        }

                        echo '</ul>';
                    }

                    if ($showHeader) {
                        echo '  </div>';
                        echo '</div>';

                        echo '<script type="text/javascript">exp('."'$type'".')</script>';
                    }

                    $element_count++;
                    break;
            }
        }

        return $element_count;
    }

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

        $selCounts = [];
        foreach ($postResource as $t => $ids) {
            $selCounts[$t] = is_array($ids) ? count($ids) : 0;
        }
        self::trace('get_posted_course(): selection counts.', $selCounts);

        if (isset($postResource[RESOURCE_DOCUMENT]) && is_array($postResource[RESOURCE_DOCUMENT]) && !empty($postResource[RESOURCE_DOCUMENT])) {
            $beforeKeys = array_slice(array_keys($postResource[RESOURCE_DOCUMENT]), 0, 10);

            $postResource[RESOURCE_DOCUMENT] = self::normalizePostedDocumentSelection($postResource[RESOURCE_DOCUMENT], $course_info);
            $_POST['resource'][RESOURCE_DOCUMENT] = $postResource[RESOURCE_DOCUMENT];

            $afterKeys = array_slice(array_keys($postResource[RESOURCE_DOCUMENT]), 0, 10);

            self::trace('get_posted_course(): document selection normalized.', [
                'beforeSampleKeys' => $beforeKeys,
                'afterSampleKeys' => $afterKeys,
                'afterCount' => count($postResource[RESOURCE_DOCUMENT]),
            ]);
        }

        $typesToExport = array_keys($postResource);

        self::trace('get_posted_course(): start build.', [
            'from' => (string) $from,
            'session_id' => (int) $session_id,
            'course_code' => (string) $course_code,
            'typesToExport' => $typesToExport,
        ]);

        $withBaseContent = false;
        if (array_key_exists('copy_only_session_items', $_POST)) {
            $copyOnlySessionItemsRaw = $_POST['copy_only_session_items'] ?? '';
            $copyOnlySessionItems = in_array((string) $copyOnlySessionItemsRaw, ['1', 'on', 'true'], true);

            $withBaseContent = !$copyOnlySessionItems;

            self::trace('get_posted_course(): base content flag resolved.', [
                'copy_only_session_items' => (string) $copyOnlySessionItemsRaw,
                'withBaseContent' => (bool) $withBaseContent,
            ]);
        }

        $cb = new CourseBuilder('partial', $course_info);
        $course = $cb->build((int) $session_id, $course_code, $withBaseContent, $typesToExport, $postResource);

        if (empty($course) || !isset($course->resources) || !is_array($course->resources)) {
            self::trace('get_posted_course(): builder returned empty course/resources.');

            if ($postedCourse instanceof Course && isset($postedCourse->resources) && is_array($postedCourse->resources)) {
                self::trace('get_posted_course(): falling back to postedCourse snapshot.');
                $course = $postedCourse;
            } else {
                return false;
            }
        }

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
            function normalizeKey(key) {
                return String(key).trim().replace(/[^A-Za-z0-9_-]/g, '_');
            }

            function exp(item) {
                var safe = normalizeKey(item);

                var el = document.getElementById('div_' + safe);
                if (!el) {
                    return;
                }

                if (el.style.display === 'none') {
                    el.style.display = '';
                    var img = document.getElementById('img_' + safe);
                    if (img) {
                        img.className = 'fa fa-minus-square-o fa-lg';
                    }
                } else {
                    el.style.display = 'none';
                    var img2 = document.getElementById('img_' + safe);
                    if (img2) {
                        img2.className = 'fa fa-plus-square-o fa-lg';
                    }
                }
            }

            function setCheckbox(type, value) {
                var d = document.course_select_form;
                if (!d) {
                    return;
                }

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
                if (!d) {
                    return;
                }

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

        echo '<div class="tool-backups-options">';
        echo '<form method="post" id="upload_form" name="course_select_form">';
        echo '<input type="hidden" name="action" value="course_select_form"/>';

        $lastCourse = null;

        if (is_array($list_course)) {
            foreach ($list_course as $course) {
                $lastCourse = $course;

                if (!isset($course->resources) || !is_array($course->resources)) {
                    continue;
                }

                foreach ($course->resources as $type => $resources) {
                    if (!is_array($resources) || count($resources) === 0) {
                        continue;
                    }

                    $code = (string) ($course->code ?? '');
                    if ($code === '') {
                        $code = 'course';
                    }

                    echo '<div class="'.self::twCard('p-0').'">';
                    echo '<div class="'.self::twSectionHeader().'" onclick="javascript:exp('."'$code'".');">';
                    echo '<em id="img_'.$code.'" class="fa fa-minus-square-o fa-lg"></em>';
                    echo '<span class="text-sm font-semibold text-gray-90"> '.$code.'</span>';
                    echo '</div>';

                    echo '<div class="p-4" id="div_'.$code.'">';
                    echo '<div class="mb-3 flex items-center justify-end gap-2">';
                    echo '<button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$code.'\',true);">'.get_lang('All').'</button>';
                    echo '<button type="button" class="'.self::twBtnNeutral().'" onclick="javascript:setCheckbox(\''.$code.'\',false);">'.get_lang('none').'</button>';
                    echo '</div>';

                    echo '<div class="space-y-2">';
                    foreach ($resources as $id => $resource) {
                        if (!$resource) {
                            continue;
                        }
                        echo '<label class="flex items-start gap-2" for="resource_'.$code.'_'.$id.'">';
                        echo '<input class="'.self::twCheckbox().'" type="checkbox" name="resource['.$code.']['.$id.']" id="resource_'.$code.'_'.$id.'"/>';
                        echo '<span class="text-sm text-gray-90">';
                        if (is_object($resource) && method_exists($resource, 'show')) {
                            $resource->show();
                        } else {
                            echo htmlspecialchars((string) $id, ENT_QUOTES, api_get_system_encoding());
                        }
                        echo '</span>';
                        echo '</label>';
                    }
                    echo '</div>';

                    echo '</div>';
                    echo '</div>';

                    echo '<script type="text/javascript">exp('."'$code'".')</script>';
                }
            }
        }

        if ($avoidSerialize) {
            if ($lastCourse && isset($lastCourse->resources) && is_array($lastCourse->resources)) {
                $lastCourse->resources[RESOURCE_DOCUMENT] = null;
                self::trace('UI: session export form nulled documents bucket before serializing (legacy behavior).');
            }
        }

        if ($lastCourse) {
            echo '<input type="hidden" name="course" value="'.base64_encode(Course::serialize($lastCourse)).'"/>';
        } else {
            echo '<div class="mt-4">'.Display::return_message(get_lang('No data available'), 'warning').'</div>';
        }

        if (is_array($hidden_fields)) {
            foreach ($hidden_fields as $key => $value) {
                echo '<input type="hidden" name="'.htmlspecialchars((string) $key, ENT_QUOTES, api_get_system_encoding()).'" value="'.htmlspecialchars((string) $value, ENT_QUOTES, api_get_system_encoding()).'"/>';
            }
        }

        $btnStyle = 'background: rgb(var(--color-primary-base)); color: rgb(var(--color-primary-button-text));';

        echo '<div class="mt-4 flex items-center justify-end">';
        echo '<button class="inline-flex items-center gap-2 text-white rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                style="'.$btnStyle.'"
                type="submit"
                onclick="checkLearnPath(\''.addslashes(get_lang('Documents will be added too')).'\')">'
            .get_lang('Validate')
            .'</button>';
        echo '</div>';

        if ($lastCourse) {
            self::display_hidden_quiz_questions($lastCourse);
            self::display_hidden_scorm_directories($lastCourse);
        }

        echo '</form>';
        echo '</div>';
        echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';
    }
}
