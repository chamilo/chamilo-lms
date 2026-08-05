<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (!api_is_platform_admin() && !api_is_teacher() && !api_is_session_admin()) {
    api_not_allowed(true);
}

if ('true' !== api_get_setting('exercise.my_courses_show_pending_exercise_attempts')) {
    api_not_allowed(true);
}

$userId = api_get_user_id();

$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$userTable = Database::get_main_table(TABLE_MAIN_USER);
$trackExerciseTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$quizTable = Database::get_course_table(TABLE_QUIZ_TEST);
$resourceNodeTable = 'resource_node';
$resourceLinkTable = 'resource_link';
$trackAttemptQualifyTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);

function pending_exercise_h(mixed $value): string
{
    return api_htmlentities((string) $value, ENT_QUOTES);
}

function pending_exercise_is_valid_date(string $date): bool
{
    return 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
}

function pending_exercise_table_has_column(string $table, string $column): bool
{
    $column = Database::escape_string($column);
    $result = Database::query("SHOW COLUMNS FROM $table LIKE '$column'");

    return Database::num_rows($result) > 0;
}

function pending_exercise_setting_is_enabled(string $name): bool
{
    $value = api_get_setting($name);

    return true === $value || 'true' === $value || 1 === $value || '1' === $value;
}

function pending_exercise_get_allowed_course_ids(int $userId, bool $includeSessions): array
{
    if (api_is_platform_admin()) {
        return [];
    }

    $courses = CourseManager::get_courses_list_by_user_id($userId, $includeSessions, false, false);
    $courseIds = [];

    foreach ($courses as $course) {
        $courseId = (int) ($course['real_id'] ?? $course['id'] ?? 0);

        if ($courseId > 0) {
            $courseIds[$courseId] = $courseId;
        }
    }

    return array_values($courseIds);
}

function pending_exercise_get_courses_for_select(int $userId, bool $includeSessions): array
{
    $courses = api_is_platform_admin()
        ? CourseManager::get_courses_list()
        : CourseManager::get_courses_list_by_user_id($userId, $includeSessions, false, false);

    $options = [];

    foreach ($courses as $course) {
        $courseId = (int) ($course['real_id'] ?? $course['id'] ?? 0);

        if ($courseId > 0) {
            $options[$courseId] = (string) ($course['title'] ?? '');
        }
    }

    asort($options);

    return $options;
}

$showAttemptsInSessions = pending_exercise_setting_is_enabled('exercise.show_exercise_attempts_in_all_user_sessions');
$currentSessionId = api_get_session_id();
$allowedCourseIds = pending_exercise_get_allowed_course_ids($userId, $showAttemptsInSessions);
$action = $_GET['a'] ?? null;
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ('get_exercise_by_course' === $action) {
    header('Content-Type: application/json');

    if ($courseId <= 0) {
        echo json_encode([]);
        exit;
    }

    if (!api_is_platform_admin() && !in_array($courseId, $allowedCourseIds, true)) {
        echo json_encode([]);
        exit;
    }

    $sql = "
        SELECT DISTINCT q.iid, q.title
        FROM $quizTable q
        INNER JOIN $resourceNodeTable rn
            ON rn.id = q.resource_node_id
        INNER JOIN $resourceLinkTable rl
            ON rl.resource_node_id = rn.id
        WHERE rl.c_id = $courseId
            AND rl.deleted_at IS NULL
        ORDER BY q.title ASC
    ";

    $result = Database::query($sql);
    $items = [];

    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $items[] = [
            'id' => (int) $row['iid'],
            'text' => html_entity_decode((string) $row['title']),
        ];
    }

    echo json_encode($items);
    exit;
}

$filterUserId = isset($_GET['filter_by_user']) ? (int) $_GET['filter_by_user'] : 0;
$exerciseId = isset($_GET['exercise_id']) ? (int) $_GET['exercise_id'] : 0;
$statusId = isset($_GET['status']) ? (int) $_GET['status'] : 3;
$questionTypeId = isset($_GET['questionTypeId']) ? (int) $_GET['questionTypeId'] : 0;
$startDate = isset($_GET['start_date']) ? trim((string) $_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim((string) $_GET['end_date']) : '';
$exportCsv = isset($_GET['export_csv']) && '1' === (string) $_GET['export_csv'];

if (!pending_exercise_is_valid_date($startDate)) {
    $startDate = '';
}

if (!pending_exercise_is_valid_date($endDate)) {
    $endDate = '';
}

$hasScore = pending_exercise_table_has_column($trackExerciseTable, 'score');
$hasExeResult = pending_exercise_table_has_column($trackExerciseTable, 'exe_result');
$hasMaxScore = pending_exercise_table_has_column($trackExerciseTable, 'max_score');
$hasExeWeighting = pending_exercise_table_has_column($trackExerciseTable, 'exe_weighting');
$hasUserIp = pending_exercise_table_has_column($trackExerciseTable, 'user_ip');
$hasDuration = pending_exercise_table_has_column($trackExerciseTable, 'exe_duration');
$hasOfficialCode = pending_exercise_table_has_column($userTable, 'official_code');

$scoreSelect = $hasScore ? 'te.score' : ($hasExeResult ? 'te.exe_result' : '0');
$maxScoreSelect = $hasMaxScore ? 'te.max_score' : ($hasExeWeighting ? 'te.exe_weighting' : '0');
$durationSelect = $hasDuration ? 'te.exe_duration' : '0';
$userIpSelect = $hasUserIp ? 'te.user_ip' : "''";
$officialCodeSelect = $hasOfficialCode ? 'u.official_code' : "''";

$revisedSelect = "
    CASE
        WHEN te.status = 'incomplete' THEN -1
        WHEN EXISTS (
            SELECT 1
            FROM $trackAttemptQualifyTable taq
            WHERE taq.exe_id = te.exe_id
              AND taq.author > 0
            LIMIT 1
        ) THEN 1
        ELSE 0
    END
";

$qualifierSelect = "
    COALESCE((
        SELECT CONCAT(author.firstname, ' ', author.lastname)
        FROM $trackAttemptQualifyTable taq
        INNER JOIN $userTable author
            ON author.id = taq.author
        WHERE taq.exe_id = te.exe_id
          AND taq.author > 0
        ORDER BY taq.insert_date DESC
        LIMIT 1
    ), '')
";

$qualificationDateSelect = "
    COALESCE((
        SELECT taq.insert_date
        FROM $trackAttemptQualifyTable taq
        WHERE taq.exe_id = te.exe_id
          AND taq.author > 0
        ORDER BY taq.insert_date DESC
        LIMIT 1
    ), '')
";

$officialCodeInList = pending_exercise_setting_is_enabled('exercise.show_official_code_exercise_result_list');
$showUsername = pending_exercise_setting_is_enabled('exercise.exercise_attempts_report_show_username');

$where = [];

if (!api_is_platform_admin()) {
    $where[] = empty($allowedCourseIds)
        ? '1 = 0'
        : 'te.c_id IN ('.implode(',', array_map('intval', $allowedCourseIds)).')';
}

if (!$showAttemptsInSessions) {
    if ($currentSessionId > 0) {
        $where[] = 'te.session_id = '.(int) $currentSessionId;
    } else {
        $where[] = '(te.session_id IS NULL OR te.session_id = 0)';
    }
}

if ($courseId > 0) {
    $where[] = 'te.c_id = '.$courseId;
}

if ($exerciseId > 0) {
    $where[] = 'te.exe_exo_id = '.$exerciseId;
}

if ($filterUserId > 0) {
    $where[] = 'te.exe_user_id = '.$filterUserId;
}

if ('' !== $startDate) {
    $where[] = "te.start_date >= '".Database::escape_string($startDate)." 00:00:00'";
}

if ('' !== $endDate) {
    $where[] = "te.exe_date <= '".Database::escape_string($endDate)." 23:59:59'";
}

switch ($statusId) {
    case 2:
        $where[] = "EXISTS (
            SELECT 1
            FROM $trackAttemptQualifyTable taq
            WHERE taq.exe_id = te.exe_id
              AND taq.author > 0
            LIMIT 1
        )";
        break;

    case 3:
        $where[] = "te.status <> 'incomplete'";
        $where[] = "NOT EXISTS (
            SELECT 1
            FROM $trackAttemptQualifyTable taq
            WHERE taq.exe_id = te.exe_id
              AND taq.author > 0
            LIMIT 1
        )";
        break;

    case 4:
        $where[] = "te.status = 'incomplete'";
        break;

    case 5:
        $where[] = "(te.exe_date IS NULL OR te.exe_date = '0000-00-00 00:00:00')";
        break;
}

if (1 === $questionTypeId) {
    $where[] = "te.status = 'incomplete'";
}

$whereSql = empty($where) ? '1 = 1' : implode("\n AND ", $where);

$sql = "
    SELECT DISTINCT
        c.id AS course_id,
        c.code AS course_code,
        c.title AS course_title,
        q.iid AS exercise_id,
        q.title AS exercise_title,
        te.exe_id AS attempt_id,
        te.exe_user_id AS user_id,
        te.session_id AS session_id,
        $scoreSelect AS score,
        $maxScoreSelect AS max_score,
        te.start_date AS start_date,
        te.exe_date AS end_date,
        te.status AS attempt_status,
        $durationSelect AS duration,
        $userIpSelect AS user_ip,
        $revisedSelect AS revised,
        $qualificationDateSelect AS date_of_qualification,
        $qualifierSelect AS qualificator_fullname,
        $officialCodeSelect AS official_code,
        u.firstname,
        u.lastname,
        u.username
    FROM $trackExerciseTable te
    INNER JOIN $quizTable q
        ON q.iid = te.exe_exo_id
    INNER JOIN $resourceNodeTable rn
        ON rn.id = q.resource_node_id
    INNER JOIN $resourceLinkTable rl
        ON rl.resource_node_id = rn.id
    INNER JOIN $courseTable c
        ON c.id = te.c_id
    INNER JOIN $userTable u
        ON u.id = te.exe_user_id
    WHERE $whereSql
        AND rl.c_id = te.c_id
        AND rl.deleted_at IS NULL
    ORDER BY c.title ASC, q.title ASC, te.exe_date DESC
    LIMIT 1000
";

$result = Database::query($sql);
$rows = [];

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $rows[] = $row;
}

if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pending_exercise_attempts.csv"');

    $output = fopen('php://output', 'w');

    $headers = [
        get_lang('Course'),
        get_lang('Exercise'),
    ];

    if ($officialCodeInList) {
        $headers[] = get_lang('OfficialCode');
    }

    $headers[] = get_lang('FirstName');
    $headers[] = get_lang('LastName');

    if ($showUsername) {
        $headers[] = get_lang('LoginName');
    }

    $headers[] = get_lang('Duration').' ('.get_lang('MinMinute').')';
    $headers[] = get_lang('StartDate');
    $headers[] = get_lang('EndDate');
    $headers[] = get_lang('Score');
    $headers[] = get_lang('IP');
    $headers[] = get_lang('Status');
    $headers[] = get_lang('Corrector');
    $headers[] = get_lang('CorrectionDate');

    fputcsv($output, $headers);

    foreach ($rows as $row) {
        $line = [
            $row['course_title'],
            $row['exercise_title'],
        ];

        if ($officialCodeInList) {
            $line[] = $row['official_code'];
        }

        $line[] = $row['firstname'];
        $line[] = $row['lastname'];

        if ($showUsername) {
            $line[] = $row['username'];
        }

        $line[] = round(((int) $row['duration']) / 60, 2);
        $line[] = $row['start_date'];
        $line[] = $row['end_date'];
        $line[] = $row['score'].' / '.$row['max_score'];
        $line[] = $row['user_ip'];
        $line[] = ((int) $row['revised']) === 1 ? get_lang('Validated') : get_lang('NotValidated');
        $line[] = $row['qualificator_fullname'];
        $line[] = $row['date_of_qualification'];

        fputcsv($output, $line);
    }

    fclose($output);
    exit;
}

$courses = pending_exercise_get_courses_for_select($userId, $showAttemptsInSessions);

Display::display_header(get_lang('PendingAttempts'));
?>
    <div class="space-y-6">
        <section class="rounded-lg border border-gray-25 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-90">
                        <?php echo pending_exercise_h(get_lang('Pending tests')); ?>
                    </h1>
                    <p class="mt-1 text-sm text-gray-50">
                        <?php echo pending_exercise_h(get_lang('List of pending test attempts across your courses.')); ?>
                    </p>
                </div>

                <a
                    class="btn btn--primary"
                    href="<?php echo pending_exercise_h(api_get_self().'?'.http_build_query(array_merge($_GET, ['export_csv' => 1]))); ?>"
                >
                    <?php echo pending_exercise_h(get_lang('Export as CSV')); ?>
                </a>
            </div>
        </section>

        <form
            id="pending-exercise-filter"
            method="get"
            action="<?php echo pending_exercise_h(api_get_self()); ?>"
            class="rounded-lg border border-gray-25 bg-white p-6 shadow-sm"
        >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('Course')); ?></span>
                    <select id="course_id" name="course_id" class="rounded-lg border border-gray-25 px-3 py-2">
                        <option value="0"><?php echo pending_exercise_h(get_lang('All')); ?></option>
                        <?php foreach ($courses as $id => $title) { ?>
                            <option value="<?php echo (int) $id; ?>" <?php echo $courseId === (int) $id ? 'selected' : ''; ?>>
                                <?php echo pending_exercise_h($title); ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('Exercise')); ?></span>
                    <select
                        id="exercise_id"
                        name="exercise_id"
                        data-selected="<?php echo (int) $exerciseId; ?>"
                        class="rounded-lg border border-gray-25 px-3 py-2"
                    >
                        <option value="0"><?php echo pending_exercise_h(get_lang('All')); ?></option>
                    </select>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('User')); ?></span>
                    <input
                        type="number"
                        min="0"
                        name="filter_by_user"
                        value="<?php echo (int) $filterUserId; ?>"
                        class="rounded-lg border border-gray-25 px-3 py-2"
                        placeholder="<?php echo pending_exercise_h(get_lang('User ID')); ?>"
                    >
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('Status')); ?></span>
                    <select name="status" class="rounded-lg border border-gray-25 px-3 py-2">
                        <?php
                        $statuses = [
                            1 => get_lang('All'),
                            2 => get_lang('Validated'),
                            3 => get_lang('NotValidated'),
                            4 => get_lang('Unclosed'),
                            5 => get_lang('Ongoing'),
                        ];
                        foreach ($statuses as $id => $label) {
                            ?>
                            <option value="<?php echo (int) $id; ?>" <?php echo $statusId === (int) $id ? 'selected' : ''; ?>>
                                <?php echo pending_exercise_h($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('QuestionType')); ?></span>
                    <select name="questionTypeId" class="rounded-lg border border-gray-25 px-3 py-2">
                        <option value="0"><?php echo pending_exercise_h(get_lang('All')); ?></option>
                        <option value="1" <?php echo 1 === $questionTypeId ? 'selected' : ''; ?>>
                            <?php echo pending_exercise_h(get_lang('QuestionsWithNoAutomaticCorrection')); ?>
                        </option>
                    </select>
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('StartDate')); ?></span>
                    <input type="date" name="start_date" value="<?php echo pending_exercise_h($startDate); ?>" class="rounded-lg border border-gray-25 px-3 py-2">
                </label>

                <label class="flex flex-col gap-1">
                    <span class="text-sm font-medium text-gray-70"><?php echo pending_exercise_h(get_lang('EndDate')); ?></span>
                    <input type="date" name="end_date" value="<?php echo pending_exercise_h($endDate); ?>" class="rounded-lg border border-gray-25 px-3 py-2">
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?php echo pending_exercise_h(api_get_self()); ?>" class="btn btn--plain">
                    <?php echo pending_exercise_h(get_lang('Reset')); ?>
                </a>

                <button type="submit" class="btn btn--primary">
                    <?php echo pending_exercise_h(get_lang('Search')); ?>
                </button>
            </div>
        </form>

        <section class="rounded-lg border border-gray-25 bg-white shadow-sm">
            <?php if (empty($rows)) { ?>
                <div class="p-6">
                    <?php echo Display::return_message(get_lang('There are no pending tests.'), 'info'); ?>
                </div>
            <?php } else { ?>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse text-sm">
                        <thead class="bg-gray-15 text-left text-gray-90">
                        <tr>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('Course')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('Exercise')); ?></th>
                            <?php if ($officialCodeInList) { ?>
                                <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('OfficialCode')); ?></th>
                            <?php } ?>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('FirstName')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('LastName')); ?></th>
                            <?php if ($showUsername) { ?>
                                <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('LoginName')); ?></th>
                            <?php } ?>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('Duration')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('StartDate')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('EndDate')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('Score')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('IP')); ?></th>
                            <th class="px-4 py-3"><?php echo pending_exercise_h(get_lang('Status')); ?></th>
                            <th class="px-4 py-3 text-right"><?php echo pending_exercise_h(get_lang('Actions')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row) {
                            $sessionId = (int) $row['session_id'];
                            $params = [
                                'cid' => (int) $row['course_id'],
                                'cidReq' => (string) $row['course_code'],
                                'sid' => $sessionId,
                                'gid' => 0,
                                'exerciseId' => (int) $row['exercise_id'],
                                'filter_by_user' => (int) $row['user_id'],
                            ];

                            if ($sessionId > 0) {
                                $params['sid'] = $sessionId;
                                $params['id_session'] = $sessionId;
                            }

                            $reportUrl = api_get_path(WEB_CODE_PATH)
                                .'exercise/exercise_report.php?'
                                .http_build_query($params);

                            $duration = round(((int) $row['duration']) / 60, 2);
                            $revised = (int) $row['revised'];
                            $statusLabel = 1 === $revised
                                ? get_lang('Validated')
                                : (0 === $revised ? get_lang('Not validated') : get_lang('Unclosed'));
                            ?>
                            <tr class="border-t border-gray-25 hover:bg-gray-10">
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['course_title']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['exercise_title']); ?></td>
                                <?php if ($officialCodeInList) { ?>
                                    <td class="px-4 py-3"><?php echo pending_exercise_h($row['official_code']); ?></td>
                                <?php } ?>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['firstname']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['lastname']); ?></td>
                                <?php if ($showUsername) { ?>
                                    <td class="px-4 py-3"><?php echo pending_exercise_h($row['username']); ?></td>
                                <?php } ?>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($duration); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['start_date']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['end_date']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['score'].' / '.$row['max_score']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($row['user_ip']); ?></td>
                                <td class="px-4 py-3"><?php echo pending_exercise_h($statusLabel); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="<?php echo pending_exercise_h($reportUrl); ?>" class="btn btn--primary btn--sm">
                                        <?php echo pending_exercise_h(get_lang('View')); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-25 px-4 py-3 text-sm text-gray-50">
                    <?php echo pending_exercise_h(sprintf(get_lang('%s results'), count($rows))); ?>
                </div>
            <?php } ?>
        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const courseSelect = document.getElementById("course_id")
            const exerciseSelect = document.getElementById("exercise_id")

            if (!courseSelect || !exerciseSelect) {
                return
            }

            async function updateExerciseList(courseId) {
                const selectedExerciseId = exerciseSelect.dataset.selected || "0"

                exerciseSelect.innerHTML = ""
                exerciseSelect.appendChild(new Option("<?php echo addslashes(get_lang('All')); ?>", "0"))

                if (!courseId || parseInt(courseId, 10) <= 0) {
                    return
                }

                const url = new URL(window.location.href)
                url.search = ""
                url.searchParams.set("a", "get_exercise_by_course")
                url.searchParams.set("course_id", courseId)

                try {
                    const response = await fetch(url.toString(), {
                        credentials: "same-origin",
                        headers: {
                            "Accept": "application/json"
                        }
                    })

                    if (!response.ok) {
                        return
                    }

                    const exercises = await response.json()

                    exercises.forEach(function (exercise) {
                        const option = new Option(exercise.text, String(exercise.id))

                        if (String(exercise.id) === String(selectedExerciseId)) {
                            option.selected = true
                        }

                        exerciseSelect.appendChild(option)
                    })
                } catch (error) {
                    console.error("Error loading exercises:", error)
                }
            }

            courseSelect.addEventListener("change", function () {
                exerciseSelect.dataset.selected = "0"
                updateExerciseList(this.value)
            })

            updateExerciseList(courseSelect.value)
        })
    </script>
<?php

Display::display_footer();
