<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
api_protect_teacher_script();

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();
$toolsRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

/** @var ImsLtiTool $baseTool */
$baseTool = isset($_REQUEST['type']) ? $toolsRepo->find(intval($_REQUEST['type'])) : null;
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : 'add';

$course = api_get_course_entity(api_get_course_int_id());
$addedTools = $toolsRepo->findBy(['course' => $course]);
$globalTools = $toolsRepo->findBy(['parent' => null, 'course' => null]);

if ($baseTool && !$baseTool->isGlobal()) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolNotAvailable'), 'warning')
    );

    header('Location: '.api_get_self().'?'.api_get_cidreq());
    exit;
}

$categories = Category::load(null, null, $course->getCode());

switch ($action) {
    case 'add':
        $form = new \Chamilo\PluginBundle\ImsLti\Form\FrmAdd('ims_lti_add_tool', [], $baseTool);
        $form->build();

        if ($baseTool) {
            $form->addHidden('type', $baseTool->getId());
        }

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            $tool = new ImsLtiTool();

            if ($baseTool) {
                $tool = clone $baseTool;
                $tool->setParent($baseTool);
            }

            $tool
                ->setName($formValues['name'])
                ->setDescription(
                    empty($formValues['description']) ? null : $formValues['description']
                )
                ->setCustomParams(
                    empty($formValues['custom_params']) ? null : $formValues['custom_params']
                )
                ->setDocumenTarget($formValues['document_target'])
                ->setCourse($course)
                ->setPrivacy(
                    !empty($formValues['share_name']),
                    !empty($formValues['share_email']),
                    !empty($formValues['share_picture'])
                );

            if (!empty($formValues['replacement_user_id'])) {
                $tool->setReplacementForUserId($formValues['replacement_user_id']);
            }

            if (!$baseTool) {
                if (ImsLti::V_1P3 === $formValues['version']) {
                    $tool
                        ->setVersion(ImsLti::V_1P3)
                        ->setLaunchUrl($formValues['launch_url'])
                        ->setClientId(
                            ImsLti::generateClientId()
                        )
                        ->setLoginUrl($formValues['login_url'])
                        ->setRedirectUrl($formValues['redirect_url'])
                        ->setAdvantageServices(
                            [
                                'ags' => $formValues['1p3_ags'] ?? LtiAssignmentGradesService::AGS_NONE,
                                'nrps' => $formValues['1p3_nrps'],
                            ]
                        )
                        ->setJwksUrl($formValues['jwks_url'])
                        ->publicKey = $formValues['public_key'];
                } elseif (ImsLti::V_1P1 === $formValues['version']) {
                    if (empty($formValues['consumer_key']) && empty($formValues['shared_secret'])) {
                        try {
                            $launchUrl = $plugin->getLaunchUrlFromCartridge($formValues['launch_url']);
                        } catch (Exception $e) {
                            Display::addFlash(
                                Display::return_message($e->getMessage(), 'error')
                            );

                            header('Location: '.api_get_self().'?'.api_get_cidreq());
                            exit;
                        }

                        $tool->setLaunchUrl($launchUrl);
                    } else {
                        $tool
                            ->setLaunchUrl($formValues['launch_url'])
                            ->setConsumerKey($formValues['consumer_key'])
                            ->setSharedSecret($formValues['shared_secret']);
                    }
                }
            }

            if (null === $baseTool ||
                ($baseTool && !$baseTool->isActiveDeepLinking())
            ) {
                $tool
                    ->setActiveDeepLinking(
                        !empty($formValues['deep_linking'])
                    );
            }

            $em->persist($tool);
            $em->flush();

            if ($tool->getVersion() === ImsLti::V_1P3) {
                $advServices = $tool->getAdvantageServices();

                if (LtiAssignmentGradesService::AGS_NONE !== $advServices['ags']) {
                    $lineItemResource = new LtiLineItemsResource(
                        $tool->getId(),
                        $course->getId()
                    );
                    $lineItemResource->createLineItem(
                        ['label' => $tool->getName(), 'scoreMaximum' => 100]
                    );

                    Display::addFlash(
                        Display::return_message($plugin->get_lang('GradebookEvaluationCreated'), 'success')
                    );
                }
            }

            if (!$tool->isActiveDeepLinking()) {
                $plugin->addCourseTool($course, $tool);
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolAdded'), 'success')
            );

            header('Location: '.api_get_self().'?'.api_get_cidreq());
            exit;
        }

        $form->setDefaultValues();
        break;
    case 'edit':
        /** @var ImsLtiTool|null $tool */
        $tool = null;

        if (!empty($_REQUEST['id'])) {
            $tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', (int) $_REQUEST['id']);
        }

        if (empty($tool) ||
            !ImsLtiPlugin::existsToolInCourse($tool->getId(), $course)
        ) {
            api_not_allowed(
                true,
                Display::return_message($plugin->get_lang('ToolNotAvailable'), 'error')
            );

            break;
        }

        $form = new \Chamilo\PluginBundle\Form\FrmEdit('ims_lti_edit_tool', [], $tool);
        $form->build(false);

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            $tool
                ->setName($formValues['name'])
                ->setDescription(
                    empty($formValues['description']) ? null : $formValues['description']
                )
                ->setActiveDeepLinking(
                    !empty($formValues['deep_linking'])
                )
                ->setCustomParams(
                    empty($formValues['custom_params']) ? null : $formValues['custom_params']
                )
                ->setDocumenTarget($formValues['document_target'])
                ->setPrivacy(
                    !empty($formValues['share_name']),
                    !empty($formValues['share_email']),
                    !empty($formValues['share_picture'])
                );

            if (!empty($formValues['replacement_user_id'])) {
                $tool->setReplacementForUserId($formValues['replacement_user_id']);
            }

            if (null === $tool->getParent()) {
                if ($tool->getVersion() === ImsLti::V_1P3) {
                    $tool
                        ->setLaunchUrl($formValues['launch_url'])
                        ->setLoginUrl($formValues['login_url'])
                        ->setRedirectUrl($formValues['redirect_url'])
                        ->setAdvantageServices(
                            [
                                'ags' => $formValues['1p3_ags'] ?? LtiAssignmentGradesService::AGS_NONE,
                                'nrps' => $formValues['1p3_nrps'],
                            ]
                        )
                        ->setJwksUrl($formValues['jwks_url'])
                        ->publicKey = $formValues['public_key'];
                } elseif ($tool->getVersion() === ImsLti::V_1P1) {
                    $tool
                        ->setLaunchUrl($formValues['launch_url'])
                        ->setConsumerKey($formValues['consumer_key'])
                        ->setSharedSecret($formValues['shared_secret']);
                }
            }

            $em->persist($tool);
            $em->flush();

            $courseTool = $plugin->findCourseToolByLink($course, $tool);

            if ($courseTool) {
                $plugin->updateCourseTool($courseTool, $tool);
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolEdited'), 'success')
            );

            header('Location: '.api_get_self().'?'.api_get_cidreq());
            exit;
        }

        $form->setDefaultValues();
        break;
    default:
        api_not_allowed(true);
        break;
}

$template = new Template($plugin->get_lang('AddExternalTool'));
$template->assign('type', $baseTool ? $baseTool->getId() : null);
$template->assign('added_tools', $addedTools);
$template->assign('global_tools', $globalTools);
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$actions = Display::url(
    Display::return_icon('add.png', $plugin->get_lang('AddExternalTool'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?'.api_get_cidreq()
);

if (!empty($categories)) {
    $actions .= Display::url(
        Display::return_icon('gradebook.png', get_lang('MakeQualifiable'), [], ICON_SIZE_MEDIUM),
        './gradebook/add_eval.php?selectcat='.$categories[0]->get_id().'&'.api_get_cidreq()
    );
}

$template->assign('actions', Display::toolbarAction('lti_toolbar', [$actions]));
$template->assign('content', $content);
$template->display_one_col_template();
