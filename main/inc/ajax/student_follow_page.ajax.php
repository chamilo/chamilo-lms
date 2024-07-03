<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();

$isAllowedToEdit = api_is_allowed_to_edit();

switch ($httpRequest->get('a')) {
    case 'form_adquisition':
        displayForm(
            $httpRequest->query->getInt('lp_view')
        );
        break;
    case 'views_invisible':
        processViewsInvisible(
            $httpRequest->request->get('chkb_view') ?: [],
            $httpRequest->request->getBoolean('state')
        );
        break;
}

function displayForm(int $lpViewId)
{
    $em = Database::getManager();

    $lpView = $em->find(CLpView::class, $lpViewId);

    if (null === $lpView) {
        return;
    }

    $lp = $em->find(CLp::class, $lpView->getLpId());

    $extraField = new ExtraField('lp_view');
    $field = $extraField->get_handler_field_info_by_field_variable(StudentFollowPage::VARIABLE_ACQUISITION);

    $extraFieldValue = new ExtraFieldValue('lp_view');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $lpViewId,
        StudentFollowPage::VARIABLE_ACQUISITION
    );

    $options = [];

    foreach ($field['options'] as $option) {
        $options[$option['option_value']] = ExtraFieldOption::translateDisplayName($option['display_text']);
    }

    $frmId = 'frm_lp_acquisition_'.$lpView->getLpId();
    $frmAction = api_get_self().'?'.http_build_query(['lp_view' => $lpViewId, 'a' => 'form_adquisition']);

    $form = new FormValidator($frmId, 'post', $frmAction);
    $form->addRadio(StudentFollowPage::VARIABLE_ACQUISITION, get_lang('Acquisition'), $options);
    $form->addHidden('lp_view', $lpViewId);
    $form->addButtonSave(get_lang('Save'));

    if ($form->validate()) {
        $values = $form->exportValues();

        $extraFieldValue = new ExtraFieldValue('lp_view');
        $extraFieldValue->save(
            [
                'variable' => StudentFollowPage::VARIABLE_ACQUISITION,
                'item_id' => $lpViewId,
                'comment' => json_encode(['user' => api_get_user_id(), 'datetime' => api_get_utc_datetime()]),
                'value' => $values[StudentFollowPage::VARIABLE_ACQUISITION],
            ]
        );

        echo StudentFollowPage::getLpAcquisition(
            [
                'iid' => $lp->getIid(),
                'lp_name' => $lp->getName(),
            ],
            $lpView->getUserId(),
            $lpView->getCId(),
            $lpView->getSessionId(),
            true
        );
        exit;
    }

    if (!empty($value)) {
        $form->setDefaults([StudentFollowPage::VARIABLE_ACQUISITION => $value['value']]);
    }

    echo $form->returnForm()
        ."<script>$(function () {
            $('#$frmId').on('submit', function (e) {
                e.preventDefault();

                var self = $(this);

                self.find(':submit').prop('disabled', true);

                $.post(this.action, self.serialize()).done(function (response) {
                    $('#acquisition-$lpViewId').html(response);

                    $('#global-modal').modal('hide');

                    self.find(':submit').prop('disabled', false);
                });
            })
        })</script>";
}

function processViewsInvisible(array $lpViews, bool $state)
{
    foreach ($lpViews as $lpViewData) {
        $parts = explode('_', $lpViewData);

        [$lpId, $userId, $courseId, $sessionId] = array_map('intval', $parts);

        $lpView = learnpath::findLastView($lpId, $userId, $courseId, $sessionId, true);

        $extraFieldValue = new ExtraFieldValue('lp_view');
        $extraFieldValue->save(
            [
                'variable' => StudentFollowPage::VARIABLE_INVISIBLE,
                'item_id' => $lpView['iid'],
                'comment' => json_encode(['user' => api_get_user_id(), 'datetime' => api_get_utc_datetime()]),
                'value' => $state,
            ]
        );
    }
}
