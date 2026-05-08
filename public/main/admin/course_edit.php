<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$em = Database::getManager();
$courseCategoriesRepo = Container::getCourseCategoryRepository();
$illustrationRepo = Container::getIllustrationRepository();

$urlId = api_get_current_access_url_id();

$courseId = $_GET['id'] ?? null;
$currentView = $_GET['view'] ?? 'general';

if (empty($courseId)) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info_by_id($courseId);
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$courseCode = $courseInfo['code'];

$tool_name = get_lang('Edit course information');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => '/admin/course-list', 'name' => get_lang('Course list')];

// Get all course categories
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$course_code = $courseInfo['code'];
$courseId = $courseInfo['real_id'];
$courseEntityForDefaults = api_get_course_entity($courseId);

// Get course teachers
$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$sql = "SELECT user.id as user_id,lastname,firstname
        FROM
            $table_user as user,
            $table_course_user as course_user
        WHERE
            course_user.status='1' AND
            course_user.user_id=user.id AND
            course_user.c_id ='".$courseId."'".
        $order_clause;
$res = Database::query($sql);
$course_teachers = [];
while ($obj = Database::fetch_object($res)) {
    $course_teachers[] = $obj->user_id;
}

// Get all possible teachers without the course teachers
if (api_is_multiple_url_enabled()) {
    $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $sql = "SELECT u.id as user_id,lastname,firstname
            FROM $table_user as u
            INNER JOIN $access_url_rel_user_table url_rel_user
            ON (u.id=url_rel_user.user_id)
            WHERE
                url_rel_user.access_url_id = $urlId AND
                status = 1".$order_clause;
} else {
    $sql = "SELECT id as user_id, lastname, firstname
            FROM $table_user WHERE status='1'".$order_clause;
}
$courseInfo['tutor_name'] = null;

$res = Database::query($sql);
$teachers = [];
$allTeachers = [];
$platform_teachers[0] = '-- '.get_lang('No administrator').' --';
while ($obj = Database::fetch_object($res)) {
    $allTeachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    if (!array_key_exists($obj->user_id, $course_teachers)) {
        $teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    }

    if (isset($course_teachers[$obj->user_id]) &&
        $courseInfo['tutor_name'] == $course_teachers[$obj->user_id]
    ) {
        $courseInfo['tutor_name'] = $obj->user_id;
    }
    // We add in the array platform teachers
    $platform_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Case where there is no teacher in the course
if (0 == count($course_teachers)) {
    $sql = 'SELECT tutor_name FROM '.$course_table.' WHERE code="'.$course_code.'"';
    $res = Database::query($sql);
    $tutor_name = Database::result($res, 0, 0);
    $courseInfo['tutor_name'] = array_search($tutor_name, $platform_teachers);
}

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?id='.$courseId
);
$form->addElement('header', get_lang('Course').'  #'.$courseInfo['real_id'].' '.$course_code);
$form->addElement('hidden', 'code', $course_code);

//title
$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$element = $form->addElement(
    'text',
    'real_code',
    [get_lang('Code'), get_lang('This value can\'t be changed.')]
);
$element->freeze();

// Visual code
$form->addText(
    'visual_code',
    [
        get_lang('visual code'),
        get_lang('Only letters (a-z) and numbers (0-9)'),
        get_lang('This value is used in the course URL'),
    ],
    true,
    [
        'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
        'pattern' => '[a-zA-Z0-9]+',
        'title' => get_lang('Only letters (a-z) and numbers (0-9)'),
    ]
);

$form->applyFilter('visual_code', 'strtoupper');
$form->applyFilter('visual_code', 'html_filter');

// Course picture preview and upload
$hasCustomCoursePicture = $illustrationRepo->hasIllustration($courseEntityForDefaults);
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntityForDefaults, 'course_picture_medium');
$allowed_picture_types = api_get_supported_image_extensions(false);
$acceptedPictureTypes = implode(
    ',',
    array_map(
        static fn (string $extension): string => '.'.$extension,
        $allowed_picture_types
    )
);

$pictureStatusLabel = $hasCustomCoursePicture ? get_lang('Current picture') : get_lang('Default');
$pictureStatusClasses = $hasCustomCoursePicture
    ? 'bg-success text-white'
    : 'bg-gray-20 text-gray-90';

$deleteCoursePictureButton = '';
if ($hasCustomCoursePicture) {
    $deleteCoursePictureButton = '
        <button
            type="submit"
            name="delete_picture"
            value="1"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-danger bg-white text-danger transition hover:bg-support-6"
            title="'.get_lang('Delete picture').'"
            aria-label="'.get_lang('Delete picture').'"
        >
            '.Display::getMdiIcon(
            ActionIcon::DELETE,
            'ch-tool-icon text-danger',
            null,
            ICON_SIZE_SMALL,
            get_lang('Delete picture')
        ).'
        </button>';
}

$form->addHtml('
    <div id="course-picture-card" class="my-6 rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-start">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 lg:col-span-3">
                <div class="mb-3 flex items-center gap-2">
                    <span class="mdi mdi-image-outline ch-tool-icon"></span>
                    <h4 class="m-0 text-body-1 font-semibold text-gray-90">'.get_lang('Course picture').'</h4>
                </div>

                <div
                    id="course-picture-input-target"
                    class="min-h-24 rounded-2xl border border-dashed border-support-3 bg-white p-4"
                >
                    <p class="m-0 mb-2 text-body-2 font-semibold text-gray-90">'.get_lang('Add image').'</p>
                    <p class="m-0 mb-3 text-caption text-gray-50">
                        '.get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowed_picture_types).')
                    </p>
');

// Keep the file element registered directly in QuickForm.
// The surrounding HTML keeps the input in the visual card without moving it with JavaScript.
$form->addFile(
    'picture',
    '',
    [
        'id' => 'picture',
        'class' => 'picture-form block w-full cursor-pointer rounded-xl border border-gray-25 bg-white text-body-2 text-gray-90 file:mr-4 file:border-0 file:bg-primary file:px-4 file:py-2 file:text-body-2 file:font-semibold file:text-white hover:file:bg-primary-gradient',
        'crop_image' => true,
        'accept' => $acceptedPictureTypes,
    ]
);

$form->addHtml('
                </div>

                <p
                    id="course-picture-selected-file"
                    class="mt-3 hidden text-body-2 font-semibold text-primary"
                ></p>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-3 lg:col-span-1">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="text-body-2 font-semibold text-gray-90">'.get_lang('Preview').'</span>
                    <span class="flex items-center gap-2">
                        <span
                            id="course-picture-status"
                            class="inline-flex items-center rounded-full px-3 py-1 text-caption font-semibold '.$pictureStatusClasses.'"
                        >
                            '.$pictureStatusLabel.'
                        </span>
                        '.$deleteCoursePictureButton.'
                    </span>
                </div>

                <div class="aspect-video overflow-hidden rounded-xl border border-gray-25 bg-white">
                    <img
                        id="course-picture-preview-image"
                        class="block h-full w-full object-cover"
                        src="'.htmlspecialchars($illustrationUrl, ENT_QUOTES | ENT_SUBSTITUTE).'"
                        alt="'.get_lang('Course picture').'"
                    />
                </div>
            </div>
        </div>
    </div>
');

$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);

$allowBaseCourseCategory = ('true' === api_get_setting('course.allow_base_course_category'));
$categories = $courseCategoriesRepo->getCategoriesByCourseIdAndAccessUrlId(
    $urlId,
    $courseId,
    $allowBaseCourseCategory
);

$courseCategoryNames = [];
$courseCategoryIds = [];

foreach ($categories as $category) {
    $courseCategoryNames[$category->getId()] = $category->getTitle();
    $courseCategoryIds[] = $category->getId();
}

$form->addSelectAjax(
    'course_categories',
    get_lang('Categories'),
    $courseCategoryNames,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category',
        'multiple' => 'multiple',
    ]
);
$courseInfo['course_categories'] = $courseCategoryIds;

$courseTeacherNames = [];
foreach ($course_teachers as $courseTeacherId) {
    $courseTeacher = api_get_user_entity($courseTeacherId);
    $courseTeacherNames[$courseTeacher->getId()] = UserManager::formatUserFullName($courseTeacher, true);
}

$form->addSelectAjax(
    'course_teachers',
    get_lang('Teachers'),
    $courseTeacherNames,
    ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=teacher_to_basis_course', 'multiple' => 'multiple']
);
$courseInfo['course_teachers'] = $course_teachers;
if (array_key_exists('add_teachers_to_sessions_courses', $courseInfo)) {
    $form->addCheckBox(
        'add_teachers_to_sessions_courses',
        null,
        get_lang('Teachers will be added as a coach in all course sessions.')
    );
}

$allowEditSessionCoaches = ('false' === api_get_setting('workflows.disabled_edit_session_coaches_course_editing_course'));
$coursesInSession = SessionManager::get_session_by_course($courseInfo['real_id']);
if (!empty($coursesInSession) && $allowEditSessionCoaches) {
    foreach ($coursesInSession as $session) {
        $sessionId = $session['id'];
        $coaches = SessionManager::getCoachesByCourseSession(
            $sessionId,
            $courseInfo['real_id']
        );
        $teachers = $allTeachers;

        $sessionTeachers = [];
        foreach ($coaches as $coachId) {
            $sessionTeachers[] = $coachId;

            if (isset($teachers[$coachId])) {
                unset($teachers[$coachId]);
            }
        }

        $groupName = 'session_coaches_'.$sessionId;
        $sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;
        $form->addMultiSelect(
            $groupName,
            Display::url(
                $session['title'],
                $sessionUrl,
                ['target' => '_blank']
            ).' - '.get_lang('Coaches'),
            $allTeachers
        );
        $courseInfo[$groupName] = $sessionTeachers;
    }
}

$form->addText('department_name', get_lang('Department'), false, ['size' => '60']);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('Department URL'), false, ['size' => '60']);
$form->applyFilter('department_url', 'html_filter');
$form->applyFilter('department_url', 'trim');

$form->addSelectLanguage('course_language', get_lang('Course language'));

// Room.
$em = Database::getManager();
$roomCount = $em->getRepository(\Chamilo\CoreBundle\Entity\Room::class)->count([]);
if ($roomCount > 0) {
    $roomOptions = [];
    if ($courseEntityForDefaults && $courseEntityForDefaults->getRoom()) {
        $currentRoom = $courseEntityForDefaults->getRoom();
        $branch = $currentRoom->getBranch();
        $roomLabel = $branch ? $branch->getTitle().' - '.$currentRoom->getTitle() : $currentRoom->getTitle();
        $roomOptions[$currentRoom->getId()] = $roomLabel;
        $courseInfo['room_id'] = $currentRoom->getId();
    }
    $form->addSelectAjax(
        'room_id',
        get_lang('Default room'),
        $roomOptions,
        [
            'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_room',
            'placeholder' => get_lang('Select'),
        ]
    );
}

CourseManager::addVisibilityOptions($form);

$group = [];
$group[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[] = $form->createElement('radio', 'subscribe', null, get_lang('This function is only available to trainers'), 0);
$form->addGroup($group, null, get_lang('Subscription'));

$group = [];
$group[] = $form->createElement('radio', 'unsubscribe', get_lang('Unsubscribe'), get_lang('Users are allowed to unsubscribe from this course'), 1);
$group[] = $form->createElement('radio', 'unsubscribe', null, get_lang('Users are not allowed to unsubscribe from this course'), 0);
$form->addGroup($group, null, get_lang('Unsubscribe'));

$form->addElement('text', 'disk_quota', [get_lang('Disk Space'), null, get_lang('MB')]);
$form->addRule('disk_quota', get_lang('Required field'), 'required');
$form->addRule('disk_quota', get_lang('This field should be numeric'), 'numeric');

$form->addText('video_url', get_lang('Video URL'), false);
$form->addCheckBox('sticky', null, get_lang('Special course'));

if ('true' === api_get_setting('course.show_course_duration')) {
    $form->addElement('text', 'duration', get_lang('Duration (minutes)'), [
        'id' => 'duration',
        'maxlength' => 10,
    ]);
    $form->addRule('duration', get_lang('This field should be numeric'), 'numeric');
}

// Extra fields
$extraField = new ExtraField('course');
$extra = $extraField->addElements(
    $form,
    $courseId,
    [],
    false,
    false,
    [],
    [],
    [],
    false,
    true
);

if ('true' === api_get_setting('course.multiple_access_url_show_shared_course_marker')) {
    $urls = UrlManager::get_access_url_from_course($courseId);
    $urlToString = '';
    foreach ($urls as $url) {
        $urlToString .= $url['url'].'<br />';
    }
    $form->addLabel('URLs', $urlToString);
}
$htmlHeadXtra[] = '
<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$htmlHeadXtra[] = '
<script>
document.addEventListener("DOMContentLoaded", function () {
    var card = document.getElementById("course-picture-card");
    var input = document.getElementById("picture");
    var previewImage = document.getElementById("course-picture-preview-image");
    var selectedFile = document.getElementById("course-picture-selected-file");
    var status = document.getElementById("course-picture-status");
    var currentPictureLabel = '.json_encode(get_lang('Current picture')).';
    var objectUrl = null;
    var originalImage = null;
    var lastCropValue = "";
    var syncTimer = null;

    function markAsCurrentPicture() {
        if (!status) {
            return;
        }

        status.textContent = currentPictureLabel;
        status.classList.remove(
            "bg-gray-20",
            "text-gray-90",
            "bg-success"
        );
        status.classList.add(
            "bg-primary",
            "text-white"
        );
    }

    function updateSelectedFileName(file) {
        if (!selectedFile) {
            return;
        }

        selectedFile.textContent = file.name;
        selectedFile.classList.remove("hidden");
    }

    function getCropInput() {
        return document.querySelector(
            "input[name=\"picture_crop_result\"], textarea[name=\"picture_crop_result\"], #picture_crop_result"
        );
    }

    function parseCropValue(value) {
        var crop = null;

        if (!value) {
            return null;
        }

        try {
            crop = JSON.parse(value);
        } catch (e) {
            try {
                crop = Object.fromEntries(new URLSearchParams(value));
            } catch (ignored) {
                crop = null;
            }
        }

        if (!crop) {
            return null;
        }

        var x = parseFloat(crop.x || crop.left || 0);
        var y = parseFloat(crop.y || crop.top || 0);
        var width = parseFloat(crop.width || crop.w || 0);
        var height = parseFloat(crop.height || crop.h || 0);

        if (!width || !height) {
            return null;
        }

        return {
            x: x,
            y: y,
            width: width,
            height: height
        };
    }

    function normaliseCrop(crop, image) {
        var normalised = {
            x: crop.x,
            y: crop.y,
            width: crop.width,
            height: crop.height
        };

        if (normalised.width <= 1 && normalised.height <= 1) {
            normalised.x *= image.naturalWidth;
            normalised.y *= image.naturalHeight;
            normalised.width *= image.naturalWidth;
            normalised.height *= image.naturalHeight;
        }

        normalised.x = Math.max(0, Math.min(normalised.x, image.naturalWidth - 1));
        normalised.y = Math.max(0, Math.min(normalised.y, image.naturalHeight - 1));
        normalised.width = Math.max(1, Math.min(normalised.width, image.naturalWidth - normalised.x));
        normalised.height = Math.max(1, Math.min(normalised.height, image.naturalHeight - normalised.y));

        return normalised;
    }

    function applyCropPreviewFromHiddenInput() {
        var cropInput = getCropInput();

        if (!cropInput || !cropInput.value || !originalImage || !previewImage) {
            return false;
        }

        if (cropInput.value === lastCropValue) {
            return true;
        }

        var crop = parseCropValue(cropInput.value);

        if (!crop) {
            return false;
        }

        lastCropValue = cropInput.value;
        crop = normaliseCrop(crop, originalImage);

        var canvas = document.createElement("canvas");
        var maxWidth = 640;
        var outputWidth = Math.min(maxWidth, Math.round(crop.width));
        var outputHeight = Math.max(1, Math.round(outputWidth * crop.height / crop.width));
        var context = canvas.getContext("2d");

        if (!context) {
            return false;
        }

        canvas.width = outputWidth;
        canvas.height = outputHeight;

        context.drawImage(
            originalImage,
            crop.x,
            crop.y,
            crop.width,
            crop.height,
            0,
            0,
            outputWidth,
            outputHeight
        );

        previewImage.src = canvas.toDataURL("image/jpeg", 0.92);
        markAsCurrentPicture();

        return true;
    }

    function isCropEditorElement(element) {
        return Boolean(
            element.closest(
                ".modal, .modal-dialog, .ui-dialog, .cropper-container, .cropper-wrap-box, .cropper-canvas, .jcrop-holder, [role=\"dialog\"]"
            )
        );
    }

    function getExternalGeneratedPreviewImages() {
        var form = input ? input.closest("form") : null;
        var cropInput = getCropInput();

        if (!form || !card || !cropInput || !cropInput.value) {
            return [];
        }

        return Array.prototype.filter.call(form.querySelectorAll("img"), function (image) {
            var src = image.getAttribute("src") || "";

            if (card.contains(image) || isCropEditorElement(image)) {
                return false;
            }

            if (image.classList.contains("hidden")) {
                return false;
            }

            return src.indexOf("blob:") === 0 || src.indexOf("data:image/") === 0;
        });
    }

    function hideGeneratedPreviewElement(image) {
        var current = image;
        var stop = input ? input.closest("form") : null;
        var steps = 0;

        if (isCropEditorElement(image)) {
            return;
        }

        while (
            current.parentElement &&
            current.parentElement !== stop &&
            steps < 4
        ) {
            var parent = current.parentElement;

            if (
                isCropEditorElement(parent) ||
                parent.querySelector("input[type=\"file\"], select, textarea")
            ) {
                break;
            }

            if (parent.children.length <= 2) {
                current = parent;
                steps++;
                continue;
            }

            break;
        }

        current.classList.add("hidden");
        current.setAttribute("aria-hidden", "true");
    }

    function hideExternalGeneratedPreviews() {
        getExternalGeneratedPreviewImages().forEach(hideGeneratedPreviewElement);
    }

    function syncPreviewAfterCrop() {
        var updated = applyCropPreviewFromHiddenInput();

        if (updated) {
            hideExternalGeneratedPreviews();
        }
    }

    function readImageFile(file) {
        if (objectUrl) {
            window.URL.revokeObjectURL(objectUrl);
        }

        objectUrl = window.URL.createObjectURL(file);

        originalImage = new Image();
        originalImage.onload = function () {
            if (previewImage) {
                previewImage.src = objectUrl;
            }

            syncPreviewAfterCrop();
        };
        originalImage.src = objectUrl;
    }

    if (!input || !card) {
        return;
    }

    input.addEventListener("change", function () {
        if (!input.files || !input.files[0]) {
            return;
        }

        lastCropValue = "";
        updateSelectedFileName(input.files[0]);
        readImageFile(input.files[0]);
        markAsCurrentPicture();

        if (syncTimer) {
            window.clearInterval(syncTimer);
        }

        syncTimer = window.setInterval(syncPreviewAfterCrop, 500);

        window.setTimeout(function () {
            if (syncTimer) {
                window.clearInterval(syncTimer);
                syncTimer = null;
            }

            syncPreviewAfterCrop();
        }, 30000);
    });
});
</script>';

$form->addButtonUpdate(get_lang('Edit course information'));

// Set some default values
$courseInfo['disk_quota'] = round(DocumentManager::get_course_quota($courseInfo['code']), 1);
$courseInfo['real_code'] = $courseInfo['code'];
$courseInfo['add_teachers_to_sessions_courses'] = $courseInfo['add_teachers_to_sessions_courses'] ?? 0;

// Set default duration in minutes
if (isset($courseInfo['duration'])) {
    $courseInfo['duration'] = $courseInfo['duration'] / 60;
}

$form->setDefaults($courseInfo);

// Validate form
if ($form->validate()) {
    $course = $form->getSubmitValues();
    $visibility = $course['visibility'];

    if (isset($course['duration'])) {
        $course['duration'] = (int) $course['duration'] * 60;
    }

    // @todo should be check in the CidReqListener
    /*global $_configuration;

    if (isset($_configuration[$urlId]) &&
        isset($_configuration[$urlId]['hosting_limit_active_courses']) &&
        $_configuration[$urlId]['hosting_limit_active_courses'] > 0
    ) {
        // Check if
        if (COURSE_VISIBILITY_HIDDEN == $courseInfo['visibility'] &&
            $visibility != $courseInfo['visibility']
        ) {
            $num = CourseManager::countActiveCourses($urlId);
            if ($num >= $_configuration[$urlId]['hosting_limit_active_courses']) {
                api_warn_hosting_contact('hosting_limit_active_courses');

                Display::addFlash(
                    Display::return_message(
                        get_lang(
                            'Sorry, this installation has an active courses limit, which has now been reached. You can still create new courses, but only if you hide/disable at least one existing active course. To do this, edit a course from the administration courses list, and change the visibility to \'hidden\', then try creating this course again. To increase the maximum number of active courses allowed on this Chamilo installation, please contact your hosting provider or, if available, upgrade to a superior hosting plan.'
                        )
                    )
                );

                header('Location: /admin/course-list');
                exit;
            }
        }
    }*/

    $visual_code = $course['visual_code'];
    $visual_code = CourseManager::generate_course_code($visual_code);

    // Check if the visual code is already used by *another* course
    $visual_code_is_used = false;

    $warn = get_lang('The following courses already use this code');
    if (!empty($visual_code)) {
        $list = CourseManager::get_courses_info_from_visual_code($visual_code);
        foreach ($list as $course_temp) {
            if ($course_temp['code'] != $course_code) {
                $visual_code_is_used = true;
                $warn .= ' '.$course_temp['title'].' ('.$course_temp['code'].'),';
            }
        }
        $warn = substr($warn, 0, -1);
    }

    $teachers = isset($course['course_teachers']) ? $course['course_teachers'] : '';
    $department_url = $course['department_url'];

    if (!stristr($department_url, 'http://')) {
        $department_url = 'http://'.$department_url;
    }

    $courseEntity = api_get_course_entity($courseId);
    $request = Container::getRequest();
    $submittedValues = $request->request->all();
    /** @var UploadedFile|null $uploadFile */
    $uploadFile = $request->files->get('picture');
    $deletePicture = !empty($submittedValues['delete_picture'] ?? null);

    // Handle course picture delete / upload.
    // Delete has priority to avoid uploading and deleting a picture in the same submit.
    if ($deletePicture) {
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }
    } elseif (null !== $uploadFile) {
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }

        $file = $illustrationRepo->addIllustration(
            $courseEntity,
            api_get_user_entity(api_get_user_id()),
            $uploadFile
        );

        if ($file) {
            if (!empty($course['picture_crop_result'])) {
                $file->setCrop($course['picture_crop_result']);
            }

            $em->persist($file);
            $em->flush();
        }
    }

    $courseEntity
        ->setCourseLanguage($course['course_language'])
        ->setTitle(str_replace('&amp;', '&', $course['title']))
        ->setVisualCode($visual_code)
        ->setDepartmentName($course['department_name'])
        ->setDepartmentUrl($department_url)
        ->setDiskQuota($course['disk_quota'])
        ->setSubscribe($course['subscribe'])
        ->setUnsubscribe($course['unsubscribe'])
        ->setVisibility($visibility)
        ->setSticky(1 === (int) ($course['sticky'] ?? 0))
        ->setVideoUrl($course['video_url'] ?? '')
    ;

    if (isset($course['duration'])) {
        $courseEntity->setDuration($course['duration']);
    }

    if (!empty($course['room_id'])) {
        $room = $em->find(\Chamilo\CoreBundle\Entity\Room::class, (int) $course['room_id']);
        $courseEntity->setRoom($room ?: null);
    } else {
        $courseEntity->setRoom(null);
    }

    $em->persist($courseEntity);
    $em->flush();

    // Updating course categories
    $courseCategoriesRepo->updateCourseRelCategoryByCourse($courseEntity, $course);

    // update the extra fields
    $courseFieldValue = new ExtraFieldValue('course');
    $courseFieldValue->saveFieldValues($course);
    $addTeacherToSessionCourses = isset($course['add_teachers_to_sessions_courses']) && !empty($course['add_teachers_to_sessions_courses']) ? 1 : 0;

    // Updating teachers
    if ($addTeacherToSessionCourses) {
        foreach ($coursesInSession as $session) {
            $sessionId = $session['id'];
            // Updating session coaches
            $sessionCoaches = isset($course['session_coaches_'.$sessionId]) ? $course['session_coaches_'.$sessionId] : [];

            if (!empty($sessionCoaches)) {
                foreach ($sessionCoaches as $teacherInfo) {
                    $coachesToSubscribe = isset($teacherInfo['coaches_by_session']) ? $teacherInfo['coaches_by_session'] : [];
                    SessionManager::updateCoaches(
                        $sessionId,
                        $courseId,
                        $coachesToSubscribe,
                        true
                    );
                }
            }
        }

        CourseManager::updateTeachers(
            $courseInfo,
            $teachers,
            true,
            true,
            false
        );
    } else {
        // Normal behaviour
        CourseManager::updateTeachers($courseInfo, $teachers, true, false);

        foreach ($coursesInSession as $session) {
            $sessionId = $session['id'];
            // Updating session coaches
            $sessionCoaches = isset($course['session_coaches_'.$sessionId]) ? $course['session_coaches_'.$sessionId] : [];

            if (!empty($sessionCoaches)) {
                SessionManager::updateCoaches(
                    $sessionId,
                    $courseId,
                    $sessionCoaches,
                    true
                );
            }
        }
    }

    if (array_key_exists('add_teachers_to_sessions_courses', $courseInfo)) {
        $sql = "UPDATE $course_table SET
                add_teachers_to_sessions_courses = '$addTeacherToSessionCourses'
                WHERE id = ".$courseInfo['real_id'];
        Database::query($sql);
    }

    $courseInfo = api_get_course_info($courseInfo['code']);
    $message = Display::url($courseInfo['title'], $courseInfo['course_public_url']);
    Display::addFlash(Display::return_message(get_lang('Item updated').': '.$message, 'info', false));
    if ($visual_code_is_used) {
        Display::addFlash(Display::return_message($warn));
    }
    header('Location: /admin/course-list');
    exit;
}

$tabs = [
    'general' => [
        'url' => 'course_edit.php?id='.$courseId,
        'content' => get_lang('Edit course information'),
    ],
    'catalogue_access' => [
        'url' => 'course_edit.php?id='.$courseId.'&view=catalogue_access',
        'content' => get_lang('Catalogue access'),
    ],
];

Display::display_header($tool_name);

$actions = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Back')),
    '/admin/course-list'
);
$actions .= Display::url(
    Display::getMdiIcon(ToolIcon::COURSE_HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Course homepage')),
    $courseInfo['course_public_url'],
    ['target' => '_blank']
);
$actions .= Display::url(
    Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Information')),
    api_get_path(WEB_CODE_PATH)."admin/course_information.php?id=$courseId"
);

echo Display::toolbarAction('toolbarCourseEdit', [Display::tabsOnlyLink($tabs, $currentView, 'course-edit-tabs')]);
echo Display::toolbarAction('toolbar', [$actions]);

echo "<script>
function moveItem(origin , destination) {
    for (var i = 0 ; i<origin.options.length ; i++) {
        if (origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
        }
    }
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}

function sortOptions(options) {

    newOptions = new Array();
    for (i = 0 ; i<options.length ; i++) {
        newOptions[i] = options[i];
    }
    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for (i = 0 ; i < newOptions.length ; i++) {
        options[i] = newOptions[i];
    }
}

function mysort(a, b) {
    if (a.text.toLowerCase() > b.text.toLowerCase()) {
        return 1;
    }
    if (a.text.toLowerCase() < b.text.toLowerCase()) {
        return -1;
    }
    return 0;
}

function valide() {
    // Checking all multiple
    $('select').filter(function() {
        if ($(this).attr('multiple')) {
            $(this).find('option').each(function() {
                $(this).attr('selected', true);
            });
        }
    });
}
</script>";

if ('catalogue_access' === $currentView) {
    echo Display::div(
        get_lang('Select classes for which this course will be visible for subscription in the catalogue. Subscription rules still apply apart from it being visible in the catalogue.'),
        ['class' => 'alert alert-info']
    );

    $em = Database::getManager();
    $accessUrl = Container::getAccessUrlUtil()->getCurrent();
    $accessUrlId = $accessUrl->getId();

    /** @var Course|null $course */
    $course = $em->getRepository(Course::class)->find($courseId);

    if (!$accessUrl || !$course) {
        echo Display::return_message(get_lang('Invalid access URL or course'), 'error');
        return;
    }

    $formCatalogue = new FormValidator(
        'form_catalogue_access',
        'post',
        api_get_self().'?id='.$courseId.'&view=catalogue_access'
    );

    $formCatalogue->addElement('header', get_lang('Course').'  #'.$courseInfo['real_id'].' '.$course_code);

    $groupEntities = $em->createQueryBuilder()
        ->select('ug')
        ->from(Usergroup::class, 'ug')
        ->innerJoin('ug.urls', 'urlRel')
        ->where('urlRel.url = :accessUrl')
        ->setParameter('accessUrl', $accessUrl)
        ->orderBy('ug.title', 'ASC')
        ->getQuery()
        ->getResult();

    $groups = [];
    foreach ($groupEntities as $group) {
        $groups[$group->getId()] = $group->getTitle();
    }

    $existing = $em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class)->findBy([
        'course' => $course,
        'accessUrl' => $accessUrl,
    ]);

    $selected = [];
    foreach ($existing as $record) {
        if ($record->getUsergroup()) {
            $selected[] = $record->getUsergroup()->getId();
        }
    }

    $formCatalogue->addMultiSelect(
        'selected_usergroups',
        get_lang('User groups'),
        $groups,
        ['style' => 'width:100%;height:300px;']
    );

    $formCatalogue->setDefaults([
        'selected_usergroups' => $selected,
    ]);

    $formCatalogue->addButtonSave(get_lang('Save'));

    if ($formCatalogue->validate()) {
        $data = $formCatalogue->getSubmitValues();
        $newGroups = $data['selected_usergroups'] ?? [];

        foreach ($existing as $old) {
            $em->remove($old);
        }
        $em->flush();

        foreach ($newGroups as $groupId) {
            $group = $em->getRepository(Usergroup::class)->find((int) $groupId);
            if ($group) {
                $rel = new CatalogueCourseRelAccessUrlRelUsergroup();
                $rel->setCourse($course);
                $rel->setAccessUrl($accessUrl);
                $rel->setUsergroup($group);
                $em->persist($rel);
            }
        }

        $em->flush();

        Display::addFlash(Display::return_message(get_lang('Saved.'), 'confirmation'));
        header('Location: '.api_get_self().'?id='.$courseId.'&view=catalogue_access');
        exit;
    }

    $formCatalogue->display();
} else {
    $form->display();
}

Display::display_footer();
