<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id = (int) $_GET['id'];
$currentView = $_GET['view'] ?? 'general';

$session = api_get_session_entity($id);
SessionManager::protectSession($session);

$tool_name = get_lang('Edit this session');

$interbreadcrumb[] = ['url' => '/admin/session-list', 'name' => get_lang('Session list')];
$interbreadcrumb[] = ['url' => 'resume_session.php?id_session='.$id, 'name' => get_lang('Session overview')];

$categoriesList = SessionManager::get_all_session_category();

$categoriesOption = [
    '0' => get_lang('none'),
];

if (false != $categoriesList) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOption[$categoryItem['id']] = $categoryItem['title'];
    }
}

$tabs = [
    'general' => [
        'url' => 'session_edit.php?id='.$id,
        'content' => get_lang('Edit this session'),
    ],
    'catalogue_access' => [
        'url' => 'session_edit.php?id='.$id.'&view=catalogue_access',
        'content' => get_lang('Catalogue access'),
    ],
];

Display::display_header($tool_name);
echo Display::toolbarAction('toolbarCourseEdit', [Display::tabsOnlyLink($tabs, $currentView, 'session-edit-tabs')]);

if ('catalogue_access' === $currentView) {
    echo Display::div(
        get_lang('Select classes for which this session will be visible for subscription in the catalogue. Subscription rules still apply apart from it being visible in the catalogue.'),
        ['class' => 'alert alert-info']
    );

    $em = Database::getManager();
    $accessUrl = Container::getAccessUrlUtil()->getCurrent();
    $accessUrlId = $accessUrl->getId();
    $sessionEntity = $em->getRepository(Session::class)->find($id);

    if (!$accessUrl || !$sessionEntity) {
        echo Display::return_message(get_lang('Invalid access URL or session'), 'error');
        Display::display_footer();
        return;
    }

    $formCatalogue = new FormValidator(
        'form_catalogue_access',
        'post',
        api_get_self().'?id='.$id.'&view=catalogue_access'
    );

    $formCatalogue->addElement('header', $session->getTitle());

    $groupEntities = $em->createQueryBuilder()
        ->select('ug')
        ->from(\Chamilo\CoreBundle\Entity\Usergroup::class, 'ug')
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

    $existing = $em->getRepository(CatalogueSessionRelAccessUrlRelUsergroup::class)->findBy([
        'session' => $sessionEntity,
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
        get_lang('User groups list'),
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
                $rel = new CatalogueSessionRelAccessUrlRelUsergroup();
                $rel->setSession($sessionEntity);
                $rel->setAccessUrl($accessUrl);
                $rel->setUsergroup($group);
                $em->persist($rel);
            }
        }

        $em->flush();

        Display::addFlash(Display::return_message(get_lang('Saved.'), 'confirmation'));
        header('Location: '.api_get_self().'?id='.$id.'&view=catalogue_access');
        exit();
    }
    $formCatalogue->display();
} else {
    $formAction = api_get_self().'?';
    $formAction .= http_build_query([
        'page' => Security::remove_XSS($_GET['page'] ?? ''),
        'id' => $id,
    ]);

    $form = new FormValidator('edit_session', 'post', $formAction);
    $form->addElement('header', $tool_name);
    $result = SessionManager::setForm($form, $session);
    $sessionPictureUrl = $session->hasImage() ? SessionManager::getSessionPictureUrl($session) : '';
    $sessionPictureStatusLabel = $session->hasImage() ? get_lang('Current picture') : get_lang('No image');
    $sessionPictureStatusClasses = $session->hasImage() ? 'bg-success text-white' : 'bg-gray-20 text-gray-90';
    $deleteSessionPictureIcon = Display::getMdiIcon(
        ActionIcon::DELETE,
        'ch-tool-icon text-danger',
        null,
        ICON_SIZE_SMALL,
        get_lang('Delete picture')
    );
    $htmlHeadXtra[] = '
<script>
$(function() {
    '.$result['js'].'
});
</script>';

    $form->addButtonUpdate(get_lang('Edit this session'));
    $showValidityField = 'true' === api_get_setting('session.enable_auto_reinscription') || 'true' === api_get_setting('session.enable_session_replication');

    $formDefaults = [
        'id' => $session->getId(),
        'session_category' => $session->getCategory()?->getId(),
        'title' => $session->getTitle(),
        'description' => $session->getDescription(),
        'show_description' => $session->getShowDescription(),
        'duration' => $session->getDuration(),
        'session_visibility' => $session->getVisibility(),
        'display_start_date' => $session->getDisplayStartDate() ? api_get_local_time($session->getDisplayStartDate()) : null,
        'display_end_date' => $session->getDisplayEndDate() ? api_get_local_time($session->getDisplayEndDate()) : null,
        'access_start_date' => $session->getAccessStartDate() ? api_get_local_time($session->getAccessStartDate()) : null,
        'access_end_date' => $session->getAccessEndDate() ? api_get_local_time($session->getAccessEndDate()) : null,
        'coach_access_start_date' => $session->getCoachAccessStartDate() ? api_get_local_time($session->getCoachAccessStartDate()) : null,
        'coach_access_end_date' => $session->getCoachAccessEndDate() ? api_get_local_time($session->getCoachAccessEndDate()) : null,
        'send_subscription_notification' => $session->getSendSubscriptionNotification(),
        'notify_boss' => $session->getNotifyBoss(),
        'coach_username' => array_map(
            function (User $user) {
                return $user->getId();
            },
            ($session->getGeneralCoaches()->getValues()??[])
        ),
        'days_before_finishing_for_reinscription' => $session->getDaysToReinscription() ?? '',
        'days_before_finishing_to_create_new_repetition' => $session->getDaysToNewRepetition() ?? '',
        'last_repetition' => $session->getLastRepetition(),
        'parent_id' => $session->getParentId() ?? 0,
    ];

    if ($showValidityField) {
        $formDefaults['validity_in_days'] = $session->getValidityInDays();
    }

    $form->setDefaults($formDefaults);
    if ($currentView && $form->validate()) {
        $params = $form->getSubmitValues();

        $name = $params['title'];
        $startDate = $params['access_start_date'] ?? null;
        $endDate = $params['access_end_date'] ?? null;
        $displayStartDate = $params['display_start_date'] ?? null;
        $displayEndDate = $params['display_end_date'] ?? null;
        $coachStartDate = $params['coach_access_start_date'] ?? null;
        $coachEndDate = $params['coach_access_end_date'] ?? null;
        $coachUsername = $params['coach_username'] ?? [];
        $id_session_category = $params['session_category'] ?? 0;
        $id_visibility = $params['session_visibility'] ?? 0;


        $duration = isset($params['duration']) ? $params['duration'] : null;
        if (1 == $params['access']) {
            $duration = null;
        }

        $description = $params['description'];
        $showDescription = isset($params['show_description']) ? 1 : 0;
        $sendSubscriptionNotification = isset($params['send_subscription_notification']);
        $isThisImageCropped = isset($params['picture_crop_result']);

        $extraFields = [];
        foreach ($params as $key => $value) {
            if (0 === strpos($key, 'extra_')) {
                $extraFields[$key] = $value;
            }
        }

        if (isset($extraFields['extra_image']) && $isThisImageCropped) {
            $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
        }

        $status = $params['status'] ?? 0;
        $notifyBoss = isset($params['notify_boss']) ? 1 : 0;

        $parentId = $params['parent_id'] ?? 0;
        $daysBeforeFinishingForReinscription = $params['days_before_finishing_for_reinscription'] ?? null;
        $daysBeforeFinishingToCreateNewRepetition = $params['days_before_finishing_to_create_new_repetition'] ?? null;
        $lastRepetition = isset($params['last_repetition']);
        $validityInDays = $params['validity_in_days'] ?? null;
        if (empty($coachUsername)) {
            $coachUsername = [];
        }

        $return = SessionManager::edit_session(
            $id,
            $name,
            $startDate,
            $endDate,
            $displayStartDate,
            $displayEndDate,
            $coachStartDate,
            $coachEndDate,
            $coachUsername,
            $id_session_category,
            $id_visibility,
            $description,
            $showDescription,
            $duration,
            $extraFields,
            null,
            $sendSubscriptionNotification,
            $status,
            $notifyBoss,
            $parentId,
            $daysBeforeFinishingForReinscription,
            $daysBeforeFinishingToCreateNewRepetition,
            $lastRepetition,
            $validityInDays
        );

        if ($return) {
            // Delete session picture
            $deletePicture = $_POST['delete_picture'] ?? '';
            if ($deletePicture && $return) {
                SessionManager::deleteAsset($return);
            }

            // Add image
            $picture = $_FILES['picture'] ?? [];
            if (!$deletePicture && !empty($picture['name'])) {
                SessionManager::updateSessionPicture(
                    $return,
                    $picture,
                    $params['picture_crop_result'] ?? ''
                );
            }

            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: resume_session.php?id_session='.$return);
            exit();
        }
    }
    echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    var form = document.querySelector("form[name=\"edit_session\"], form#edit_session, form");
    var input = document.getElementById("picture");
    var deleteCheckbox = document.querySelector("input[name=\"delete_picture\"]");
    var existingImageUrl = '.json_encode($sessionPictureUrl).';
    var statusLabel = '.json_encode($sessionPictureStatusLabel).';
    var statusClasses = '.json_encode($sessionPictureStatusClasses).';
    var currentPictureLabel = '.json_encode(get_lang('Current picture')).';
    var noImageLabel = '.json_encode(get_lang('No image')).';
    var previewLabel = '.json_encode(get_lang('Preview')).';
    var sessionPictureLabel = '.json_encode(get_lang('Image')).';
    var addImageLabel = '.json_encode(get_lang('Add image')).';
    var allowedTypesLabel = '.json_encode(get_lang('Only PNG, JPG or GIF images allowed')).';
    var deletePictureLabel = '.json_encode(get_lang('Delete picture')).';
    var deleteIconHtml = '.json_encode($deleteSessionPictureIcon).';
    var selectedFile = null;
    var previewImage = null;
    var status = null;
    var objectUrl = null;
    var originalImage = null;
    var lastCropValue = "";
    var syncTimer = null;

    function closestRow(element) {
        if (!element) {
            return null;
        }

        return element.closest(".form-group, .form-row, .row, .control-group, .field, .field-container, .formw") || element.parentElement;
    }

    function addInputClasses() {
        if (!input) {
            return;
        }

        [
            "block",
            "w-full",
            "cursor-pointer",
            "rounded-xl",
            "border",
            "border-gray-25",
            "bg-white",
            "text-body-2",
            "text-gray-90",
            "file:mr-4",
            "file:border-0",
            "file:bg-primary",
            "file:px-4",
            "file:py-2",
            "file:text-body-2",
            "file:font-semibold",
            "file:text-white",
            "hover:file:bg-primary-gradient"
        ].forEach(function (className) {
            input.classList.add(className);
        });
    }

    function hideEmptyLabels(container) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.querySelectorAll("label"), function (label) {
            if (!label.textContent.trim()) {
                label.classList.add("hidden");
            }
        });
    }

    function hideOriginalSessionImage() {
        if (!form || !existingImageUrl) {
            return;
        }

        Array.prototype.forEach.call(form.querySelectorAll("img"), function (image) {
            var src = image.getAttribute("src") || "";

            if (image.closest("#session-picture-card")) {
                return;
            }

            if (src === existingImageUrl || src.indexOf(existingImageUrl) !== -1) {
                var row = closestRow(image);
                if (row) {
                    row.classList.add("hidden");
                    row.setAttribute("aria-hidden", "true");
                } else {
                    image.classList.add("hidden");
                }
            }
        });
    }

    function buildCard() {
        if (!form || !input || document.getElementById("session-picture-card")) {
            return;
        }

        var inputRow = closestRow(input);
        var deleteRow = closestRow(deleteCheckbox);
        var card = document.createElement("div");
        var hasImage = Boolean(existingImageUrl);
        var deleteButtonHtml = "";

        if (hasImage) {
            deleteButtonHtml =
                "<button type=\"button\" id=\"session-picture-delete-button\" class=\"inline-flex h-8 w-8 items-center justify-center rounded-full border border-danger bg-white text-danger transition hover:bg-support-6\" title=\"" + deletePictureLabel + "\" aria-label=\"" + deletePictureLabel + "\">" +
                deleteIconHtml +
                "</button>";
        }

        card.id = "session-picture-card";
        card.className = "my-6 rounded-2xl border border-gray-25 bg-white p-4 shadow-sm";
        card.innerHTML =
            "<div class=\"grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-start\">" +
                "<div class=\"rounded-2xl border border-gray-25 bg-support-2 p-4 lg:col-span-3\">" +
                    "<div class=\"mb-3 flex items-center gap-2\">" +
                        "<span class=\"mdi mdi-image-outline ch-tool-icon\"></span>" +
                        "<h4 class=\"m-0 text-body-1 font-semibold text-gray-90\">" + sessionPictureLabel + "</h4>" +
                    "</div>" +
                    "<div id=\"session-picture-input-target\" class=\"min-h-24 rounded-2xl border border-dashed border-support-3 bg-white p-4\">" +
                        "<p class=\"m-0 mb-2 text-body-2 font-semibold text-gray-90\">" + addImageLabel + "</p>" +
                        "<p class=\"m-0 mb-3 text-caption text-gray-50\">" + allowedTypesLabel + "</p>" +
                    "</div>" +
                    "<p id=\"session-picture-selected-file\" class=\"mt-3 hidden text-body-2 font-semibold text-primary\"></p>" +
                "</div>" +
                "<div class=\"rounded-2xl border border-gray-25 bg-support-2 p-3 lg:col-span-1\">" +
                    "<div class=\"mb-3 flex items-center justify-between gap-2\">" +
                        "<span class=\"text-body-2 font-semibold text-gray-90\">" + previewLabel + "</span>" +
                        "<span class=\"flex items-center gap-2\">" +
                            "<span id=\"session-picture-status\" class=\"inline-flex items-center rounded-full px-3 py-1 text-caption font-semibold " + statusClasses + "\">" + statusLabel + "</span>" +
                            deleteButtonHtml +
                        "</span>" +
                    "</div>" +
                    "<div id=\"session-picture-preview-box\" class=\"aspect-video overflow-hidden rounded-xl border border-gray-25 bg-white\">" +
                        (
                            hasImage
                                ? "<img id=\"session-picture-preview-image\" class=\"block h-full w-full object-cover\" src=\"" + existingImageUrl + "\" alt=\"" + sessionPictureLabel + "\" />"
                                : "<div id=\"session-picture-empty-preview\" class=\"flex h-full w-full items-center justify-center bg-gray-20 text-caption font-semibold text-gray-50\">" + noImageLabel + "</div>"
                        ) +
                    "</div>" +
                "</div>" +
            "</div>";

        if (inputRow) {
            inputRow.parentNode.insertBefore(card, inputRow);
        } else {
            form.appendChild(card);
        }

        var target = document.getElementById("session-picture-input-target");
        selectedFile = document.getElementById("session-picture-selected-file");
        previewImage = document.getElementById("session-picture-preview-image");
        status = document.getElementById("session-picture-status");

        if (target && inputRow) {
            inputRow.classList.remove("hidden");
            inputRow.classList.add("m-0", "w-full");
            target.appendChild(inputRow);
            hideEmptyLabels(inputRow);
        }

        if (deleteRow) {
            deleteRow.classList.add("hidden");
            deleteRow.setAttribute("aria-hidden", "true");
        }

        var deleteButton = document.getElementById("session-picture-delete-button");
        if (deleteButton && deleteCheckbox) {
            deleteButton.addEventListener("click", function () {
                deleteCheckbox.checked = true;
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        }

        addInputClasses();
        hideOriginalSessionImage();
    }

    function ensurePreviewImage() {
        var previewBox = document.getElementById("session-picture-preview-box");
        var emptyPreview = document.getElementById("session-picture-empty-preview");

        if (previewImage) {
            return previewImage;
        }

        if (!previewBox) {
            return null;
        }

        if (emptyPreview) {
            emptyPreview.remove();
        }

        previewImage = document.createElement("img");
        previewImage.id = "session-picture-preview-image";
        previewImage.className = "block h-full w-full object-cover";
        previewImage.alt = sessionPictureLabel;
        previewBox.appendChild(previewImage);

        return previewImage;
    }

    function markAsCurrentPicture() {
        if (!status) {
            return;
        }

        status.textContent = currentPictureLabel;
        status.classList.remove("bg-gray-20", "text-gray-90", "bg-success");
        status.classList.add("bg-primary", "text-white");
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

        if (!cropInput || !cropInput.value || !originalImage) {
            return false;
        }

        var image = ensurePreviewImage();

        if (!image) {
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

        image.src = canvas.toDataURL("image/jpeg", 0.92);
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

    function hideExternalGeneratedPreviews() {
        var cropInput = getCropInput();

        if (!form || !cropInput || !cropInput.value) {
            return;
        }

        Array.prototype.forEach.call(form.querySelectorAll("img"), function (image) {
            var src = image.getAttribute("src") || "";

            if (image.closest("#session-picture-card") || isCropEditorElement(image)) {
                return;
            }

            if (src.indexOf("blob:") !== 0 && src.indexOf("data:image/") !== 0) {
                return;
            }

            var row = closestRow(image) || image;
            row.classList.add("hidden");
            row.setAttribute("aria-hidden", "true");
        });
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
            var image = ensurePreviewImage();

            if (image) {
                image.src = objectUrl;
            }

            syncPreviewAfterCrop();
        };
        originalImage.src = objectUrl;
    }

    if (!form || !input) {
        return;
    }

    buildCard();

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

    $form->display();
    ?>

    <script>
        $(function() {
            <?php
            echo $session->getDuration() > 0 ? 'accessSwitcher(0);' : 'accessSwitcher(1);';
            ?>
        });

        function accessSwitcher(accessFromReady) {
            var access = $('#access option:selected').val();

            if (accessFromReady >= 0) {
                access = accessFromReady;
                $('[name=access]').val(access);
            }
            if (access == 1) {
                $('#duration_div').hide();
                $('#date_fields').show();
                emptyDuration();
            } else {
                $('#duration_div').show();
                $('#date_fields').hide();
            }
        }

        function emptyDuration() {
            if ($('#duration').val()) {
                $('#duration').val('');
            }
        }

        $(function() {
            $('#show-options').on('click', function (e) {
                e.preventDefault();
                var display = $('#options').css('display');
                display === 'block' ? $('#options').slideUp() : $('#options').slideDown() ;
            });
        });

    </script>
    <?php
}
Display::display_footer();
