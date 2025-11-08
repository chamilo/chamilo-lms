<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;

$cidReset = true;
require_once __DIR__ . '/../inc/global.inc.php';

api_protect_admin_script(true);

$this_section = SECTION_PLATFORM_ADMIN;

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$toolName = get_lang('Export all test results');

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------
/**
 * Sends a JSON response and exits.
 */
function send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Normalize a label for Select/Select2:
 * - Decode HTML entities (e.g., &eacute; → é)
 * - Strip HTML tags (safety)
 * - Collapse newlines/tabs/spaces into single spaces
 * - Trim edges
 */
function clean_label(string $label): string
{
    // Decode entities to real UTF-8 characters
    $decoded = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Remove any HTML tags just in case
    $decoded = strip_tags($decoded);

    // Replace CR/LF/TAB by single spaces and collapse multiple spaces
    $decoded = preg_replace('/\s+/u', ' ', $decoded ?? '');

    return trim($decoded ?? '');
}

/**
 * Send a minimal HTML page to the iframe that forwards a message to the parent.
 * This avoids breaking binary downloads while still surfacing errors to the user.
 */
function iframe_post_message(string $message, bool $ok = false): void
{
    // Keep it extremely small; no BOM; no extra whitespace.
    echo '<!doctype html><meta charset="utf-8"><script>try{parent.postMessage({type:"export-status",ok:'
        . ($ok ? 'true' : 'false')
        . ',message:"' . addslashes($message) . '"},"*");}catch(e){}</script>';
    exit;
}

// -----------------------------------------------------------------------------
// Services / repositories
// -----------------------------------------------------------------------------
$user            = api_get_user_entity(api_get_user_id());
$sessionRepo     = Container::getSessionRepository();
$courseRepo      = Container::getCourseRepository();
$cQuizRepo       = Container::getQuizRepository();
$isPlatformAdmin = api_is_platform_admin();

// -----------------------------------------------------------------------------
// AJAX endpoints (no full-page reload).
// -----------------------------------------------------------------------------
if (isset($_GET['ajax'])) {
    if (!api_is_allowed_to_edit(null, true)) {
        send_json(['ok' => false, 'message' => get_lang('NotAllowed')], 403);
    }

    $ajax = (string) $_GET['ajax'];

    try {
        if ($ajax === 'courses') {
            $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
            $options = [];

            if ($sessionId > 0) {
                $session = $sessionRepo->find($sessionId);
                if ($session) {
                    foreach ($session->getCourses() as $sessionCourse) {
                        $course = $sessionCourse->getCourse();
                        $options[] = [
                            'id'   => (int) $course->getId(),
                            'text' => clean_label((string) $course->getTitle()), // decode + normalize
                        ];
                    }
                }
            } else {
                if ($isPlatformAdmin) {
                    if (class_exists('CourseManager') && method_exists('CourseManager', 'get_courses_list')) {
                        $all = CourseManager::get_courses_list(0, 0, 'title');
                        foreach ($all as $c) {
                            $options[] = [
                                'id'   => (int) $c['real_id'],
                                'text' => clean_label((string) $c['title']),
                            ];
                        }
                    } else {
                        foreach ($courseRepo->findAll() as $courseEntity) {
                            $options[] = [
                                'id'   => (int) $courseEntity->getId(),
                                'text' => clean_label((string) $courseEntity->getTitle()),
                            ];
                        }
                    }
                } else {
                    if (class_exists('CourseManager') && method_exists('CourseManager', 'get_course_list_of_user_as_course_admin')) {
                        $mine = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
                        foreach ($mine as $c) {
                            $options[] = [
                                'id'   => (int) $c['real_id'],
                                'text' => clean_label((string) $c['title']),
                            ];
                        }
                    }
                }
            }

            usort($options, static fn($a, $b) => strcasecmp($a['text'], $b['text']));

            send_json(['ok' => true, 'options' => $options]);
        }

        if ($ajax === 'exercises') {
            $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
            $courseId  = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : 0;

            if ($courseId <= 0) {
                send_json(['ok' => true, 'options' => []]);
            }

            $course  = $courseRepo->find($courseId);
            $session = $sessionId > 0 ? $sessionRepo->find($sessionId) : null;

            if (!$course) {
                send_json(['ok' => true, 'options' => []]);
            }

            $qb     = $cQuizRepo->findAllByCourse($course, $session);
            $exList = $qb->getQuery()->getResult();

            $options = [];
            foreach ($exList as $ex) {
                /** @var \Chamilo\CourseBundle\Entity\CQuiz $ex */
                $options[] = [
                    'id'   => (int) $ex->getIid(),
                    'text' => clean_label((string) $ex->getTitle()), // decode + normalize
                ];
            }

            usort($options, static fn($a, $b) => strcasecmp($a['text'], $b['text']));

            send_json(['ok' => true, 'options' => $options]);
        }

        send_json(['ok' => false, 'message' => 'Unknown action'], 400);
    } catch (Throwable $e) {
        error_log('[export_exercise_results AJAX] '.$e->getMessage());
        send_json(['ok' => false, 'message' => 'Server error'], 500);
    }
}

// -----------------------------------------------------------------------------
// Request parameters (for initial render / submit)
// -----------------------------------------------------------------------------
$sessionId  = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;
$courseId   = isset($_REQUEST['selected_course']) ? (int) $_REQUEST['selected_course'] : 0;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;

// -----------------------------------------------------------------------------
// Initial lists (first paint) – apply same normalization to avoid mismatches
// -----------------------------------------------------------------------------
$sessionSelectList = [0 => get_lang('Select')];
try {
    $sessionList = $isPlatformAdmin
        ? $sessionRepo->findAll()
        : $sessionRepo->getSessionsByUser($user, api_get_url_entity())->getQuery()->getResult();

    foreach ($sessionList as $s) {
        $sessionSelectList[$s->getId()] = clean_label((string) $s->getTitle()); // decode + normalize
    }
} catch (Exception $e) {
    error_log('[export_exercise_results] Error loading sessions: '.$e->getMessage());
    $sessionSelectList = [0 => get_lang('Error loading sessions')];
}

$courseSelectList = [0 => get_lang('Select')];
try {
    if ($sessionId > 0) {
        $session = $sessionRepo->find($sessionId);
        if ($session) {
            foreach ($session->getCourses() as $sc) {
                $course = $sc->getCourse();
                $courseSelectList[(int) $course->getId()] = clean_label((string) $course->getTitle()); // decode
            }
        }
    } else {
        if ($isPlatformAdmin) {
            if (class_exists('CourseManager') && method_exists('CourseManager', 'get_courses_list')) {
                $all = CourseManager::get_courses_list(0, 0, 'title');
                foreach ($all as $c) {
                    $courseSelectList[(int) $c['real_id']] = clean_label((string) $c['title']); // decode
                }
            } else {
                foreach ($courseRepo->findAll() as $c) {
                    $courseSelectList[(int) $c->getId()] = clean_label((string) $c->getTitle()); // decode
                }
            }
        } else {
            if (class_exists('CourseManager') && method_exists('CourseManager', 'get_course_list_of_user_as_course_admin')) {
                $mine = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
                foreach ($mine as $c) {
                    $courseSelectList[(int) $c['real_id']] = clean_label((string) $c['title']); // decode
                }
            }
        }
    }
} catch (Exception $e) {
    error_log('[export_exercise_results] Error loading courses: '.$e->getMessage());
    $courseSelectList = [0 => get_lang('Error loading courses')];
}

$exerciseSelectList = [0 => get_lang('Select')];
if ($courseId > 0) {
    try {
        $courseEntity  = $courseRepo->find($courseId);
        $sessionEntity = $sessionId > 0 ? $sessionRepo->find($sessionId) : null;

        if ($courseEntity) {
            $qb = $cQuizRepo->findAllByCourse($courseEntity, $sessionEntity);
            $exerciseList = $qb->getQuery()->getResult();

            foreach ($exerciseList as $ex) {
                /** @var CQuiz $ex */
                $exerciseSelectList[(int) $ex->getIid()] = clean_label((string) $ex->getTitle()); // decode
            }
        }
    } catch (Exception $e) {
        error_log('[export_exercise_results] Error loading exercises: '.$e->getMessage());
        $exerciseSelectList = [0 => get_lang('Error loading tests')];
    }
}

// -----------------------------------------------------------------------------
// Head extras: visual polish + AJAX logic + overlay + iframe messaging
// -----------------------------------------------------------------------------
$htmlHeadXtra[] = "
<style>
/* Select2 polish for consistent height */
.select2-selection, .select2-selection__rendered { height: 38px !important; line-height: 38px !important; }
.select2-container .select2-selection--single .select2-selection__arrow { height: 38px !important; }

/* Tiny loading badges near labels */
.badge { display: inline-block; font-size: 12px; border-radius: 12px; padding: 4px 8px; background: #eef2ff; color: #374151; margin-left: 8px; }
.hidden { display: none !important; }

/* Export overlay shown only while exporting */
#export-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,0.55); backdrop-filter: blur(2px);
  display: none; align-items: center; justify-content: center;
}
#export-overlay .box {
  background: #fff; border-radius: 14px; padding: 22px 28px; min-width: 280px;
  box-shadow: 0 10px 30px rgba(0,0,0,.15); text-align: center;
}
#export-overlay .spinner {
  width: 36px; height: 36px; margin: 0 auto 12px auto; border-radius: 50%;
  border: 4px solid #e5e7eb; border-top-color: #4f46e5; animation: spin 0.9s linear infinite;
}
#export-overlay .label { font-weight: 600; color: #111827; margin-bottom: 2px; }
#export-overlay .hint  { font-size: 13px; color: #6b7280; }
@keyframes spin { to { transform: rotate(360deg);} }
</style>
<script>
$(function () {
    // Initialize Select2 if available
    if ($.fn && $.fn.select2) {
        $('.select2').select2({ width: '100%', placeholder: '".addslashes(get_lang('Select'))."', allowClear: true });
    }

    const \$session   = $('#session_id');
    const \$course    = $('#selected_course');
    const \$exercise  = $('#exerciseId');

    const \$loadingCourses   = $('<span id=\"loading-courses\" class=\"badge hidden\">".addslashes(get_lang('Loading'))."...</span>');
    const \$loadingExercises = $('<span id=\"loading-exercises\" class=\"badge hidden\">".addslashes(get_lang('Loading'))."...</span>');

    // Add small loading badges next to selects
    \$session.closest('.form-group').find('label').append($('<span/>'));
    \$course.closest('.form-group').find('label').append(\$loadingCourses);
    \$exercise.closest('.form-group').find('label').append(\$loadingExercises);

    // Utility: refill a select with [{id, text}]
    function refillSelect(\$select, items, opts) {
        const keepValue = opts && opts.keepValue === true;
        const prev = keepValue ? \$select.val() : null;

        // Wipe options
        \$select.empty();

        // Placeholder first option
        const placeholder = opts && opts.placeholder ? opts.placeholder : '".addslashes(get_lang('Select'))."';
        \$select.append(new Option(placeholder, '0', false, false));

        // Add items
        if (Array.isArray(items)) {
            for (const it of items) {
                \$select.append(new Option(it.text, it.id, false, false));
            }
        }

        // Re-apply Select2
        if (\$select.data('select2')) {
            \$select.trigger('change.select2');
        }

        // Try to preserve previous value if still exists
        if (keepValue && prev && \$select.find('option[value=\"'+prev+'\"]').length) {
            \$select.val(prev).trigger('change');
        } else {
            \$select.val('0').trigger('change');
        }
    }

    // Disable a select with friendly UX
    function disableSelect(\$select, disabled) {
        \$select.prop('disabled', !!disabled);
        if (\$select.data('select2')) { \$select.select2(); }
    }

    // Fetch courses via AJAX
    function loadCourses(sessionId) {
        \$loadingCourses.removeClass('hidden');
        disableSelect(\$course, true);
        disableSelect(\$exercise, true);
        refillSelect(\$exercise, [], { placeholder: '".addslashes(get_lang('First select a course'))."' });

        $.getJSON('".addslashes(api_get_self())."', { ajax: 'courses', session_id: sessionId })
            .done(function(resp) {
                if (resp && resp.ok) {
                    refillSelect(\$course, resp.options || [], { placeholder: '".addslashes(get_lang('Select'))."' });
                    disableSelect(\$course, false);
                } else {
                    refillSelect(\$course, [], { placeholder: '".addslashes(get_lang('Error loading courses'))."' });
                }
            })
            .fail(function() {
                refillSelect(\$course, [], { placeholder: '".addslashes(get_lang('Error loading courses'))."' });
            })
            .always(function() {
                \$loadingCourses.addClass('hidden');
            });
    }

    // Fetch exercises via AJAX
    function loadExercises(sessionId, courseId) {
        \$loadingExercises.removeClass('hidden');
        disableSelect(\$exercise, true);

        $.getJSON('".addslashes(api_get_self())."', { ajax: 'exercises', session_id: sessionId, selected_course: courseId })
            .done(function(resp) {
                if (resp && resp.ok) {
                    refillSelect(\$exercise, resp.options || [], { placeholder: '".addslashes(get_lang('Select'))."'} );
                    disableSelect(\$exercise, false);
                } else {
                    refillSelect(\$exercise, [], { placeholder: '".addslashes(get_lang('Error loading tests'))."' });
                }
            })
            .fail(function() {
                refillSelect(\$exercise, [], { placeholder: '".addslashes(get_lang('Error loading tests'))."' });
            })
            .always(function() {
                \$loadingExercises.addClass('hidden');
            });
    }

    // On session change → load courses and reset exercises
    \$session.on('change', function() {
        const sId = parseInt(\$session.val() || '0', 10) || 0;
        loadCourses(sId);
    });

    // On course change → load exercises
    \$course.on('change', function() {
        const sId = parseInt(\$session.val() || '0', 10) || 0;
        const cId = parseInt(\$course.val() || '0', 10) || 0;
        if (cId > 0) {
            loadExercises(sId, cId);
        } else {
            refillSelect(\$exercise, [], { placeholder: '".addslashes(get_lang('First select a course'))."' });
            disableSelect(\$exercise, true);
        }
    });

    // --- Export overlay + iframe target (no page reload) ---
    const \$overlay = $(
      '<div id=\"export-overlay\">' +
        '<div class=\"box\">' +
          '<div class=\"spinner\"></div>' +
          '<div class=\"label\">".addslashes(get_lang('Please wait this could take a while'))."</div>' +
          '<div class=\"hint\">".addslashes(get_lang('Generating file, do not close this tab'))."</div>' +
        '</div>' +
      '</div>'
    );
    $('body').append(\$overlay);

    const \$form = $('form[name=\"export_all_results_form\"]');
    \$form.attr('target', 'export_iframe'); // Send to hidden iframe to keep page intact

    // Ensure hidden token input exists
    if ($('input[name=\"download_token\"]').length === 0) {
        \$form.append('<input type=\"hidden\" name=\"download_token\" value=\"\" />');
    }
    const \$tokenInput = $('input[name=\"download_token\"]');

    // Cookie helpers
    function getCookie(name) {
        const value = ('; ' + document.cookie).split('; ' + name + '=');
        if (value.length === 2) return value.pop().split(';').shift();
        return null;
    }
    function deleteCookie(name) {
        document.cookie = name + '=; Max-Age=0; path=/';
    }

    // Listen for error/info messages coming from the iframe (server-side postMessage)
    window.addEventListener('message', function(ev){
        if (!ev || !ev.data) return;
        if (ev.data.type === 'export-status' && ev.data.message) {
            // Ensure overlay is closed and button is re-enabled even if no cookie arrives.
            $('#export-overlay').hide();
            \$form.find('button[type=\"submit\"]').prop('disabled', false);
            if (window.__exportCookieInterval) { clearInterval(window.__exportCookieInterval); window.__exportCookieInterval = null; }
            window.__exportSafetyTimer && clearTimeout(window.__exportSafetyTimer);

            alert(ev.data.message);
        }
    });

    // Show overlay on submit + disable button and start cookie polling
    \$form.on('submit', function() {
        // Generate a unique token per export
        const token = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
        \$tokenInput.val(token);

        $('#export-overlay').css('display','flex');
        $(this).find('button[type=\"submit\"]').prop('disabled', true);

        // Poll for server-set cookie to know the download has started
        window.__exportCookieInterval && clearInterval(window.__exportCookieInterval);
        window.__exportCookieInterval = setInterval(function() {
            var c = getCookie('download_token');
            if (c === token) {
                // Hide overlay and cleanup once cookie matches
                $('#export-overlay').hide();
                \$form.find('button[type=\"submit\"]').prop('disabled', false);
                clearInterval(window.__exportCookieInterval);
                window.__exportCookieInterval = null;
                deleteCookie('download_token');
                window.__exportSafetyTimer && clearTimeout(window.__exportSafetyTimer);
            }
        }, 400);

        // Safety timeout: hide overlay after 120s in case of unexpected failures
        window.__exportSafetyTimer && clearTimeout(window.__exportSafetyTimer);
        window.__exportSafetyTimer = setTimeout(function(){
          $('#export-overlay').hide();
          \$form.find('button[type=\"submit\"]').prop('disabled', false);
          if (window.__exportCookieInterval) { clearInterval(window.__exportCookieInterval); window.__exportCookieInterval = null; }
        }, 120000);
    });

    // Also hide overlay when iframe finishes (for error pages or non-download responses)
    $('#export_iframe').on('load', function() {
        $('#export-overlay').hide();
        \$form.find('button[type=\"submit\"]').prop('disabled', false);
        window.__exportSafetyTimer && clearTimeout(window.__exportSafetyTimer);
        if (window.__exportCookieInterval) { clearInterval(window.__exportCookieInterval); window.__exportCookieInterval = null; }
    });

    // First paint: if course is selected, ensure exercises are filled accurately via AJAX
    (function bootstrapAjaxFill() {
        const sId = parseInt(\$session.val() || '0', 10) || 0;
        const cId = parseInt(\$course.val() || '0', 10) || 0;

        if (sId > 0) {
            // Sync courses list with the selected session (keeps selected value if present)
            \$loadingCourses.removeClass('hidden');
            $.getJSON('".addslashes(api_get_self())."', { ajax: 'courses', session_id: sId })
                .done(function(resp) {
                    if (resp && resp.ok) {
                        refillSelect(\$course, resp.options || [], { keepValue: true });
                    }
                })
                .always(function(){ \$loadingCourses.addClass('hidden'); });
        }
        if (cId > 0) {
            loadExercises(sId, cId);
        } else {
            disableSelect(\$exercise, true);
        }
    })();
});
</script>
";

// -----------------------------------------------------------------------------
// Form (still POST; AJAX only handles dependent selects without reload)
// -----------------------------------------------------------------------------
$form = new FormValidator('export_all_results_form', 'POST');
$form->addHeader($toolName);

// Session
$form->addSelect(
    'session_id',
    get_lang('Session'),
    $sessionSelectList,
    [
        'id'    => 'session_id',
        'class' => 'select2 form-control',
    ]
)->setSelected($sessionId);

// Course
$form->addSelect(
    'selected_course',
    get_lang('Course'),
    $courseSelectList ?: [0 => get_lang('Select')],
    [
        'id'    => 'selected_course',
        'class' => 'select2 form-control',
    ]
)->setSelected($courseId);

// Exercise
if ($courseId === 0) {
    $form->addSelect(
        'exerciseId',
        get_lang('Test'),
        [0 => get_lang('First select a course')],
        [
            'id'       => 'exerciseId',
            'class'    => 'select2 form-control',
            'disabled' => true,
        ]
    );
} else {
    $form->addSelect(
        'exerciseId',
        get_lang('Test'),
        $exerciseSelectList ?: [0 => get_lang('Select')],
        [
            'id'    => 'exerciseId',
            'class' => 'select2 form-control',
        ]
    )->setSelected($exerciseId);
}

// Date filters
$form->addDateTimePicker('start_date', get_lang('Start date'));
$form->addDateTimePicker('end_date', get_lang('End date'));

// Validation
$form->addRule('start_date', get_lang('Invalid date'), 'datetime');
$form->addRule('end_date', get_lang('Invalid date'), 'datetime');
$form->addRule(['start_date','end_date'], get_lang('StartDateShouldBeBeforeEndDate'), 'date_compare', 'lte');

// Required (session can be 0)
$form->addRule('selected_course', get_lang('Required field'), 'required');
$form->addRule('exerciseId', get_lang('Required field'), 'required');

// Hidden download token (will be filled by JS just-in-time)
$form->addHidden('download_token', '');

// Export button
$form->addButtonExport(get_lang('Export'), 'submit');

// -----------------------------------------------------------------------------
// Submit
// -----------------------------------------------------------------------------
if ($form->validate()) {
    $values     = $form->getSubmitValues();
    $sessionId  = isset($values['session_id']) ? (int) $values['session_id'] : 0;
    $courseId   = isset($values['selected_course']) ? (int) $values['selected_course'] : 0;
    $exerciseId = isset($values['exerciseId']) ? (int) $values['exerciseId'] : 0;

    // Download token cookie handshake to reliably hide the overlay in JS
    $downloadToken = isset($values['download_token']) ? (string) $values['download_token'] : '';
    if ($downloadToken !== '') {
        // Set cookie just before starting the export; JS polls for this exact value
        // Use modern options when available; fall back to legacy signature if needed.
        if (PHP_VERSION_ID >= 70300) {
            setcookie(
                'download_token',
                $downloadToken,
                [
                    'expires'  => 0,
                    'path'     => '/',
                    'secure'   => !empty($_SERVER['HTTPS']),
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]
            );
        } else {
            setcookie('download_token', $downloadToken, 0, '/');
        }
    }

    // Early validation error → notify parent (page) via postMessage; do not use Flash in iframe.
    if ($courseId === 0 || $exerciseId === 0) {
        iframe_post_message(get_lang('Required field'), false);
    } else {
        $filterDates = [
            'start_date' => !empty($values['start_date']) ? $values['start_date'] : '',
            'end_date'   => !empty($values['end_date'])   ? $values['end_date']   : '',
        ];

        try {
            // Keep existing behavior: sends ZIP download or returns false when nothing to export
            $result = ExerciseLib::exportExerciseAllResultsZip($sessionId, $courseId, $exerciseId, $filterDates);

            // If library signals "no results", inform parent page (alert) via postMessage.
            if ($result === false) {
                iframe_post_message(get_lang('No result found for export in this test.'), false);
            }
            // If the method streams and exits on success, we never reach here. That's fine.
        } catch (Exception $e) {
            error_log('[export_exercise_results] Export error: ' . $e->getMessage());
            iframe_post_message(sprintf(get_lang('Export failed: %s'), $e->getMessage()), false);
        }
    }
}

// -----------------------------------------------------------------------------
// Render
// -----------------------------------------------------------------------------
Display::display_header($toolName);

// Hidden iframe to capture the file download response (avoid page reload)
echo '<iframe id="export_iframe" name="export_iframe" class="hidden" style="width:0;height:0;border:0;"></iframe>';

// Do NOT show the static waiting message permanently; the overlay appears only on submit.
$form->display();

Display::display_footer();
