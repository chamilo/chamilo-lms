<?php
/* For licensing terms, see /license.txt */

class SearchCourseWidget
{
    public const MAX_RESULTS = 50;

    public static function post(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
    }

    public static function get(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function getPluginLang(string $name): string
    {
        return SearchCoursePlugin::create()->get_lang($name);
    }

    public static function factory(): self
    {
        return new self();
    }

    public static function getSearchPageUrl(): string
    {
        return SearchCoursePlugin::create()->getSearchPageUrl();
    }

    public function renderBlock(): string
    {
        if (api_is_anonymous()) {
            return '';
        }

        $content = '<div class="space-y-4" data-search-course-region-block="1">';
        $content .= $this->renderHeader();
        $content .= $this->renderSearchForm();
        $content .= '<p class="text-xs text-gray-50">'.self::escape(self::getPluginLang('SearchCourseRegionHelp')).'</p>';
        $content .= '</div>';

        return $content;
    }

    public function run(): string
    {
        $content = '<div class="space-y-6">';
        $content .= $this->renderHeader();
        $content .= RegisterCourseWidget::factory()->run();
        $content .= $this->renderSearchForm();

        $searchTerm = self::post('search_term');

        if ('' !== $searchTerm) {
            $courses = $this->retrieveCourses($searchTerm);
            $content .= $this->renderResults($courses, $searchTerm);
        }

        $content .= '</div>';

        return $content;
    }

    public function renderHeader(): string
    {
        if (api_is_anonymous()) {
            return '';
        }
        return '
            <section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'
                    .self::escape(self::getPluginLang('CourseCatalog')).'
                </p>
                <h2 class="mt-1 text-2xl font-bold text-gray-90">'
                    .self::escape(self::getPluginLang('Search courses')).'
                </h2>
                <p class="mt-2 text-sm text-gray-50">'
                    .self::escape(self::getPluginLang('SearchCourseIntro')).'
                </p>
            </section>';
    }

    public function renderSearchForm(): string
    {
        global $stok;

        $searchTerm = self::escape(self::post('search_term'));
        $token = self::escape((string) $stok);

        return '
            <section class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
                <form class="flex flex-col gap-3 md:flex-row md:items-end" method="post" action="'.self::escape(self::getSearchPageUrl()).'">
                    <input type="hidden" name="sec_token" value="'.$token.'">
                    <input type="hidden" name="search_course" value="1">
                    <label class="flex flex-1 flex-col gap-1 text-sm">
                        <span class="font-semibold text-gray-70">'.self::escape(get_lang('Search')).'</span>
                        <input
                            class="w-full rounded-lg border border-gray-25 px-3 py-2 text-sm"
                            type="text"
                            name="search_term"
                            value="'.$searchTerm.'"
                            placeholder="'.self::escape(self::getPluginLang('SearchByCourseTitleCodeOrTeacher')).'"
                        >
                    </label>
                    <button class="btn btn--primary" type="submit">
                        <span class="mdi mdi-magnify ch-tool-icon" aria-hidden="true"></span>
                        '.self::escape(get_lang('Search')).'
                    </button>
                </form>
            </section>';
    }

    public function renderResults(array $courses, string $searchTerm): string
    {
        $searchTermHtml = self::escape($searchTerm);
        $count = count($courses);

        $content = '
            <section class="rounded-2xl border border-gray-20 bg-white shadow-sm">
                <div class="border-b border-gray-20 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'
                        .self::escape(self::getPluginLang('SearchResults')).'
                    </p>
                    <h3 class="mt-1 text-xl font-bold text-gray-90">'
                        .sprintf(
                            self::escape(self::getPluginLang('Search results for:')),
                            $searchTermHtml
                        ).'
                    </h3>
                    <p class="mt-1 text-sm text-gray-50">'
                        .sprintf(self::escape(self::getPluginLang('CoursesFound')), $count).'
                    </p>
                </div>';

        if (empty($courses)) {
            return $content.'
                <div class="p-6 text-center text-sm text-gray-50">'
                    .self::escape(get_lang('No data available')).'
                </div>
            </section>';
        }

        $content .= '
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-gray-10 text-xs uppercase text-gray-50">
                        <tr>
                            <th class="px-4 py-3">'.self::escape(get_lang('Course')).'</th>
                            <th class="px-4 py-3">'.self::escape(get_lang('Teacher')).'</th>
                            <th class="px-4 py-3">'.self::escape(get_lang('Visibility')).'</th>
                            <th class="px-4 py-3 text-right">'.self::escape(get_lang('Actions')).'</th>
                        </tr>
                    </thead>
                    <tbody>';

        $userCourses = api_is_anonymous() ? [] : $this->retrieveUserCourses();

        foreach ($courses as $course) {
            $content .= $this->renderCourseRow($course, $userCourses);
        }

        $content .= '
                    </tbody>
                </table>
            </div>
        </section>';

        return $content;
    }

    public function renderCourseRow(array $course, array $userCourses): string
    {
        $courseCode = (string) ($course['code'] ?? '');
        $courseId = (int) ($course['id'] ?? 0);
        $title = (string) ($course['title'] ?? '');
        $visualCode = (string) ($course['visual_code'] ?? '');
        $tutor = (string) ($course['tutor'] ?? '');
        $visibility = (int) ($course['visibility'] ?? 0);
        $courseUrl = api_get_path(WEB_PATH).'course/'.$courseId.'/home?sid=0';

        $details = [];

        if ('true' === api_get_setting('display_coursecode_in_courselist') && '' !== $visualCode) {
            $details[] = self::escape($visualCode);
        }

        $detailsHtml = empty($details) ? '' : '<p class="mt-1 text-xs text-gray-50">'.implode(' · ', $details).'</p>';

        return '
            <tr class="border-t border-gray-15">
                <td class="px-4 py-3">
                    <a class="font-semibold text-blue-700 hover:underline" href="'.self::escape($courseUrl).'">'
                        .self::escape($title).'
                    </a>'
                    .$detailsHtml.'
                </td>
                <td class="px-4 py-3 text-gray-70">'.self::escape($tutor).'</td>
                <td class="px-4 py-3">'.$this->renderVisibilityBadge($visibility).'</td>
                <td class="px-4 py-3 text-right">'.$this->renderActions($course, $userCourses).'</td>
            </tr>';
    }

    public function renderVisibilityBadge(int $visibility): string
    {
        $label = match ($visibility) {
            COURSE_VISIBILITY_OPEN_WORLD => self::getPluginLang('Public'),
            COURSE_VISIBILITY_OPEN_PLATFORM => self::getPluginLang('PlatformUsers'),
            COURSE_VISIBILITY_REGISTERED => self::getPluginLang('RegistrationAllowed'),
            default => get_lang('Private'),
        };

        $class = match ($visibility) {
            COURSE_VISIBILITY_OPEN_WORLD => 'bg-green-100 text-green-700',
            COURSE_VISIBILITY_OPEN_PLATFORM => 'bg-blue-100 text-blue-700',
            COURSE_VISIBILITY_REGISTERED => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-700',
        };

        return '<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold '.$class.'">'.self::escape($label).'</span>';
    }

    public function renderActions(array $course, array $userCourses): string
    {
        global $stok;

        $courseCode = (string) ($course['code'] ?? '');
        $courseId = (int) ($course['id'] ?? 0);
        $courseUrl = api_get_path(WEB_PATH).'course/'.$courseId.'/home?sid=0';

        $viewLink = '
            <a href="'.self::escape($courseUrl).'" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-blue-100 bg-white text-blue-700 hover:bg-blue-50" title="'.self::escape(get_lang('View')).'">
                <span class="mdi mdi-eye ch-tool-icon" aria-hidden="true"></span>
                <span class="sr-only">'.self::escape(get_lang('View')).'</span>
            </a>';

        if (api_is_anonymous()) {
            return '<div class="flex justify-end gap-2">'.$viewLink.'</div>';
        }

        if (isset($userCourses[$courseCode])) {
            return '<div class="flex justify-end gap-2">'.$viewLink.'<span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">'.self::escape(self::getPluginLang('Already subscribed')).'</span></div>';
        }

        if (SUBSCRIBE_ALLOWED !== (int) ($course['subscribe'] ?? 0)) {
            return '<div class="flex justify-end gap-2">'.$viewLink.'<span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">'.self::escape(self::getPluginLang('Subscribing not allowed')).'</span></div>';
        }

        $token = self::escape((string) $stok);

        $subscribeForm = '
            <form method="post" action="'.self::escape(self::getSearchPageUrl()).'" class="inline-flex">
                <input type="hidden" name="sec_token" value="'.$token.'">
                <input type="hidden" name="action" value="'.RegisterCourseWidget::ACTION_SUBSCRIBE.'">
                <input type="hidden" name="'.RegisterCourseWidget::PARAM_SUBSCRIBE.'" value="'.self::escape($courseCode).'">
                <input type="hidden" name="search_course" value="1">
                <input type="hidden" name="search_term" value="'.self::escape(self::post('search_term')).'">
                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-green-100 bg-white text-green-700 hover:bg-green-50" title="'.self::escape(get_lang('Subscribe')).'">
                    <span class="mdi mdi-account-plus ch-tool-icon" aria-hidden="true"></span>
                    <span class="sr-only">'.self::escape(get_lang('Subscribe')).'</span>
                </button>
            </form>';

        return '<div class="flex justify-end gap-2">'.$viewLink.$subscribeForm.'</div>';
    }

    public function retrieveCourses(string $searchTerm): array
    {
        if ('' === trim($searchTerm)) {
            return [];
        }

        $searchTerm = Database::escape_string($searchTerm);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $whereVisibility = $this->getVisibilityWhereClause();

        $sql = "
            SELECT
                id,
                code,
                visual_code,
                title,
                tutor_name,
                subscribe,
                unsubscribe,
                registration_code,
                visibility
            FROM $courseTable
            WHERE ($whereVisibility)
              AND (
                    code LIKE '%$searchTerm%'
                 OR visual_code LIKE '%$searchTerm%'
                 OR title LIKE '%$searchTerm%'
                 OR tutor_name LIKE '%$searchTerm%'
              )
            ORDER BY title ASC, visual_code ASC
            LIMIT ".self::MAX_RESULTS;

        $result = [];
        $resultSet = Database::query($sql);

        while ($row = Database::fetch_assoc($resultSet)) {
            $code = (string) $row['code'];
            $result[$code] = [
                'id' => (int) $row['id'],
                'code' => $code,
                'visual_code' => (string) $row['visual_code'],
                'title' => (string) $row['title'],
                'tutor' => (string) $row['tutor_name'],
                'subscribe' => (int) $row['subscribe'],
                'unsubscribe' => (int) $row['unsubscribe'],
                'registration_code' => (string) $row['registration_code'],
                'visibility' => (int) $row['visibility'],
            ];
        }

        return $result;
    }

    public function retrieveUserCourses(?int $userId = null): array
    {
        if (null === $userId) {
            $userId = api_get_user_id();
        }

        if (empty($userId)) {
            return [];
        }

        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $userCourseTable = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $userId = (int) $userId;

        $sql = "
            SELECT
                course.code,
                course.visual_code,
                course.title,
                course.tutor_name,
                course.directory,
                course.subscribe,
                course.unsubscribe,
                course_rel_user.status,
                course_rel_user.sort,
                course_rel_user.user_course_cat
            FROM $courseTable course
            INNER JOIN $userCourseTable course_rel_user ON course.id = course_rel_user.c_id
            WHERE course_rel_user.user_id = $userId
            ORDER BY course_rel_user.sort ASC";

        $result = [];
        $resultSet = Database::query($sql);

        while ($row = Database::fetch_assoc($resultSet)) {
            $code = (string) $row['code'];
            $result[$code] = [
                'code' => $code,
                'visual_code' => (string) $row['visual_code'],
                'title' => (string) $row['title'],
                'directory' => (string) $row['directory'],
                'status' => (int) $row['status'],
                'tutor' => (string) $row['tutor_name'],
                'subscribe' => (int) $row['subscribe'],
                'unsubscribe' => (int) $row['unsubscribe'],
                'sort' => (int) $row['sort'],
                'user_course_category' => (int) $row['user_course_cat'],
            ];
        }

        return $result;
    }

    private function getVisibilityWhereClause(): string
    {
        if (api_is_anonymous()) {
            return 'visibility = '.COURSE_VISIBILITY_OPEN_WORLD;
        }

        return implode(
            ' OR ',
            [
                'visibility = '.COURSE_VISIBILITY_OPEN_WORLD,
                'visibility = '.COURSE_VISIBILITY_OPEN_PLATFORM,
                '(visibility = '.COURSE_VISIBILITY_REGISTERED.' AND subscribe = '.SUBSCRIBE_ALLOWED.')',
            ]
        );
    }
}
