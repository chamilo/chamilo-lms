<?php

/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to create courses by uploading a CSV file
 * Copyright (c) 2005 Bart Mollet <bart.mollet@hogent.be>.
 */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Admin\CourseCsvImportValidator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Validates imported data.
 *
 * @param array $courses
 *
 * @return array
 */
function validate_courses_data(array $courses): array
{
    $errors = [];
    $courseCodes = [];

    foreach ($courses as $index => $course) {
        $line = $index + 2;
        $code = CourseManager::generate_course_code($course['Code']);

        if ('' === $code) {
            $errors[] = [
                'line' => $line,
                'code' => $course['Code'],
                'title' => $course['Title'],
                'error' => 'The course code is invalid.',
            ];
            continue;
        }

        if (isset($courseCodes[$code])) {
            $errors[] = [
                'line' => $line,
                'code' => $course['Code'],
                'title' => $course['Title'],
                'error' => get_lang('A code has been used twice in the file. This is not authorized. Courses codes should be unique.'),
            ];
        }
        $courseCodes[$code] = true;

        if (api_get_course_info($code)) {
            $errors[] = [
                'line' => $line,
                'code' => $course['Code'],
                'title' => $course['Title'],
                'error' => get_lang('This code exists'),
            ];
        }

        foreach (getTeacherListInArray($course['Teacher']) as $teacher) {
            if (empty(api_get_user_info_from_username($teacher))) {
                $errors[] = [
                    'line' => $line,
                    'code' => $course['Code'],
                    'title' => $course['Title'],
                    'error' => get_lang('Unknown trainer').' ('.$teacher.')',
                ];
            }
        }
    }

    return $errors;
}

/**
 * Split teacher usernames from the CSV, ignoring empty entries and whitespace.
 *
 * @return list<string>
 */
function getTeacherListInArray(string $teachers): array
{
    if ('' === trim($teachers)) {
        return [];
    }

    return array_values(array_unique(array_filter(
        array_map('trim', explode('|', $teachers)),
        static fn (string $teacher): bool => '' !== $teacher
    )));
}

/**
 * Create courses with their category and teachers after the whole CSV is validated.
 *
 * @return array{created: list<string>, errors: list<string>}
 */
function save_courses_data(array $courses): array
{
    $created = [];
    $errors = [];
    $creatorId = api_get_user_id();
    $addMeAsTeacher = !empty($_POST['add_me_as_teacher']);

    foreach ($courses as $index => $courseData) {
        $line = $index + 2;
        $code = $courseData['Code'];

        try {
            // The CSV contains a category code, but course registration expects IDs.
            $category = CourseCategory::getCategory($courseData['CourseCategory']);
            if (empty($category)) {
                $normalizedCategoryCode = CourseManager::generate_course_code($courseData['CourseCategory']);
                if ('' === $normalizedCategoryCode) {
                    throw new RuntimeException('Invalid course category code.');
                }

                $category = CourseCategory::getCategory($normalizedCategoryCode);
                if (empty($category)) {
                    $categoryName = $courseData['CourseCategoryName'] ?: $courseData['CourseCategory'];
                    $createdCategory = CourseCategory::add($courseData['CourseCategory'], $categoryName, 'TRUE');
                    if (null === $createdCategory) {
                        throw new RuntimeException('Could not create the course category.');
                    }
                    $category = ['id' => $createdCategory->getId()];
                }
            }

            $categoryId = (int) ($category['id'] ?? 0);
            if ($categoryId <= 0) {
                throw new RuntimeException('Course category not found.');
            }

            $teacherIds = [];
            foreach (getTeacherListInArray($courseData['Teacher']) as $username) {
                $teacherInfo = api_get_user_info_from_username($username);
                if (empty($teacherInfo['user_id'])) {
                    throw new RuntimeException('Unknown trainer.');
                }
                $teacherIds[] = (int) $teacherInfo['user_id'];
            }

            $params = [
                'title' => $courseData['Title'],
                'wanted_code' => $code,
                'tutor_name' => null,
                'course_categories' => [$categoryId],
                'course_language' => $courseData['Language'],
                'user_id' => $creatorId,
                'add_user_as_teacher' => $addMeAsTeacher,
            ];

            // Preserve the established course-creation lifecycle and CSV options.
            $course = CourseManager::create_course($params);
            if (!$course instanceof CourseEntity) {
                throw new RuntimeException('Course creation returned no course.');
            }

            $created[] = '<a href="'.htmlspecialchars(api_get_course_url($course->getId()).'/', ENT_QUOTES, 'UTF-8').'">'
                .htmlspecialchars($course->getTitle(), ENT_QUOTES, 'UTF-8').'</a>';

            foreach (array_unique($teacherIds) as $teacherId) {
                if ($addMeAsTeacher && $creatorId === $teacherId) {
                    continue;
                }
                if (!CourseManager::subscribeUser($teacherId, $course->getId(), COURSEMANAGER)) {
                    $errors[] = sprintf(
                        '%s %d: Could not subscribe teacher %d to course %s.',
                        get_lang('Line'),
                        $line,
                        $teacherId,
                        htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        } catch (Throwable $exception) {
            $logCode = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $code);
            error_log(sprintf('Course CSV import failed on line %d (%s): %s', $line, $logCode, $exception->getMessage()));
            $errors[] = sprintf(
                '%s %d: %s (%s) — Course creation failed. Check the server error log.',
                get_lang('Line'),
                $line,
                htmlspecialchars($courseData['Title'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
            );
        }
    }

    return ['created' => $created, 'errors' => $errors];
}

/**
 * Read the CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All course-information read from the file
 */
function parse_csv_courses_data($file)
{
    return Import::csv_reader($file);
}

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$defined_auth_sources[] = UserAuthSource::PLATFORM;

if (isset($extAuthSource) && is_array($extAuthSource)) {
    $defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('Import courses list').' CSV';

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

set_time_limit(0);

// Symfony may have closed the session after handling the legacy bootstrap request.
// Reopen it so FormValidator's CSRF token and import feedback persist across requests.
$session = Container::getSession();
if ($session instanceof SessionInterface && !$session->isStarted()) {
    $session->start();
}

// Build the form first and protect it: FormValidator::protect() adds the CSRF
// token and $form->validate() verifies it before any processing runs.
$form = new FormValidator(
    'import',
    'post',
    api_get_self(),
    null,
    ['enctype' => 'multipart/form-data']
);
$form->protect();
$form->addHeader($tool_name);
$form->addElement('file', 'import_file', get_lang('CSV file import location'));
$form->addElement('checkbox', 'add_me_as_teacher', null, get_lang('Add me as teacher in the imported courses.'));
$form->addButtonImport(get_lang('Import'), 'save');

$formValidated = $form->validate();
$isImportRequest = 'POST' === ($_SERVER['REQUEST_METHOD'] ?? '');
$importFeedback = '';

if ($isImportRequest) {
    if (!$formValidated) {
        $importFeedback = Display::return_message('The import form could not be validated.', 'error', false);
    } elseif (empty($_FILES['import_file']['tmp_name']) || UPLOAD_ERR_OK !== ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE)) {
        $importFeedback = Display::return_message(get_lang('The file upload has failed.'), 'error', false);
    } elseif ('csv' !== strtolower(pathinfo((string) $_FILES['import_file']['name'], PATHINFO_EXTENSION))) {
        $importFeedback = Display::return_message(get_lang('You must import a file corresponding to the selected format'), 'error', false);
    } else {
        try {
            $check = Import::assertCommaSeparated($_FILES['import_file']['tmp_name'], true);
            if (true !== $check) {
                $importFeedback = Display::return_message($check, 'error', false);
            } else {
                $courses = (new CourseCsvImportValidator())->normalizeRows(
                    parse_csv_courses_data($_FILES['import_file']['tmp_name'])
                );
                $errors = validate_courses_data($courses);

                if ([] !== $errors) {
                    $errorMessage = '<ul>';
                    foreach ($errors as $error) {
                        $errorMessage .= '<li>'.get_lang('Line').' '.(int) $error['line'].': '
                            .htmlspecialchars($error['error'], ENT_QUOTES, 'UTF-8').' — '
                            .htmlspecialchars($error['title'], ENT_QUOTES, 'UTF-8').' ('
                            .htmlspecialchars($error['code'], ENT_QUOTES, 'UTF-8').')</li>';
                    }
                    $importFeedback .= Display::return_message($errorMessage.'</ul>', 'error', false);
                } else {
                    $result = save_courses_data($courses);
                    if ([] !== $result['created']) {
                        $importFeedback .= Display::return_message(
                            implode('<br>', $result['created']).' '.get_lang('Created'),
                            'success',
                            false
                        );
                    }
                    if ([] !== $result['errors']) {
                        $importFeedback .= Display::return_message(
                            'Some courses could not be imported:<ul><li>'.implode('</li><li>', $result['errors']).'</li></ul>',
                            'error',
                            false
                        );
                    }
                    if ([] === $result['created'] && [] === $result['errors']) {
                        $importFeedback .= Display::return_message('No courses were imported.', 'warning', false);
                    }
                }
            }
        } catch (InvalidArgumentException $exception) {
            $importFeedback = Display::return_message(
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
                'error',
                false
            );
        } catch (Throwable $exception) {
            error_log('Course CSV import failed: '.$exception->getMessage());
            $importFeedback = Display::return_message('Unable to import the CSV file. Check the server error log.', 'error', false);
        }
    }

    // QuickForm consumes its CSRF token on successful validation.
    // Issue a new token before rendering the form for another import.
    $freshToken = Security::get_token();
    $form->setConstants(['protect_token' => $freshToken]);
    $form->setToken($freshToken);

    if ($session instanceof SessionInterface) {
        if (!$session->isStarted()) {
            $session->start();
        }
        $session->save();
    }
}

Display::display_header($tool_name);
echo $importFeedback;

$form->display();

$content = '
<div style="clear: both;"></div>
<p>'.get_lang('The CSV file must look like this').' ('.get_lang('Fields in <strong>bold</strong> are mandatory.').') :</p>
<blockquote>
<pre>
<b>Code</b>,<b>Title</b>,<b>CourseCategory</b>,<b>CourseCategoryName</b>,Teacher,Language
BIO0015,Biology,BIO,Science,teacher1,english
BIO0016,Maths,MATH,Engineerng,teacher2|teacher3,english
BIO0017,Language,LANG,, ,english
</pre>
</blockquote>';
echo Display::prose($content);

Display::display_footer();
