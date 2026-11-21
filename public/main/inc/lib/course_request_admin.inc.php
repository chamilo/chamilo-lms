<?php

/* For licensing terms, see /license.txt */

/**
 * Shared presentation and actions for the three legacy course request lists.
 *
 * Based on the original course request pages by José Manuel Abuin Mosquera,
 * Bruno Rubio Gayo and Ivan Tcholakov.
 */
function course_request_admin_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
}

function course_request_admin_pages(): array
{
    return [
        COURSE_REQUEST_PENDING => ['course_request_review.php', get_lang('Review incoming course requests'), 'mdi-notebook-heart-outline'],
        COURSE_REQUEST_ACCEPTED => ['course_request_accepted.php', get_lang('Accepted course requests'), 'mdi-check-circle-outline'],
        COURSE_REQUEST_REJECTED => ['course_request_rejected.php', get_lang('Rejected course requests'), 'mdi-close-circle-outline'],
    ];
}

function course_request_admin_navigation(int $status, array $counts = []): void
{
    $escape = 'course_request_admin_escape';
    echo '<nav class="mb-4 flex flex-wrap gap-2 border-b border-gray-25" aria-label="'.$escape(get_lang('Course management')).'">';
    foreach (course_request_admin_pages() as $key => [$url, $label, $icon]) {
        $active = $key === $status;
        echo '<a href="'.$escape($url).'" class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-body-2 font-semibold '.($active ? 'border-primary text-primary' : 'border-transparent text-gray-90 hover:text-primary').'"'.($active ? ' aria-current="page"' : '').'>';
        echo '<span class="mdi '.$escape($icon).'" aria-hidden="true"></span>'.$escape($label);
        if (isset($counts[$key])) {
            echo '<span class="rounded-full bg-support-2 px-2 py-1 text-caption">'.(int) $counts[$key].'</span>';
        }
        echo '</a>';
    }
    echo '</nav>';
}

function course_request_admin_list(int $status): void
{
    global $interbreadcrumb, $tool_name;

    if (!api_protect_admin_script()) {
        return;
    }
    $escape = 'course_request_admin_escape';
    $pages = course_request_admin_pages();
    [$path, $title] = $pages[$status];
    $tool_name = $title;
    $interbreadcrumb[] = ['url' => '/admin', 'name' => get_lang('Administration')];
    $interbreadcrumb[] = ['url' => '/admin/course-list', 'name' => get_lang('Course list')];

    if ('true' !== api_get_setting('course.course_validation')) {
        Display::display_header($title);
        echo Display::return_message(sprintf(
            get_lang('The "Course validation" feature is not enabled at the moment. In order to use this feature, please, enable it by using the  %s setting.'),
            '<a href="/admin/settings/search_settings?keyword=course_validation">'.$escape(get_lang('Courses validation')).'</a>'
        ), 'warning', false);
        Display::display_footer();

        return;
    }

    $connection = Database::getManager()->getConnection();
    $requestTable = Database::get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $categoryTable = Database::get_main_table(TABLE_MAIN_CATEGORY);
    // All three lists retain the existing platform-admin visibility of requests.
    $keyword = is_string($_GET['keyword'] ?? null) ? mb_substr(trim($_GET['keyword']), 0, 250) : '';
    $sorts = ['code' => 'cr.code', 'title' => 'cr.title', 'category' => 'cc.code', 'trainer' => 'cr.tutor_name', 'date' => 'cr.request_date'];
    $headers = ['code' => get_lang('Course code'), 'title' => get_lang('Title'), 'category' => get_lang('Category'), 'trainer' => get_lang('Trainer'), 'date' => get_lang('Request date')];
    $sort = is_string($_GET['sort'] ?? null) && isset($sorts[$_GET['sort']]) ? $_GET['sort'] : 'date';
    $direction = 'asc' === ($_GET['direction'] ?? '') ? 'ASC' : 'DESC';
    $page = is_scalar($_GET['page'] ?? null) ? max(1, (int) $_GET['page']) : 1;
    $pageSize = 20;
    $url = static function (array $changes = []) use ($path, $keyword, $sort, $direction, &$page): string {
        return $path.'?'.http_build_query(array_replace(['keyword' => $keyword, 'sort' => $sort, 'direction' => strtolower($direction), 'page' => $page], $changes));
    };
    $canDelete = COURSE_REQUEST_PENDING !== $status || DELETE_ACTION_ENABLED;
    $labels = [
        'accept' => get_lang('Accept this course'),
        'reject' => get_lang('Reject this course request'),
        'info' => get_lang('Ask for additional information'),
        'delete' => get_lang('Delete this course request'),
        'bulk_delete' => get_lang('Delete selected course request(s)'),
    ];
    $confirm = null;
    $message = null;
    $messageType = 'warning';
    if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '')) {
        if (!Security::check_token('post', null, 'course_request_admin')) {
            api_not_allowed(true);

            return;
        }
        $operation = is_string($_POST['operation'] ?? null) ? $_POST['operation'] : '';
        [$action, $id] = array_pad(explode(':', $operation, 2), 2, '0');
        $ids = 'bulk_delete' === $action ? ($_POST['course_request'] ?? []) : [$id];
        $ids = is_array($ids) ? array_values(array_unique(array_filter(array_map(static fn ($value): int => is_scalar($value) ? max(0, (int) $value) : 0, $ids)))) : [];
        $ids = array_slice($ids, 0, $pageSize);
        $allowed = [];
        foreach ($ids as $requestId) {
            $row = $connection->fetchAssociative("SELECT id, code, status, info FROM $requestTable WHERE id = ?", [$requestId]);
            if (!$row || (int) $row['status'] !== $status) {
                continue;
            }
            $valid = match ($action) {
                'accept' => COURSE_REQUEST_ACCEPTED !== $status,
                'reject' => COURSE_REQUEST_PENDING === $status,
                'info' => COURSE_REQUEST_ACCEPTED !== $status && 0 === (int) $row['info'],
                'delete', 'bulk_delete' => $canDelete,
                default => false,
            };
            if ($valid) {
                $allowed[] = $row;
            }
        }
        if (!$ids || count($allowed) !== count($ids)) {
            $message = get_lang('The course request you wanted to access has not been found or it does not exist.');
        } elseif ('1' !== ($_POST['confirmed'] ?? '')) {
            $confirm = [$action, $allowed];
        } else {
            foreach ($allowed as $row) {
                $requestId = (int) $row['id'];
                $code = $escape($row['code']);
                $result = match ($action) {
                    'accept' => CourseRequestManager::accept_course_request($requestId),
                    'reject' => CourseRequestManager::reject_course_request($requestId),
                    'info' => CourseRequestManager::ask_for_additional_info($requestId),
                    'delete', 'bulk_delete' => CourseRequestManager::delete_course_request($requestId),
                };
                $message = match ($action) {
                    'accept' => $result ? sprintf(get_lang('The course request %s has been accepted. A new course %s has been created.'), $code, $escape($result)) : sprintf(get_lang('The course request %s has not been accepted due to internal error.'), $code),
                    'reject' => sprintf(get_lang($result ? 'The course request %s has been rejected.' : 'The course request %s has not been rejected due to internal error.'), $code),
                    'info' => sprintf(get_lang($result ? 'Additional information about the course request %s has been asked.' : 'Additional information about the course request %s has not been asked due to internal error.'), $code),
                    'delete', 'bulk_delete' => sprintf(get_lang($result ? 'The course request %s has been deleted.' : 'The course request %s has not been deleted due to internal error.'), $code),
                };
                Display::addFlash(Display::return_message($message, $result ? 'normal' : 'error', false));
            }
            header('Location: '.$url());

            exit;
        }
    }

    $counts = [];
    foreach ($connection->fetchAllAssociative("SELECT status, COUNT(*) AS total FROM $requestTable GROUP BY status") as $row) {
        $counts[(int) $row['status']] = (int) $row['total'];
    }
    foreach ($pages as $key => $unused) {
        $counts[$key] ??= 0;
    }
    $where = ' WHERE cr.status = ?';
    $params = [$status];
    if ('' !== $keyword) {
        $where .= ' AND (cr.title LIKE ? OR cr.code LIKE ? OR cr.visual_code LIKE ? OR cr.tutor_name LIKE ?)';
        array_push($params, ...array_fill(0, 4, '%'.$keyword.'%'));
    }
    $from = " FROM $requestTable cr LEFT JOIN $categoryTable cc ON cc.id = cr.category_id";
    $total = (int) $connection->fetchOne('SELECT COUNT(*)'.$from.$where, $params);
    $lastPage = max(1, (int) ceil($total / $pageSize));
    $page = min($page, $lastPage);
    $offset = ($page - 1) * $pageSize;
    $rows = $connection->fetchAllAssociative('SELECT cr.id, cr.code, cr.title, cc.code AS category_code, cr.user_id, cr.tutor_name, cr.request_date, cr.info'.$from.$where.' ORDER BY '.$sorts[$sort].' '.$direction.', cr.id DESC LIMIT '.$pageSize.' OFFSET '.$offset, $params);
    $token = Security::get_existing_token('course_request_admin');
    $tokenInput = '<input type="hidden" name="course_request_admin_sec_token" value="'.$escape($token).'">';
    Display::display_header($title);
    ?>
    <div class="w-full min-w-0 space-y-4">
        <h2 class="text-xl font-semibold text-gray-90"><?php echo $escape($title); ?></h2>
        <?php course_request_admin_navigation($status, $counts); ?>
        <?php if ($message) {
            echo Display::return_message($escape($message), $messageType, false);
        } ?>
        <?php if ($confirm) { ?>
            <section class="rounded-xl border border-gray-25 bg-support-2 p-4">
                <h3 class="font-semibold text-gray-90"><?php echo $escape($labels[$confirm[0]]); ?></h3>
                <p class="my-2 text-body-2"><?php echo $escape(implode(', ', array_column($confirm[1], 'code'))); ?></p>
                <form method="post" action="<?php echo $escape($url()); ?>" class="flex flex-wrap gap-2">
                    <?php echo $tokenInput; ?>
                    <input type="hidden" name="confirmed" value="1">
                    <?php foreach ($confirm[1] as $row) { ?>
                        <input type="hidden" name="course_request[]" value="<?php echo (int) $row['id']; ?>">
                    <?php } ?>
                    <button class="btn <?php echo in_array($confirm[0], ['delete', 'bulk_delete', 'reject'], true) ? 'btn--danger' : 'btn--success'; ?>" type="submit" name="operation" value="<?php echo $escape($confirm[0].':'.(int) $confirm[1][0]['id']); ?>"><?php echo $escape(get_lang('Confirm')); ?></button>
                    <a class="btn btn--plain" href="<?php echo $escape($url()); ?>"><?php echo $escape(get_lang('Cancel')); ?></a>
                </form>
            </section>
        <?php } ?>
        <form method="get" action="<?php echo $escape($path); ?>" class="flex flex-wrap items-end gap-2">
            <div class="w-full max-w-md">
                <label class="mb-1 block text-body-2 font-semibold" for="course-request-keyword"><?php echo $escape(get_lang('Keyword')); ?></label>
                <input class="form-control w-full" id="course-request-keyword" name="keyword" type="search" value="<?php echo $escape($keyword); ?>" placeholder="<?php echo $escape(get_lang('Search')); ?>">
            </div>
            <button class="btn btn--primary" type="submit"><span class="mdi mdi-magnify" aria-hidden="true"></span> <?php echo $escape(get_lang('Search')); ?></button>
            <?php if ('' !== $keyword) { ?><a class="btn btn--plain" href="<?php echo $escape($path); ?>"><?php echo $escape(get_lang('Clear')); ?></a><?php } ?>
        </form>
        <section class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm">
            <?php if (!$rows) { ?>
                <div class="flex flex-col items-center gap-2 px-4 py-12 text-center text-gray-50">
                    <span class="mdi mdi-notebook-outline text-4xl" aria-hidden="true"></span>
                    <p class="m-0 text-body-1 font-semibold"><?php echo $escape(get_lang('No results found')); ?></p>
                </div>
            <?php } else { ?>
                <form method="post" action="<?php echo $escape($url()); ?>">
                    <?php echo $tokenInput; ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-body-2">
                            <thead class="bg-support-2 text-gray-90"><tr>
                                <?php if ($canDelete) { ?><th scope="col" class="p-3"><span class="sr-only"><?php echo $escape(get_lang('Select')); ?></span></th><?php } ?>
                                <?php foreach ($headers as $key => $label) { ?>
                                    <th scope="col" class="px-3 py-3 text-start font-semibold"<?php echo $key === $sort ? ' aria-sort="'.('ASC' === $direction ? 'ascending' : 'descending').'"' : ''; ?>><a class="inline-flex items-center gap-1 text-gray-90" href="<?php echo $escape($url(['sort' => $key, 'direction' => $key === $sort && 'ASC' === $direction ? 'desc' : 'asc', 'page' => 1])); ?>"><?php echo $escape($label); ?><span class="mdi <?php echo $key === $sort ? ('ASC' === $direction ? 'mdi-arrow-up' : 'mdi-arrow-down') : 'mdi-swap-vertical'; ?>" aria-hidden="true"></span></a></th>
                                <?php } ?>
                                <th scope="col" class="px-3 py-3 text-end"><?php echo $escape(get_lang('Actions')); ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($rows as $row) {
                                $id = (int) $row['id']; ?>
                                <tr class="border-t border-gray-20 hover:bg-support-2">
                                    <?php if ($canDelete) { ?><td class="p-3"><input type="checkbox" name="course_request[]" value="<?php echo $id; ?>" aria-label="<?php echo $escape(get_lang('Select').' '.$row['code']); ?>"></td><?php } ?>
                                    <td class="px-3 py-3 font-semibold whitespace-nowrap"><?php echo $escape($row['code']); ?></td>
                                    <td class="px-3 py-3 min-w-48"><a class="text-primary font-medium" href="course_request_edit.php?id=<?php echo $id; ?>&amp;caller=<?php echo $status; ?>"><?php echo $escape($row['title']); ?></a></td>
                                    <td class="px-3 py-3"><?php echo $escape($row['category_code'] ?? '-'); ?></td>
                                    <td class="px-3 py-3"><a class="text-primary" href="user_information.php?user_id=<?php echo (int) $row['user_id']; ?>"><?php echo $escape($row['tutor_name'] ?? '-'); ?></a></td>
                                    <td class="px-3 py-3 whitespace-nowrap"><?php echo $escape(api_get_local_time($row['request_date'])); ?></td>
                                    <td class="px-3 py-3"><div class="flex flex-wrap justify-end gap-2">
                                        <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-25 text-primary hover:bg-support-2" href="course_request_edit.php?id=<?php echo $id; ?>&amp;caller=<?php echo $status; ?>" title="<?php echo $escape(get_lang('Edit')); ?>" aria-label="<?php echo $escape(get_lang('Edit')); ?>"><span class="mdi mdi-pencil text-lg" aria-hidden="true"></span></a>
                                        <?php
                                            $actions = [];
                                if (COURSE_REQUEST_ACCEPTED !== $status) {
                                    $actions['accept'] = 'mdi-check-circle-outline';
                                }
                                if (COURSE_REQUEST_PENDING === $status) {
                                    $actions['reject'] = 'mdi-close-circle-outline';
                                }
                                if (COURSE_REQUEST_ACCEPTED !== $status && !(int) $row['info']) {
                                    $actions['info'] = 'mdi-information-outline';
                                }
                                if ($canDelete) {
                                    $actions['delete'] = 'mdi-delete-outline';
                                }
                                foreach ($actions as $key => $icon) { ?>
                                            <button type="submit" name="operation" value="<?php echo $escape($key.':'.$id); ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-25 <?php echo in_array($key, ['delete', 'reject'], true) ? 'text-danger' : 'text-primary'; ?>" title="<?php echo $escape($labels[$key]); ?>" aria-label="<?php echo $escape($labels[$key]); ?>"><span class="mdi <?php echo $escape($icon); ?> text-lg" aria-hidden="true"></span></button>
                                        <?php } ?>
                                    </div></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($canDelete) { ?><div class="border-t border-gray-20 p-3"><button class="btn btn--plain" type="submit" name="operation" value="bulk_delete"><?php echo $escape($labels['bulk_delete']); ?></button></div><?php } ?>
                </form>
            <?php } ?>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-20 px-4 py-3 text-body-2 text-gray-50">
                <span><?php echo $total ? $offset + 1 : 0; ?>–<?php echo min($offset + $pageSize, $total); ?> / <?php echo $total; ?></span>
                <div class="flex items-center gap-2">
                    <?php if ($page > 1) { ?><a class="btn btn--plain" href="<?php echo $escape($url(['page' => $page - 1])); ?>"><?php echo $escape(get_lang('Previous')); ?></a><?php } ?>
                    <span><?php echo $page; ?> / <?php echo $lastPage; ?></span>
                    <?php if ($page < $lastPage) { ?><a class="btn btn--plain" href="<?php echo $escape($url(['page' => $page + 1])); ?>"><?php echo $escape(get_lang('Next')); ?></a><?php } ?>
                </div>
            </div>
        </section>
        <a class="btn btn--plain" href="/admin/course-list"><span class="mdi mdi-arrow-left" aria-hidden="true"></span> <?php echo $escape(get_lang('Course list')); ?></a>
    </div>
    <?php
    Display::display_footer();
}
