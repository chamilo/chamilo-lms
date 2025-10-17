<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__ . '/../inc/global.inc.php';

api_protect_admin_script(true);

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$toolName = get_lang('Export all exercise results');

// Get request parameters
$sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null;
$courseId = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;

// Get repositories
$user = api_get_user_entity(api_get_user_id());
$sessionRepository = Container::getSessionRepository();
$courseRepository = Container::getCourseRepository();
$cQuizRepository = Container::getCQuizRepository();

// Load sessions using repository
$sessionSelectList = [0 => get_lang('Select')];
try {
    if (api_is_platform_admin()) {
        $sessionList = $sessionRepository->findAll();
    } else {
        $sessionList = $sessionRepository->getSessionsByUser($user, api_get_url_entity())->getQuery()->getResult();
    }

    foreach ($sessionList as $session) {
        $sessionSelectList[$session->getId()] = $session->getTitle();
    }
} catch (Exception $e) {
    error_log('Error loading sessions: ' . $e->getMessage());
    $sessionSelectList = [0 => get_lang('Error loading sessions')];
}

// Load courses using repository based on selected session
$courseSelectList = [0 => get_lang('Select a session first')];
if (!empty($sessionId) && $sessionId > 0) {
    try {
        $sessionEntity = $sessionRepository->find($sessionId);
        if ($sessionEntity) {
            $courseSelectList = [0 => get_lang('Select')];
            $sessionCourses = $sessionEntity->getCourses();
            foreach ($sessionCourses as $sessionCourse) {
                $course = $sessionCourse->getCourse();
                $courseSelectList[$course->getId()] = $course->getTitle();
            }
        } else {
            $courseSelectList = [0 => get_lang('Session not found')];
        }
    } catch (Exception $e) {
        error_log('Error loading courses for session ' . $sessionId . ': ' . $e->getMessage());
        $courseSelectList = [0 => get_lang('Error loading courses')];
    }
}

// Load exercises using repository based on selected course and session
$exerciseSelectList = [0 => get_lang('Select a course first')];
if (!empty($courseId) && $courseId > 0) {
    try {
        $courseEntity = $courseRepository->find($courseId);
        $sessionEntity = $sessionId ? $sessionRepository->find($sessionId) : null;
        
        if ($courseEntity) {
            $exerciseList = $cQuizRepository->findAllByCourse($courseEntity, $sessionEntity);
            $exerciseList = $exerciseList->getQuery()->getResult();
            
            if (!empty($exerciseList)) {
                $exerciseSelectList = [0 => get_lang('Select')];
                foreach ($exerciseList as $exercise) {
                    $exerciseSelectList[$exercise->getIid()] = $exercise->getTitle();
                }
            } else {
                $exerciseSelectList = [0 => get_lang('No exercises found')];
            }
        } else {
            $exerciseSelectList = [0 => get_lang('Course not found')];
        }
    } catch (Exception $e) {
        error_log('Error loading exercises for course ' . $courseId . ': ' . $e->getMessage());
        $exerciseSelectList = [0 => get_lang('Error loading exercises')];
    }
}

// JavaScript for form submission (page reload on change)
$htmlHeadXtra[] = "
<style>
.select2-selection, .select2-selection__rendered {
    height: 38px !important;
    line-height: 38px !important;
}
</style>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        placeholder: '" . addslashes(get_lang('Select')) . "',
        allowClear: true
    });
    
    // Session change handler - reload page with new session
    $('#session_id').on('change', function() {
        var sessionId = $(this).val();
        if (sessionId && sessionId != '0') {
            window.location.href = '" . api_get_self() . "?session_id=' + sessionId;
        } else {
            window.location.href = '" . api_get_self() . "';
        }
    });
    
    // Course change handler - reload page with session and course
    $('#selected_course').on('change', function() {
        var courseId = $(this).val();
        var sessionId = $('#session_id').val() || 0;
        if (courseId && courseId != '0') {
            window.location.href = '" . api_get_self() . "?session_id=' + sessionId + '&selected_course=' + courseId;
        } else if (sessionId && sessionId != '0') {
            window.location.href = '" . api_get_self() . "?session_id=' + sessionId;
        } else {
            window.location.href = '" . api_get_self() . "';
        }
    });
});
</script>";

// Form creation
$form = new FormValidator('export_all_results_form', 'POST');
$form->addHeader(get_lang('Export all exercise results'));

// Add form elements with Select2
$form->addSelect(
    'session_id',
    get_lang('Session'),
    $sessionSelectList,
    [
        'id' => 'session_id',
        'class' => 'select2 form-control'
    ]
)->setSelected($sessionId);

if (empty($sessionId) || $sessionId == 0) {
    $form->addSelect(
        'selected_course',
        get_lang('Course'),
        $courseSelectList,
        [
            'id' => 'selected_course',
            'class' => 'select2 form-control',
            'disabled' => true
        ]
    );
} else {
    $form->addSelect(
        'selected_course',
        get_lang('Course'),
        $courseSelectList,
        [
            'id' => 'selected_course',
            'class' => 'select2 form-control',
        ]
    )->setSelected($courseId);
}

if (empty($courseId) || $courseId == 0) {
    $form->addSelect(
        'exerciseId',
        get_lang('Exercise'),
        $exerciseSelectList,
        [
            'id' => 'exerciseId',
            'class' => 'select2 form-control',
            'disabled' => true
        ]
    );
} else {
    $form->addSelect(
        'exerciseId',
        get_lang('Exercise'),
        $exerciseSelectList,
        [
            'id' => 'exerciseId',
            'class' => 'select2 form-control',
        ]
    )->setSelected($exerciseId);
}

// Date filters
$form->addDateTimePicker('start_date', get_lang('Start date'));
$form->addDateTimePicker('end_date', get_lang('End date'));

// Validation rules
$form->addRule('session_id', get_lang('Required field'), 'required');
$form->addRule('selected_course', get_lang('Required field'), 'required');
$form->addRule('exerciseId', get_lang('Required field'), 'required');

// Export button
$form->addButtonExport(get_lang('Export'), 'submit');

// Process form submission
if ($form->validate()) {
    $values = $form->getSubmitValues();

    $sessionId = (int) $values['session_id'];
    $courseId = (int) $values['selected_course'];
    $exerciseId = (int) $values['exerciseId'];

    $filterDates = [
        'start_date' => (!empty($values['start_date']) ? $values['start_date'] : ''),
        'end_date' => (!empty($values['end_date']) ? $values['end_date'] : ''),
    ];

    try {
        ExerciseLib::exportExerciseAllResultsZip($sessionId, $courseId, $exerciseId, $filterDates);
    } catch (Exception $e) {
        Display::addFlash(Display::return_message(get_lang('Export failed') . ': ' . $e->getMessage(), 'error'));
        error_log('Exercise export error: ' . $e->getMessage());
    }
}

Display::display_header($toolName);

$form->display();

Display::display_footer();
