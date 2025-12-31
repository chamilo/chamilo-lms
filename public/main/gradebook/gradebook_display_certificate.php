<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

if (!api_is_student_boss()) {
    api_protect_course_script(true);
}

api_block_anonymous_users();

if (!api_is_allowed_to_edit() && !api_is_student_boss()) {
    api_not_allowed(true);
}

api_set_more_memory_and_time_limits();

// Extra javascript functions for in html head:
$htmlHeadXtra[] = "<script>
function confirmation() {
    if (confirm(\" ".trim(get_lang('Are you sure you want to delete'))." ?\")) {
        return true;
    } else {
        return false;
    }
}
</script>";

$categoryId = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;
$category = null;
$repo = Container::getGradeBookCategoryRepository();
if (!empty($categoryId)) {
    $category = $repo->find($categoryId);
}
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;
$filterOfficialCode = isset($_POST['filter']) ? Security::remove_XSS($_POST['filter']) : null;
$filterOfficialCodeGet = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;

$url = api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId.'&filter='.$filterOfficialCode;
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$statusToFilter = empty($sessionId) ? STUDENT : 0;
$courseLearners = CourseManager::getUserListFromCourseId(
    api_get_course_int_id(),
    $sessionId,
    null,
    null,
    $statusToFilter
);

$courseLearnerIds = array_values(array_filter(array_unique(array_map(
    static function (array $u): int {
        return (int) ($u['user_id'] ?? $u['id'] ?? 0);
    },
    $courseLearners
))));

$filter = api_get_setting('certificate.certificate_filter_by_official_code');
$userList = [];
$filterForm = null;
$certificate_list = [];
if ('true' === $filter) {
    $options = UserManager::getOfficialCodeGrouped();
    $options = array_merge(['all' => get_lang('All')], $options);
    $form = new FormValidator(
        'official_code_filter',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&cat_id='.$categoryId
    );
    $form->addSelect('filter', get_lang('Code'), $options);
    $form->addButton('submit', get_lang('Submit'));
    $filterForm = '<div class="mt-4">'.$form->returnForm().'</div>';

    if ($form->validate()) {
        $officialCode = $form->getSubmitValue('filter');

        if ('all' === $officialCode) {
            $certificate_list = GradebookUtils::get_list_users_certificates($categoryId, $courseLearnerIds);
        } else {
            $rows = UserManager::getUsersByOfficialCode($officialCode);

            $idsByCode = array_values(array_filter(array_unique(array_map(
                static function (array $u): int {
                    return (int) ($u['user_id'] ?? $u['id'] ?? 0);
                },
                $rows ?: []
            ))));

            // Only keep learners from this course/session
            $finalIds = array_values(array_intersect($courseLearnerIds, $idsByCode));

            $certificate_list = GradebookUtils::get_list_users_certificates($categoryId, $finalIds);
        }
    } else {
        $certificate_list = GradebookUtils::get_list_users_certificates($categoryId, $courseLearnerIds);
    }
} else {
    $certificate_list = GradebookUtils::get_list_users_certificates($categoryId, $courseLearnerIds);
}

$content = '';
$allowCustomCertificate = 'true' === api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate') &&
    1 == api_get_course_setting('customcertificate_course_enable', $courseInfo);

$tags = Certificate::notificationTags();

switch ($action) {
    case 'send_notifications':
        $currentUserInfo = api_get_user_info();
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        $subject = get_lang('Certificate notification');
        if (!empty($message)) {
            foreach ($certificate_list as $index => $value) {
                $userInfo = api_get_user_info($value['user_id']);
                if (empty($userInfo)) {
                    continue;
                }
                $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $value['user_id'],
                    $categoryId
                );

                foreach ($list as $valueCertificate) {
                    Certificate::sendNotification(
                        $subject,
                        $message,
                        $userInfo,
                        $courseInfo,
                        $valueCertificate
                    );
                }
            }
            Display::addFlash(Display::return_message(get_lang('Sent')));
        }

        header('Location: '.$url);
        exit;

    case 'show_notification_form':
        $form = new FormValidator('notification', 'post', $url.'&action=send_notifications');
        $form->addHeader(get_lang('Notify'));
        $form->addHtmlEditor('message', get_lang('Message'));
        $form->addLabel(
            get_lang('Tags'),
            Display::return_message(implode('<br />', $tags), 'normal', false)
        );
        $form->addButtonSend(get_lang('Send message'));
        $form->setDefaults(['message' => nl2br(get_lang('((user_first_name)),'))]);
        $content = $form->returnForm();
        break;
    case 'export_all_certificates':
        if ($allowCustomCertificate) {
            $params = 'course_code='.api_get_course_id().
                '&session_id='.api_get_session_id().
                '&'.api_get_cidreq().
                '&cat_id='.$categoryId;
            $url = api_get_path(WEB_PLUGIN_PATH).
                'CustomCertificate/src/print_certificate.php?export_all_in_one=1&'.$params;
        } else {
            if (api_is_student_boss()) {
                $userGroup = new UserGroupModel();
                $userList = $userGroup->getGroupUsersByUser(api_get_user_id());
            } else {
                $userList = [];
                if (!empty($filterOfficialCodeGet)) {
                    $userList = UserManager::getUsersByOfficialCode($filterOfficialCodeGet);
                }
            }

            Category::exportAllCertificates($categoryId, $userList);
        }

        header('Location: '.$url);
        exit;

    case 'export_all_certificates_zip':
        if ($allowCustomCertificate) {
            $params = 'course_code='.api_get_course_id().
                '&session_id='.api_get_session_id().
                '&'.api_get_cidreq().
                '&cat_id='.$categoryId;
            $url = api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/src/print_certificate.php?export_all=1&'.$params;

            header('Location: '.$url);
        }
        exit;
    case 'generate_all_certificates':
        $userList = CourseManager::getUserListFromCourseId(
            api_get_course_int_id(),
            api_get_session_id(),
            null,
            null,
            $statusToFilter
        );
        if (!empty($userList)) {
            foreach ($userList as $userInfo) {
                if (INVITEE == $userInfo['status']) {
                    continue;
                }
                Category::generateUserCertificate($category, $userInfo['user_id']);
            }
        }
        header('Location: '.$url);
        exit;

    case 'delete_all_certificates':
        // CSRF protection (same as row delete)
        if (!Security::check_token('get')) {
            Display::addFlash(
                Display::return_message(get_lang('Security token expired. Please reload the page and try again.'), 'warning')
            );
            header('Location: '.$url);
            exit;
        }

        $certRepo = Container::getGradeBookCertificateRepository();

        // Delete only the users currently listed (respects the official code filter)
        $userIdsToDelete = array_values(array_filter(array_unique(array_map(
            static function (array $row): int {
                return (int) ($row['user_id'] ?? 0);
            },
            $certificate_list ?: []
        ))));

        $deleted = 0;

        foreach ($userIdsToDelete as $userId) {
            try {
                if ($certRepo->deleteCertificateAndRelatedFiles($userId, $categoryId)) {
                    $deleted++;
                }
            } catch (\Throwable $e) {
                error_log('[gradebook_display_certificate] Bulk delete failed for user '.$userId.': '.$e->getMessage());
            }
        }

        Security::clear_token();

        // Keep existing language keys; optional count for debugging
        Display::addFlash(
            Display::return_message(get_lang('Deleted').' ('.$deleted.')', 'confirmation')
        );

        header('Location: '.$url);
        exit;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Assessments'),
];

$this_section = SECTION_COURSES;

$pageTitle = get_lang('List of learner certificates');
Display::display_header($pageTitle);

if ('delete' === $action) {
    $check = Security::check_token('get');

    if (!$check) {
        // Explain why deletion did not happen
        Display::addFlash(
            Display::return_message(
                get_lang('Security token expired. Please reload the page and try again.'),
                'warning'
            )
        );
        header('Location: '.$url);
        exit;
    }

    $certificateId = isset($_GET['certificate_id']) ? (int) $_GET['certificate_id'] : 0;

    if ($certificateId <= 0) {
        Display::addFlash(Display::return_message(get_lang('Certificate not found.'), 'warning'));
        Security::clear_token();
        header('Location: '.$url);
        exit;
    }

    try {
        $certificate = new Certificate($certificateId);
        $result = $certificate->deleteCertificate();
        Security::clear_token();

        Display::addFlash(
            Display::return_message(
                $result ? get_lang('Certificate removed') : get_lang('Certificate can\'t be removed'),
                $result ? 'confirmation' : 'error'
            )
        );
    } catch (\Throwable $e) {
        Security::clear_token();
        error_log('[gradebook_display_certificate] Delete failed: '.$e->getMessage());
        Display::addFlash(Display::return_message(get_lang('Certificate can\'t be removed'), 'error'));
    }

    header('Location: '.$url);
    exit;
}

$defaultBackUrl = api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq();
if (!empty($categoryId)) {
    $defaultBackUrl .= '&selectcat='.(int) $categoryId;
}

$backUrl = $defaultBackUrl;

echo '<div class="w-full mx-auto px-4 sm:px-6 lg:px-8">';

$token = Security::get_token();

if (!empty($content)) {
    echo '<div class="mb-6">'.$content.'</div>';
}

// @todo replace all this code with something like get_total_weight()
$cats = Category::load(
    $categoryId,
    null,
    0,
    null,
    null,
    null,
    null
);

if (!empty($cats)) {
    // With this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id = (api_is_allowed_to_edit() ? null : api_get_user_id());
    } else {
        $stud_id = api_get_user_id();
    }

    $total_weight = $cats[0]->get_weight();
    $allcat = $cats[0]->get_subcategories(
        $stud_id,
        api_get_course_int_id(),
        api_get_session_id()
    );
    $alleval = $cats[0]->get_evaluations($stud_id);
    $alllink = $cats[0]->get_links($stud_id);

    $datagen = new GradebookDataGenerator($allcat, $alleval, $alllink);

    $total_resource_weight = 0;
    if (!empty($datagen)) {
        $data_array = $datagen->get_data(0, 0, null, true);

        if (!empty($data_array)) {
            $newarray = [];
            foreach ($data_array as $data) {
                $newarray[] = array_slice($data, 1);
            }

            foreach ($newarray as $item) {
                $total_resource_weight = $total_resource_weight + $item['2'];
            }
        }
    }

    $weightWarningHtml = '';

    if ($total_resource_weight != $total_weight) {
        $weightWarningHtml = Display::return_message(
            get_lang('The sum of all activity weights must be equal to the total weight indicated in your assessment settings, otherwise the learners will not be able to reach the sufficient score to achieve their certification.'),
            'warning'
        );
    }
}

$actions = '';

$actions .= Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $backUrl,
    ['class' => 'inline-flex items-center']
);

$actions .= Display::url(
    Display::getMdiIcon(ObjectIcon::CERTIFICATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Generate certificates')),
    $url.'&action=generate_all_certificates'
);
$actions .= Display::url(
    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete all certificates')),
    $url.'&action=delete_all_certificates&sec_token='.$token,
    ['onclick' => 'return confirmation();']
);

$hideCertificateExport = api_get_setting('certificate.hide_certificate_export_link');

if (count($certificate_list) > 0 && 'true' !== $hideCertificateExport) {
    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export all certificates to PDF')),
        $url.'&action=export_all_certificates'
    );

    if ($allowCustomCertificate) {
        $actions .= Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export all certificates to ZIP')),
            $url.'&action=export_all_certificates_zip'
        );
    }

    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::SEND_MESSAGE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Send certificate notification to all users')),
        $url.'&action=show_notification_form'
    );
}

// Action toolbar
echo '<div class="mb-4">';
echo Display::toolbarAction('actions', [$actions]);
echo '</div>';

// Title below actions
echo '<div class="mb-4">';
echo Display::page_header($pageTitle);
echo '</div>';

// Warning below title (and below actions)
if (!empty($weightWarningHtml)) {
    echo '<div class="mb-6">';
    echo $weightWarningHtml;
    echo '</div>';
}

if (!empty($filterForm)) {
    echo $filterForm;
}

if (0 == count($certificate_list)) {
    echo '<div class="mt-4">';
    echo Display::return_message(get_lang('No results available'), 'warning');
    echo '</div>';
    echo '</div>'; // container
    Display::display_footer();
    exit;
}

// Modern list layout (cards)
echo '<div class="mt-6 space-y-4">';

foreach ($certificate_list as $index => $value) {
    $learnerName = api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')';

    echo '<div class="bg-white border border-gray-20 rounded-2xl shadow-sm overflow-hidden">';
    echo '<div class="px-4 py-3 sm:px-6 border-b border-gray-20 bg-gray-10">';
    echo '<div class="text-sm font-semibold text-gray-90">'.get_lang('Learner').' : '.$learnerName.'</div>';
    echo '</div>';

    $list = GradebookUtils::get_list_gradebook_certificates_by_user_id(
        $value['user_id'],
        $categoryId
    );

    if (empty($list)) {
        echo '<div class="p-4 sm:p-6 text-sm text-gray-60">'.get_lang('No results available').'</div>';
        echo '</div>';
        continue;
    }

    echo '<div class="divide-y divide-gray-20">';

    foreach ($list as $valueCertificate) {
        echo '<div class="p-4 sm:p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">';

        // Left info
        echo '<div class="flex flex-col sm:flex-row sm:items-center gap-4 text-sm">';
        echo '<div><span class="text-gray-60">'.get_lang('Score').' :</span> <span class="font-medium">'.$valueCertificate['score_certificate'].'</span></div>';
        echo '<div><span class="text-gray-60">'.get_lang('Date').' :</span> <span class="font-medium">'.api_convert_and_format_date($valueCertificate['created_at']).'</span></div>';
        echo '</div>';

        // Right actions
        echo '<div class="flex items-center gap-2 flex-wrap justify-start lg:justify-end">';

        /**
         * Resource-first per-course certificate resolution.
         * - First: try to resolve a Resource certificate for (cat_id, user_id).
         * - Never fallback to the user's general/custom certificate here.
         * - If no Resource exists, use legacy path_certificate (HTML/PDF in /certificates/).
         * - Keep publish flag behavior; platform admins bypass publish.
         */
        $isPublished = !empty($valueCertificate['publish']) || api_is_allowed_to_edit() || api_is_platform_admin();

        $certRepo = Container::getGradeBookCertificateRepository();
        $router   = null;
        try {
            $router = Container::getRouter(); // Might not exist in some installs; guarded below.
        } catch (\Throwable $e) {
            // Non-fatal. We'll just skip route generation if router is not available.
        }

        $htmlUrl     = '';
        $pdfUrl      = '';
        $isAvailable = false;

        // Try to resolve the per-category (course/session) resource
        try {
            $entity = $certRepo->getCertificateByUserId(
                $categoryId === 0 ? null : (int) $categoryId,
                (int) $value['user_id']
            );

            if ($entity && $entity->hasResourceNode()) {
                // HTML is served through the Resource layer (secured, hashed filename)
                $htmlUrl     = (string) $certRepo->getResourceFileUrl($entity);
                $isAvailable = $isPublished && $htmlUrl !== '';

                // PDF is served by your Symfony controller (update the route name/params if needed)
                if ($router && $isAvailable) {
                    // Attempt 1: route by certificateId (common signature)
                    try {
                        $pdfUrl = $router->generate('gradebook_certificate_pdf', [
                            'certificateId' => (int) $valueCertificate['id'],
                        ]);
                    } catch (\Throwable $e1) {
                        // Attempt 2: route by userId+catId (alternative signature)
                        try {
                            $pdfUrl = $router->generate('gradebook_certificate_pdf', [
                                'userId' => (int) $value['user_id'],
                                'catId'  => (int) $categoryId,
                            ]);
                        } catch (\Throwable $e2) {
                            error_log('[gradebook_display_certificate] PDF route resolution failed: '.$e2->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[gradebook_display_certificate] resource resolve error: '.$e->getMessage());
        }

        // Legacy per-course fallback ONLY if no resource is available.
        // IMPORTANT: do NOT fallback to general/custom certificate on this screen.
        if (!$isAvailable) {
            $pathRaw = isset($valueCertificate['path_certificate']) ? (string) $valueCertificate['path_certificate'] : '';
            $path    = ltrim($pathRaw, '/'); // normalize: remove leading slash if present
            $hasPath = $path !== '';
            $hash    = $hasPath ? pathinfo($path, PATHINFO_FILENAME) : '';

            $isAvailable = $hasPath && $isPublished;

            if ($isAvailable) {
                $htmlUrl = api_get_path(WEB_PATH).'certificates/'.$hash.'.html';
                $pdfUrl  = api_get_path(WEB_PATH).'certificates/'.$hash.'.pdf';
            }
        }

        // Render buttons (enabled/disabled) preserving existing behavior
        if ($isAvailable) {
            // HTML certificate button/link
            echo Display::url(
                get_lang('Certificate'),
                $htmlUrl,
                ['target' => '_blank', 'class' => 'btn btn--plain']
            );

            // PDF download icon/link (only if we have a URL)
            if (!empty($pdfUrl)) {
                echo Display::url(
                    Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Download')),
                    $pdfUrl,
                    ['target' => '_blank', 'title' => 'Download PDF certificate']
                );
            } else {
                // Path is not available for some reason: show a disabled icon with a clear tooltip
                echo '<button type="button" class="btn btn-link disabled" disabled title="PDF path unavailable">'
                    .Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon text-muted', null, ICON_SIZE_SMALL, get_lang('PDF path unavailable'))
                    .'</button>';
            }
        } else {
            // Disabled HTML button
            echo '<button type="button" class="btn btn--plain disabled" disabled title="Certificate not available">'
                .get_lang('Certificate')
                .'</button>';

            // Disabled PDF icon
            echo '<button type="button" class="btn btn-link disabled" disabled title="PDF download unavailable">'
                .Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon text-muted', null, ICON_SIZE_SMALL, get_lang('PDF download unavailable'))
                .'</button>';
        }

        // Delete action (unchanged)
        echo '<a onclick="return confirmation();" '
            .'href="gradebook_display_certificate.php?sec_token='.$token.'&'.api_get_cidreq()
            .'&action=delete&cat_id='.$categoryId.'&certificate_id='.$valueCertificate['id'].'">'
            .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete'))
            .'</a>';

        echo '</div>'; // actions
        echo '</div>'; // row
    }

    echo '</div>'; // divider
    echo '</div>'; // card
}

echo '</div>'; // list
echo '</div>'; // container

Display::display_footer();
