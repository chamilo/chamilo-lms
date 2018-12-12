<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Form\ExternalToolType;
use Chamilo\LtiBundle\Util\Utils;
use Chamilo\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class CourseController.
 *
 * @package Chamilo\LtiBundle\Controller
 */
class CourseController extends BaseController
{
    /**
     * @Route("/edit/{id}", name="chamilo_lti_edit", requirements={"id"="\d+"})
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    public function editAction($id, Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExternalTool $tool */
        $tool = $em->find('ChamiloLtiBundle:ExternalTool', $id);

        if (empty($tool)) {
            throw $this->createNotFoundException('External tool not found');
        }

        $course = $this->getCourse();

        $form = $this->createForm(ExternalToolType::class, $tool);
        $form->get('shareName')->setData($tool->isSharingName());
        $form->get('shareEmail')->setData($tool->isSharingEmail());
        $form->get('sharePicture')->setData($tool->isSharingPicture());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->setConfigureBreadcrumb($course);

            return $this->render(
                '@ChamiloTheme/Lti/course_configure.twig',
                [
                    'title' => $this->trans('Edit external tool'),
                    'added_tools' => [],
                    'global_tools' => [],
                    'form' => $form->createView(),
                    'course' => $course,
                ]
            );
        }

        /** @var ExternalTool $tool */
        $tool = $form->getData();

        $em->persist($tool);

        if (!$tool->isActiveDeepLinking()) {
            $courseTool = $em->getRepository('ChamiloCourseBundle:CTool')
                ->findOneBy(
                    [
                        'course' => $course,
                        'link' => $this->generateUrl(
                            'chamilo_lti_show',
                            ['code' => $course->getCode(), 'id' => $tool->getId()]
                        ),
                    ]
                );

            if (empty($courseTool)) {
                throw $this->createNotFoundException('Course tool not found.');
            }

            $courseTool->setName($tool->getName());

            $em->persist($courseTool);
        }

        $em->flush();

        $this->addFlash('success', $this->trans('External tool edited'));

        return $this->redirectToRoute(
            'chamilo_lti_edit',
            ['id' => $tool->getId(), 'code' => $course->getCode()]
        );
    }

    /**
     * @Route("/launch/{id}", name="chamilo_lti_launch", requirements={"id"="\d+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function launchAction($id): Response
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExternalTool|null $tool */
        $tool = $em->find('ChamiloLtiBundle:ExternalTool', $id);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $settingsManager = $this->get('chamilo.settings.manager');

        /** @var User $user */
        $user = $this->getUser();
        $course = $this->getCourse();
        $session = $this->getCourseSession();

        if (empty($tool->getCourse()) || $tool->getCourse()->getId() !== $course->getId()) {
            throw $this->createAccessDeniedException('');
        }

        $ltiUtil = $this->get('chamilo_lti_utils');

        $institutionDomain = $ltiUtil->getInstitutionDomain();
        $toolUserId = $ltiUtil->generateToolUserId($user->getId());

        $params = [];
        $params['lti_version'] = 'LTI-1p0';

        if ($tool->isActiveDeepLinking()) {
            $params['lti_message_type'] = 'ContentItemSelectionRequest';
            $params['content_item_return_url'] = $this->generateUrl(
                'chamilo_lti_return_item',
                ['code' => $course->getCode()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $params['accept_media_types'] = '*/*';
            $params['accept_presentation_document_targets'] = 'iframe';
            $params['title'] = $tool->getName();
            $params['text'] = $tool->getDescription();
            $params['data'] = 'tool:'.$tool->getId();
        } else {
            $params['lti_message_type'] = 'basic-lti-launch-request';
            $params['resource_link_id'] = $tool->getId();
            $params['resource_link_title'] = $tool->getName();
            $params['resource_link_description'] = $tool->getDescription();

            $toolEval = $tool->getGradebookEval();

            if (!empty($toolEval)) {
                $params['lis_result_sourcedid'] = json_encode(
                    ['e' => $toolEval->getId(), 'u' => $user->getId(), 'l' => uniqid(), 'lt' => time()]
                );
                $params['lis_outcome_service_url'] = api_get_path(WEB_PATH).'lti/os';
                /* $params['lis_outcome_service_url'] = $this->generateUrl(
                    'chamilo_lti_os',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ); */
                $params['lis_person_sourcedid'] = "$institutionDomain:$toolUserId";
                $params['lis_course_section_sourcedid'] = "$institutionDomain:".$course->getId();

                if ($session) {
                    $params['lis_course_section_sourcedid'] .= ':'.$session->getId();
                }
            }
        }

        $params['user_id'] = $toolUserId;

        if ($tool->isSharingPicture()) {
            $params['user_image'] = \UserManager::getUserPicture($user->getId());
        }

        $params['roles'] = Utils::generateUserRoles($user);

        if ($tool->isSharingName()) {
            $params['lis_person_name_given'] = $user->getFirstname();
            $params['lis_person_name_family'] = $user->getLastname();
            $params['lis_person_name_full'] = $user->getFirstname().' '.$user->getLastname();
        }

        if ($tool->isSharingEmail()) {
            $params['lis_person_contact_email_primary'] = $user->getEmail();
        }

        if ($user->hasRole('ROLE_RRHH')) {
            $scopeMentor = $ltiUtil->generateRoleScopeMentor($user);

            if (!empty($scopeMentor)) {
                $params['role_scope_mentor'] = $scopeMentor;
            }
        }

        $params['context_id'] = $course->getId();
        $params['context_type'] = 'CourseSection';
        $params['context_label'] = $course->getCode();
        $params['context_title'] = $course->getTitle();
        $params['launch_presentation_locale'] = 'en';
        $params['launch_presentation_document_target'] = 'iframe';
        $params['tool_consumer_info_product_family_code'] = 'Chamilo LMS';
        $params['tool_consumer_info_version'] = '2.0';
        $params['tool_consumer_instance_guid'] = $institutionDomain;
        $params['tool_consumer_instance_name'] = $settingsManager->getSetting('platform.site_name');
        $params['tool_consumer_instance_url'] = $this->generateUrl(
            'home',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $params['tool_consumer_instance_contact_email'] = $settingsManager->getSetting('admin.administrator_email');

        $params['oauth_callback'] = 'about:blank';

        $customParams = $tool->parseCustomParams();
        Utils::trimParams($customParams);
        $this->variableSubstitution($params, $customParams, $user, $course, $session);

        $params += $customParams;
        Utils::trimParams($params);

        if (!empty($tool->getConsumerKey()) && !empty($tool->getSharedSecret())) {
            $consumer = new \OAuthConsumer(
                $tool->getConsumerKey(),
                $tool->getSharedSecret(),
                null
            );
            $hmacMethod = new \OAuthSignatureMethod_HMAC_SHA1();

            $request = \OAuthRequest::from_consumer_and_token(
                $consumer,
                '',
                'POST',
                $tool->getLaunchUrl(),
                $params
            );
            $request->sign_request($hmacMethod, $consumer, '');

            $params = $request->get_parameters();
        }

        Utils::removeQueryParamsFromLaunchUrl($tool, $params);

        return $this->render(
            '@ChamiloTheme/Lti/launch.html.twig',
            [
                'params' => $params,
                'launch_url' => $tool->getLaunchUrl(),
            ]
        );
    }

    /**
     * @Route("/item_return", name="chamilo_lti_return_item")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function returnItemAction(Request $request): Response
    {
        $contentItems = $request->get('content_items');
        $data = $request->get('data');

        if (empty($contentItems) || empty($data)) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        /** @var ExternalTool $tool */
        $tool = $em->find('ChamiloLtiBundle:ExternalTool', str_replace('tool:', '', $data));

        if (empty($tool)) {
            throw $this->createNotFoundException('External tool not found');
        }

        $course = $this->getCourse();
        $url = $this->generateUrl(
            'chamilo_lti_return_item',
            ['code' => $course->getCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $signatureIsValid = Utils::checkRequestSignature(
            $url,
            $request->get('oauth_consumer_key'),
            $request->get('oauth_signature'),
            $tool
        );

        if (!$signatureIsValid) {
            throw $this->createAccessDeniedException();
        }

        $contentItems = json_decode($contentItems, true)['@graph'];

        $supportedItemTypes = ['LtiLinkItem'];

        foreach ($contentItems as $contentItem) {
            if (!in_array($contentItem['@type'], $supportedItemTypes)) {
                continue;
            }

            if ('LtiLinkItem' === $contentItem['@type']) {
                $newTool = $this->createLtiLink($contentItem, $tool);

                $this->addFlash(
                    'success',
                    sprintf(
                        $this->trans('External tool added: %s'),
                        $newTool->getName()
                    )
                );
            }
        }

        return $this->render(
            '@ChamiloTheme/Lti/item_return.html.twig',
            ['course' => $course]
        );
    }

    /**
     * @Route("/{id}", name="chamilo_lti_show", requirements={"id"="\d+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function showAction($id): Response
    {
        $course = $this->getCourse();

        $em = $this->getDoctrine()->getManager();

        /** @var ExternalTool|null $externalTool */
        $externalTool = $em->find('ChamiloLtiBundle:ExternalTool', $id);

        if (empty($externalTool)) {
            throw $this->createNotFoundException();
        }

        if (empty($externalTool->getCourse()) || $externalTool->getCourse()->getId() !== $course->getId()) {
            throw $this->createAccessDeniedException('');
        }

        $breadcrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadcrumb->addChild(
            $course->getTitle(),
            [
                'uri' => $this->generateUrl(
                    'chamilo_course_home_home_index',
                    ['course' => $course->getCode()]
                ),
            ]
        );
        $breadcrumb->addChild(
            $this->trans($externalTool->getName())
        );

        return $this->render(
            'ChamiloThemeBundle:Lti:iframe.html.twig',
            ['tool' => $externalTool, 'course' => $course]
        );
    }

    /**
     * @Route("/", name="chamilo_lti_configure")
     * @Route("/add/{id}", name="chamilo_lti_configure_global", requirements={"id"="\d+"})
     *
     * @Security("has_role('ROLE_TEACHER')")
     *
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    public function courseConfigureAction($id = '', Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('ChamiloLtiBundle:ExternalTool');

        $tool = new ExternalTool();
        $parentTool = null;

        if (!empty($id)) {
            $parentTool = $repo->findOneBy(['id' => $id, 'course' => null]);

            if (empty($parentTool)) {
                throw $this->createNotFoundException('External tool not found');
            }

            $tool = clone $parentTool;
            $tool->setParent($parentTool);
        }

        $course = $this->getCourse();

        $form = $this->createForm(ExternalToolType::class, $tool);
        $form->get('shareName')->setData($tool->isSharingName());
        $form->get('shareEmail')->setData($tool->isSharingEmail());
        $form->get('sharePicture')->setData($tool->isSharingPicture());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->setConfigureBreadcrumb($course);

            $categories = \Category::load(null, null, $course->getCode());
            $actions = '';

            if (!empty($categories)) {
                $actions .= \Display::url(
                    \Display::return_icon('gradebook.png', get_lang('MakeQualifiable'), [], ICON_SIZE_MEDIUM),
                    $this->generateUrl(
                        'chamilo_lti_grade',
                        ['catId' => $categories[0]->get_id(), 'code' => $course->getCode()]
                    )
                    //'./gradebook/add_eval.php?selectcat='.$categories[0]->get_id().'&'.api_get_cidreq()
                );
            }

            return $this->render(
                '@ChamiloTheme/Lti/course_configure.twig',
                [
                    'title' => $this->trans('Add external tool'),
                    'added_tools' => $repo->findBy(['course' => $course]),
                    'global_tools' => $repo->findBy(['parent' => null, 'course' => null]),
                    'form' => $form->createView(),
                    'course' => $course,
                    'actions' => $actions,
                ]
            );
        }

        /** @var ExternalTool $tool */
        $tool = $form->getData();
        $tool->setCourse($course);

        $em->persist($tool);
        $em->flush();

        $this->addFlash('success', $this->trans('External tool added'));

        if (!$tool->isActiveDeepLinking()) {
            $courseTool = new CTool();
            $courseTool
                ->setCourse($course)
                ->setImage('plugin.png')
                ->setName($tool->getName())
                ->setVisibility(true)
                ->setTarget('_self')
                ->setCategory('interaction')
                ->setLink(
                    $this->generateUrl(
                        'chamilo_lti_show',
                        ['code' => $course->getCode(), 'id' => $tool->getId()]
                    )
                );

            $em->persist($courseTool);
            $em->flush();

            return $this->redirectToRoute(
                'chamilo_course_home_home_index',
                ['course' => $course->getCode()]
            );
        }

        return $this->redirectToRoute(
            'chamilo_lti_configure',
            ['course' => $course->getCode()]
        );
    }

    /**
     * @Route("/grade/{catId}", name="chamilo_lti_grade", requirements={"catId"="\d+"})
     *
     * @Security("has_role('ROLE_TEACHER')")
     *
     * @param string $catId
     *
     * @throws \Exception
     */
    public function gradeAction($catId)
    {
        $em = $this->getDoctrine()->getManager();
        $toolRepo = $em->getRepository('ChamiloLtiBundle:ExternalTool');
        $course = $this->getCourse();
        /** @var User $user */
        $user = $this->getUser();

        $categories = \Category::load(null, null, $course->getCode());

        if (empty($categories)) {
            throw $this->createNotFoundException();
        }

        $evaladd = new \Evaluation();
        $evaladd->set_user_id($user->getId());

        if (!empty($catId)) {
            $evaladd->set_category_id($catId);
            $cat = \Category::load($catId);
            $evaladd->set_course_code($course->getCode());
        } else {
            $evaladd->set_category_id(0);
        }

        $form = new \EvalForm(
            \EvalForm::TYPE_ADD,
            $evaladd,
            null,
            'add_eval_form',
            null,
            $this->generateUrl(
                'chamilo_lti_grade',
                ['catId' => $catId, 'code' => $course->getCode()]
            ).'?'.api_get_cidreq()
        );
        $form->removeElement('name');
        $form->removeElement('addresult');
        /** @var \HTML_QuickForm_select $slcLtiTools */
        $slcLtiTools = $form->createElement('select', 'name', $this->trans('External tool'));
        $form->insertElementBefore($slcLtiTools, 'hid_category_id');
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        $tools = $toolRepo->findBy(['course' => $course, 'gradebookEval' => null]);

        /** @var ExternalTool $tool */
        foreach ($tools as $tool) {
            $slcLtiTools->addOption($tool->getName(), $tool->getId());
        }

        if (!$form->validate()) {
            $this->setConfigureBreadcrumb($course);

            return $this->render(
                '@ChamiloTheme/Lti/gradebook.html.twig',
                [
                    'form' => $form->returnForm(),
                ]
            );
        }

        $values = $form->exportValues();

        $tool = $toolRepo->find($values['name']);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $eval = new \Evaluation();
        $eval->set_name($tool->getName());
        $eval->set_description($values['description']);
        $eval->set_user_id($values['hid_user_id']);

        if (!empty($values['hid_course_code'])) {
            $eval->set_course_code($values['hid_course_code']);
        }

        $eval->set_course_code($course->getCode());
        $eval->set_category_id($values['hid_category_id']);

        $values['weight'] = $values['weight_mask'];

        $eval->set_weight($values['weight']);
        $eval->set_max($values['max']);
        $eval->set_visible(empty($values['visible']) ? 0 : 1);
        $eval->add();

        $gradebookEval = $em->find('ChamiloCoreBundle:GradebookEvaluation', $eval->get_id());

        $tool->setGradebookEval($gradebookEval);

        $em->persist($tool);
        $em->flush();

        $this->addFlash('success', $this->trans('Evaluation for external tool added'));

        return $this->redirect(api_get_course_url());
    }

    /**
     * @param Course $course
     */
    private function setConfigureBreadcrumb(Course $course)
    {
        $breadcrumb = $this->get('chamilo_core.block.breadcrumb');
        $breadcrumb->addChild(
            $course->getTitle(),
            [
                'uri' => $this->generateUrl(
                    'chamilo_course_home_home_index',
                    ['course' => $course->getCode()]
                ),
            ]
        );
        $breadcrumb->addChild(
            $this->trans('Configure external tool')
        );
    }

    /**
     * @param array        $params
     * @param array        $customParams
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session
     */
    private function variableSubstitution(
        array $params,
        array &$customParams,
        User $user,
        Course $course,
        Session $session = null
    ) {
        $replaceable = self::getReplaceableVariables($user, $course, $session);
        $variables = array_keys($replaceable);

        foreach ($customParams as $customKey => $customValue) {
            if (!in_array($customValue, $variables)) {
                continue;
            }

            $val = $replaceable[$customValue];

            if (is_array($val)) {
                $val = current($val);

                if (array_key_exists($val, $params)) {
                    $customParams[$customKey] = $params[$val];

                    continue;
                } else {
                    $val = false;
                }
            }

            if (false === $val) {
                $customParams[$customKey] = $customValue;

                continue;
            }

            $customParams[$customKey] = $replaceable[$customValue];
        }
    }

    /**
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session
     *
     * @return array
     */
    private static function getReplaceableVariables(User $user, Course $course, Session $session = null)
    {
        return [
            '$User.id' => $user->getId(),
            '$User.image' => ['user_image'],
            '$User.username' => $user->getUsername(),

            '$Person.sourcedId' => false,
            '$Person.name.full' => $user->getFullname(),
            '$Person.name.family' => $user->getLastname(),
            '$Person.name.given' => $user->getFirstname(),
            '$Person.name.middle' => false,
            '$Person.name.prefix' => false,
            '$Person.name.suffix' => false,
            '$Person.address.street1' => $user->getAddress(),
            '$Person.address.street2' => false,
            '$Person.address.street3' => false,
            '$Person.address.street4' => false,
            '$Person.address.locality' => false,
            '$Person.address.statepr' => false,
            '$Person.address.country' => false,
            '$Person.address.postcode' => false,
            '$Person.address.timezone' => false, //$user->getTimezone(),
            '$Person.phone.mobile' => false,
            '$Person.phone.primary' => $user->getPhone(),
            '$Person.phone.home' => false,
            '$Person.phone.work' => false,
            '$Person.email.primary' => $user->getEmail(),
            '$Person.email.personal' => false,
            '$Person.webaddress' => false, //$user->getWebsite(),
            '$Person.sms' => false,

            '$CourseTemplate.sourcedId' => false,
            '$CourseTemplate.label' => false,
            '$CourseTemplate.title' => false,
            '$CourseTemplate.shortDescription' => false,
            '$CourseTemplate.longDescription' => false,
            '$CourseTemplate.courseNumber' => false,
            '$CourseTemplate.credits' => false,

            '$CourseOffering.sourcedId' => false,
            '$CourseOffering.label' => false,
            '$CourseOffering.title' => false,
            '$CourseOffering.shortDescription' => false,
            '$CourseOffering.longDescription' => false,
            '$CourseOffering.courseNumber' => false,
            '$CourseOffering.credits' => false,
            '$CourseOffering.academicSession' => false,

            '$CourseSection.sourcedId' => ['lis_course_section_sourcedid'],
            '$CourseSection.label' => $course->getCode(),
            '$CourseSection.title' => $course->getTitle(),
            '$CourseSection.shortDescription' => false,
            '$CourseSection.longDescription' => $session && $session->getShowDescription()
                ? $session->getDescription()
                : false,
            '$CourseSection.courseNumber' => false,
            '$CourseSection.credits' => false,
            '$CourseSection.maxNumberofStudents' => false,
            '$CourseSection.numberofStudents' => false,
            '$CourseSection.dept' => false,
            '$CourseSection.timeFrame.begin' => $session && $session->getDisplayStartDate()
                ? $session->getDisplayStartDate()->format(\DateTime::ATOM)
                : false,
            '$CourseSection.timeFrame.end' => $session && $session->getDisplayEndDate()
                ? $session->getDisplayEndDate()->format(\DateTime::ATOM)
                : false,
            '$CourseSection.enrollControl.accept' => false,
            '$CourseSection.enrollControl.allowed' => false,
            '$CourseSection.dataSource' => false,
            '$CourseSection.sourceSectionId' => false,

            '$Group.sourcedId' => false,
            '$Group.grouptype.scheme' => false,
            '$Group.grouptype.typevalue' => false,
            '$Group.grouptype.level' => false,
            '$Group.email' => false,
            '$Group.url' => false,
            '$Group.timeFrame.begin' => false,
            '$Group.timeFrame.end' => false,
            '$Group.enrollControl.accept' => false,
            '$Group.enrollControl.allowed' => false,
            '$Group.shortDescription' => false,
            '$Group.longDescription' => false,
            '$Group.parentId' => false,

            '$Membership.sourcedId' => false,
            '$Membership.collectionSourcedId' => false,
            '$Membership.personSourcedId' => false,
            '$Membership.status' => false,
            '$Membership.role' => ['roles'],
            '$Membership.createdTimestamp' => false,
            '$Membership.dataSource' => false,

            '$LineItem.sourcedId' => false,
            '$LineItem.type' => false,
            '$LineItem.type.displayName' => false,
            '$LineItem.resultValue.max' => false,
            '$LineItem.resultValue.list' => false,
            '$LineItem.dataSource' => false,

            '$Result.sourcedGUID' => ['lis_result_sourcedid'],
            '$Result.sourcedId' => ['lis_result_sourcedid'],
            '$Result.createdTimestamp' => false,
            '$Result.status' => false,
            '$Result.resultScore' => false,
            '$Result.dataSource' => false,

            '$ResourceLink.title' => ['resource_link_title'],
            '$ResourceLink.description' => ['resource_link_description'],
        ];
    }

    /**
     * @param array        $contentItem
     * @param ExternalTool $baseTool
     *
     * @return ExternalTool
     */
    private function createLtiLink(array &$contentItem, ExternalTool $baseTool)
    {
        $newTool = clone $baseTool;
        $newTool->setParent($baseTool);
        $newTool->setActiveDeepLinking(false);

        if (!empty($contentItem['title'])) {
            $newTool->setName($contentItem['title']);
        }

        if (!empty($contentItem['text'])) {
            $newTool->setDescription($contentItem['text']);
        }

        if (!empty($contentItem['url'])) {
            $newTool->setLaunchUrl($contentItem['url']);
        }

        if (!empty($contentItem['custom'])) {
            $newTool->setCustomParams(
                $newTool->encodeCustomParams($contentItem['custom'])
            );
        }

        $em = $this->getDoctrine()->getManager();

        $em->persist($newTool);
        $em->flush();

        $course = $newTool->getCourse();

        $courseTool = new CTool();
        $courseTool
            ->setCourse($course)
            ->setImage('plugin.png')
            ->setName($newTool->getName())
            ->setVisibility(true)
            ->setTarget('_self')
            ->setCategory('interaction')
            ->setLink(
                $this->generateUrl(
                    'chamilo_lti_show',
                    ['code' => $course->getCode(), 'id' => $newTool->getId()]
                )
            );

        $em->persist($courseTool);
        $em->flush();

        return $newTool;
    }
}
