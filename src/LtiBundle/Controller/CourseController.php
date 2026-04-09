<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Controller;

use Category;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Form\ExternalToolType;
use Chamilo\LtiBundle\Util\OAuth1Helper;
use Chamilo\LtiBundle\Util\Utils;
use Doctrine\Persistence\ManagerRegistry;
use EvalForm;
use Evaluation;
use HTML_QuickForm_select;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UserManager;

#[Route(path: '/courses/{cid}/lti')]
class CourseController extends ToolBaseController
{
    public function __construct(
        private readonly CShortcutRepository $shortcutRepository,
        private readonly ManagerRegistry $managerRegistry,
        private readonly UserHelper $userHelper,
    ) {}

    #[Route(path: '/edit/{id}', name: 'chamilo_lti_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $em = $this->managerRegistry->getManager();
        $repo = $em->getRepository(ExternalTool::class);

        /** @var ExternalTool|null $tool */
        $tool = $repo->find($id);

        if (empty($tool)) {
            throw $this->createNotFoundException('External tool not found');
        }

        $course = $this->getCourse();

        if (!$this->isToolInCourse($tool, $course)) {
            throw $this->createAccessDeniedException('');
        }

        $form = $this->createForm(ExternalToolType::class, $tool);
        $form->get('shareName')->setData($tool->isSharingName());
        $form->get('shareEmail')->setData($tool->isSharingEmail());
        $form->get('sharePicture')->setData($tool->isSharingPicture());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                '@ChamiloCore/Lti/course_configure.twig',
                $this->buildCourseConfigureViewData(
                    $repo,
                    $course,
                    $form,
                    $this->trans('Edit external tool'),
                    $request,
                    $this->buildRouteUrlWithLegacyContext(
                        'chamilo_lti_edit',
                        [
                            'id' => $tool->getId(),
                            'cid' => $course->getId(),
                        ],
                        $request,
                        $course
                    )
                )
            );
        }

        /** @var ExternalTool $tool */
        $tool = $form->getData();

        $em->persist($tool);

        if (!$tool->isActiveDeepLinking()) {
            $courseTool = $em->getRepository(CTool::class)->findOneBy(
                [
                    'course' => $course,
                    'link' => $this->generateUrl(
                        'chamilo_lti_show',
                        [
                            'cid' => $course->getId(),
                            'id' => $tool->getId(),
                        ]
                    ),
                ]
            );

            if (!empty($courseTool)) {
                $courseTool->setTitle($tool->getTitle());
                $em->persist($courseTool);
            }
        }

        $em->flush();

        $this->addFlash('success', $this->trans('External tool edited'));

        return $this->redirect(
            $this->buildRouteUrlWithLegacyContext(
                'chamilo_lti_edit',
                [
                    'id' => $tool->getId(),
                    'cid' => $course->getId(),
                ],
                $request,
                $course
            )
        );
    }

    #[Route(path: '/launch/{id}', name: 'chamilo_lti_launch', requirements: ['id' => '\d+'])]
    public function launch(int $id, Utils $ltiUtil): Response
    {
        $em = $this->managerRegistry->getManager();

        /** @var ExternalTool|null $tool */
        $tool = $em->find(ExternalTool::class, $id);

        if (empty($tool)) {
            throw $this->createNotFoundException();
        }

        $settingsManager = $this->get('chamilo.settings.manager');

        $user = $this->userHelper->getCurrent();
        $course = $this->getCourse();
        $session = $this->getCourseSession();

        if (!$this->isToolInCourse($tool, $course)) {
            throw $this->createAccessDeniedException('');
        }

        $institutionDomain = $ltiUtil->getInstitutionDomain();
        $toolUserId = $ltiUtil->generateToolUserId($user->getId());

        $params = [];
        $params['lti_version'] = 'LTI-1p0';

        if ($tool->isActiveDeepLinking()) {
            $params['lti_message_type'] = 'ContentItemSelectionRequest';
            $params['content_item_return_url'] = $this->generateUrl(
                'chamilo_lti_return_item',
                [
                    'cid' => $course->getId(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $params['accept_media_types'] = '*/*';
            $params['accept_presentation_document_targets'] = 'iframe';
            $params['title'] = $tool->getTitle();
            $params['text'] = $tool->getDescription();
            $params['data'] = 'tool:'.$tool->getId();
        } else {
            $params['lti_message_type'] = 'basic-lti-launch-request';
            $params['resource_link_id'] = $tool->getId();
            $params['resource_link_title'] = $tool->getTitle();
            $params['resource_link_description'] = $tool->getDescription();

            $toolEval = $tool->getGradebookEval();

            if (!empty($toolEval)) {
                $params['lis_result_sourcedid'] = json_encode(
                    [
                        'e' => $toolEval->getId(),
                        'u' => $user->getId(),
                        'l' => uniqid(),
                        'lt' => time(),
                    ]
                );
                $params['lis_outcome_service_url'] = api_get_path(WEB_PATH).'lti/os';
                $params['lis_person_sourcedid'] = "{$institutionDomain}:{$toolUserId}";
                $params['lis_course_section_sourcedid'] = "{$institutionDomain}:".$course->getId();

                if ($session) {
                    $params['lis_course_section_sourcedid'] .= ':'.$session->getId();
                }
            }
        }

        $params['user_id'] = $toolUserId;

        if ($tool->isSharingPicture()) {
            $params['user_image'] = UserManager::getUserPicture($user->getId());
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

        if ($user->isHRM()) {
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
        $params['launch_presentation_document_target'] = 'iframe' === $tool->getDocumentTarget()
            ? 'iframe'
            : 'window';
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
            $params = OAuth1Helper::buildSignedPostParams(
                (string) $tool->getLaunchUrl(),
                $params,
                (string) $tool->getConsumerKey(),
                (string) $tool->getSharedSecret()
            );
        }

        Utils::removeQueryParamsFromLaunchUrl($tool, $params);

        return $this->render(
            '@ChamiloCore/Lti/launch.html.twig',
            [
                'params' => $params,
                'launch_url' => $tool->getLaunchUrl(),
            ]
        );
    }

    #[Route(path: '/item_return', name: 'chamilo_lti_return_item')]
    public function returnItem(Request $request): Response
    {
        $contentItems = $request->get('content_items');
        $data = $request->get('data');

        if (empty($contentItems) || empty($data)) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->managerRegistry->getManager();

        /** @var ExternalTool|null $tool */
        $tool = $em->find(ExternalTool::class, str_replace('tool:', '', $data));

        if (empty($tool)) {
            throw $this->createNotFoundException('External tool not found');
        }

        $course = $this->getCourse();
        $url = $this->generateUrl(
            'chamilo_lti_return_item',
            [
                'cid' => $course->getId(),
            ],
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
            if (!\in_array($contentItem['@type'], $supportedItemTypes, true)) {
                continue;
            }

            if ('LtiLinkItem' === $contentItem['@type']) {
                $newTool = $this->createLtiLink($contentItem, $tool);

                $this->addFlash(
                    'success',
                    \sprintf(
                        $this->trans('External tool added: %s'),
                        $newTool->getTitle()
                    )
                );
            }
        }

        return $this->render(
            '@ChamiloCore/Lti/item_return.html.twig',
            [
                'course' => $course,
            ]
        );
    }

    #[Route(path: '/{id}', name: 'chamilo_lti_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $course = $this->getCourse();
        $em = $this->managerRegistry->getManager();

        /** @var ExternalTool|null $externalTool */
        $externalTool = $em->find(ExternalTool::class, $id);

        if (empty($externalTool)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isToolInCourse($externalTool, $course)) {
            throw $this->createAccessDeniedException('');
        }

        return $this->render(
            '@ChamiloCore/Lti/iframe.html.twig',
            [
                'tool' => $externalTool,
                'course' => $course,
            ]
        );
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route(path: '/', name: 'chamilo_lti_configure')]
    #[Route(path: '/add/{id}', name: 'chamilo_lti_configure_global', requirements: ['id' => '\d+'])]
    public function courseConfigure(?int $id, Request $request): Response
    {
        $em = $this->managerRegistry->getManager();
        $repo = $em->getRepository(ExternalTool::class);
        $course = $this->getCourse();

        $externalTool = new ExternalTool();

        if (null !== $id) {
            /** @var ExternalTool|null $parentTool */
            $parentTool = $repo->find($id);

            if (empty($parentTool) || !$this->isGlobalTool($parentTool)) {
                throw $this->createNotFoundException('External tool not found');
            }

            $externalTool = clone $parentTool;
            $externalTool->setToolParent($parentTool);
            $externalTool->setResourceNode(null);
        }

        $form = $this->createForm(ExternalToolType::class, $externalTool);
        $form->get('shareName')->setData($externalTool->isSharingName());
        $form->get('shareEmail')->setData($externalTool->isSharingEmail());
        $form->get('sharePicture')->setData($externalTool->isSharingPicture());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $formActionUrl = null === $id
                ? $this->buildRouteUrlWithLegacyContext(
                    'chamilo_lti_configure',
                    ['cid' => $course->getId()],
                    $request,
                    $course
                )
                : $this->buildRouteUrlWithLegacyContext(
                    'chamilo_lti_configure_global',
                    [
                        'cid' => $course->getId(),
                        'id' => $id,
                    ],
                    $request,
                    $course
                );

            return $this->render(
                '@ChamiloCore/Lti/course_configure.twig',
                $this->buildCourseConfigureViewData(
                    $repo,
                    $course,
                    $form,
                    $this->trans('Add external tool'),
                    $request,
                    $formActionUrl
                )
            );
        }

        /** @var ExternalTool $externalTool */
        $externalTool = $form->getData();

        /** @var Course|null $managedCourse */
        $managedCourse = $em->find(Course::class, $course->getId());

        if (null === $managedCourse) {
            throw $this->createNotFoundException('Course not found');
        }

        if (null === $externalTool->getParent()) {
            $externalTool->setParent($managedCourse);
        }

        if (null !== $externalTool->getToolParent()) {
            $externalTool->setResourceNode(null);
        }

        $externalTool->addCourseLink($managedCourse, $this->getCourseSession());

        $em->persist($externalTool);
        $em->flush();

        $this->addFlash('success', $this->trans('External tool added'));

        $user = $this->userHelper->getCurrent();

        if (!$externalTool->isActiveDeepLinking()) {
            $shortcut = $this->shortcutRepository->addShortCut($externalTool, $user, $managedCourse);

            $shortcut->setTitle($externalTool->getTitle());
            $shortcut->setShortCutNode($externalTool->getResourceNode());
            $shortcut->target = 'iframe' === $externalTool->getDocumentTarget() ? '_self' : '_blank';

            $em->persist($shortcut);
            $this->shortcutRepository->setVisibilityPublished($shortcut, $managedCourse, $this->getCourseSession());
            $em->flush();

            return $this->redirect(
                $this->buildRouteUrlWithLegacyContext(
                    'chamilo_core_course_home',
                    [
                        'cid' => $managedCourse->getId(),
                    ],
                    $request,
                    $managedCourse
                )
            );
        }

        return $this->redirect(
            $this->buildRouteUrlWithLegacyContext(
                'chamilo_lti_configure',
                [
                    'cid' => $managedCourse->getId(),
                ],
                $request,
                $managedCourse
            )
        );
    }

    #[Route(path: '/grade/{catId}', name: 'chamilo_lti_grade', requirements: ['catId' => '\d+'])]
    #[IsGranted('ROLE_TEACHER')]
    public function grade(int $catId, Request $request): Response
    {
        $em = $this->managerRegistry->getManager();
        $toolRepo = $em->getRepository(ExternalTool::class);
        $course = $this->getCourse();
        $selectedToolId = $request->query->getInt('toolId');
        $selectedTool = null;
        $user = $this->userHelper->getCurrent();
        $categories = Category::load(null, null, $course->getId());

        if (empty($categories)) {
            throw $this->createNotFoundException();
        }

        $courseTools = $this->getAddedToolsForCourse($toolRepo, $course);

        $evaladd = new Evaluation();
        $evaladd->set_user_id($user->getId());
        $evaladd->setCourseId($course->getId());
        $evaladd->set_category_id(!empty($catId) ? $catId : 0);

        $form = new EvalForm(
            EvalForm::TYPE_ADD,
            $evaladd,
            null,
            'add_eval_form'
        );

        $form->removeElement('name');
        $form->removeElement('addresult');

        /** @var HTML_QuickForm_select $slcLtiTools */
        $slcLtiTools = $form->createElement(
            'select',
            'name',
            $this->trans('External tool'),
            [],
            []
        );

        $form->insertElementBefore($slcLtiTools, 'hid_category_id');
        $form->addRule('name', get_lang('Required field'), 'required');

        $tools = $this->getGradeableToolsForCourse($toolRepo, $course);

        if ($selectedToolId > 0) {
            /** @var ExternalTool|null $selectedTool */
            $selectedTool = $toolRepo->find($selectedToolId);

            if (
                empty($selectedTool)
                || !$this->isToolInCourse($selectedTool, $course)
                || null !== $selectedTool->getGradebookEval()
            ) {
                throw $this->createNotFoundException('External tool not found');
            }
        }

        /** @var ExternalTool $tool */
        foreach ($tools as $tool) {
            $slcLtiTools->addOption($tool->getTitle(), $tool->getId());
        }

        if (null !== $selectedTool) {
            $form->setDefaults([
                'name' => $selectedTool->getId(),
            ]);
        }

        if (empty($tools)) {
            $this->addFlash('warning', $this->trans('There are no external tools available to add to the gradebook'));

            return $this->redirect(
                $this->buildRouteUrlWithLegacyContext(
                    'chamilo_lti_configure',
                    [
                        'cid' => $course->getId(),
                    ],
                    $request,
                    $course
                )
            );
        }

        if (!$form->validate()) {
            return $this->render('@ChamiloCore/Lti/gradebook.html.twig', [
                'form' => $form->returnForm(),
                'form_action_url' => $this->buildRouteUrlWithLegacyContext(
                    'chamilo_lti_grade',
                    [
                        'cid' => $course->getId(),
                        'catId' => $catId,
                    ],
                    $request,
                    $course
                ),
                'course' => $course,
                'title' => $this->trans('Add classroom activity'),
                'course_tools' => $courseTools,
                'selected_tool' => $selectedTool,
                'selected_category_id' => $catId,
                'legacy_context_query' => $this->getLegacyContextQueryString($request, $course),
            ]);
        }

        $values = $form->exportValues();

        /** @var ExternalTool|null $tool */
        $tool = $toolRepo->find((int) $values['name']);

        if (
            empty($tool)
            || !$this->isToolInCourse($tool, $course)
            || null !== $tool->getGradebookEval()
        ) {
            throw $this->createNotFoundException('External tool not found');
        }

        $eval = new Evaluation();
        $eval->set_name($tool->getTitle());
        $eval->set_description($values['description']);
        $eval->set_user_id($values['hid_user_id']);
        $eval->setCourseId($course->getId());
        $eval->set_category_id((int) $values['hid_category_id']);

        $values['weight'] = $values['weight_mask'];

        $eval->set_weight($values['weight']);
        $eval->set_max($values['max']);
        $eval->set_visible(empty($values['visible']) ? 0 : 1);
        $eval->add();

        if (empty($eval->get_id())) {
            $this->addFlash('error', $this->trans('The evaluation could not be created'));

            return $this->redirect(
                $this->buildRouteUrlWithLegacyContext(
                    'chamilo_lti_grade',
                    [
                        'cid' => $course->getId(),
                        'catId' => $catId,
                    ],
                    $request,
                    $course
                )
            );
        }

        $gradebookEval = $em->find(GradebookEvaluation::class, $eval->get_id());
        if (null === $gradebookEval) {
            $this->addFlash('error', $this->trans('The gradebook evaluation could not be linked'));

            return $this->redirect(
                $this->buildGradebookIndexUrl(
                    $course,
                    (int) $values['hid_category_id'],
                    $request
                )
            );
        }

        $tool->setGradebookEval($gradebookEval);
        $em->persist($tool);
        $em->flush();
        $this->addFlash('success', $this->trans('Evaluation for external tool added'));

        return $this->redirect(
            $this->buildGradebookIndexUrl(
                $course,
                (int) $values['hid_category_id'],
                $request
            )
        );
    }

    /**
     * @param mixed $repo
     *
     * @return ExternalTool[]
     */
    private function getAddedToolsForCourse($repo, Course $course): array
    {
        /** @var ExternalTool[] $allTools */
        $allTools = $repo->findAll();

        return array_values(array_filter(
            $allTools,
            function (ExternalTool $tool) use ($course): bool {
                $link = $this->getToolLinkInCourse($tool, $course);

                return null !== $link;
            }
        ));
    }

    /**
     * @param mixed $repo
     *
     * @return ExternalTool[]
     */
    private function getGradeableToolsForCourse($repo, Course $course): array
    {
        return array_values(array_filter(
            $this->getAddedToolsForCourse($repo, $course),
            static fn (ExternalTool $tool): bool => null === $tool->getGradebookEval()
        ));
    }

    private function isToolInCourse(ExternalTool $tool, Course $course): bool
    {
        return null !== $this->getToolLinkInCourse($tool, $course);
    }

    private function getToolLinkInCourse(ExternalTool $tool, Course $course): ?ResourceLink
    {
        $resourceNode = $tool->getResourceNode();

        if (null === $resourceNode) {
            return null;
        }

        $currentSession = $this->getCourseSession();
        $currentSessionId = $currentSession?->getId() ?? 0;
        $courseId = $course->getId();

        $fallbackCourseLink = null;

        foreach ($resourceNode->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();

            if (null === $linkCourse || $linkCourse->getId() !== $courseId) {
                continue;
            }

            $linkSession = $link->getSession();
            $linkSessionId = $linkSession?->getId() ?? 0;

            if ($currentSessionId > 0) {
                if ($linkSessionId === $currentSessionId) {
                    return $link;
                }

                if (0 === $linkSessionId && null === $fallbackCourseLink) {
                    $fallbackCourseLink = $link;
                }

                continue;
            }

            if (0 === $linkSessionId) {
                return $link;
            }

            if (null === $fallbackCourseLink) {
                $fallbackCourseLink = $link;
            }
        }

        return $fallbackCourseLink;
    }

    private function isGlobalTool(ExternalTool $tool): bool
    {
        return null === $tool->getFirstResourceLink() && null === $tool->getToolParent();
    }

    private function buildCourseConfigureViewData(
        $repo,
        Course $course,
        $form,
        string $title,
        Request $request,
        string $formActionUrl
    ): array {
        $categories = Category::load(null, null, $course->getId());
        $firstGradebookCategoryId = null;

        if (!empty($categories)) {
            $firstGradebookCategoryId = $categories[0]->get_id();
        }

        $addedTools = $this->getAddedToolsForCourse($repo, $course);

        $addedToolIds = array_map(
            static fn (ExternalTool $tool): int => $tool->getId(),
            $addedTools
        );

        $globalTools = array_values(array_filter(
            $repo->findAll(),
            function (ExternalTool $tool) use ($course, $addedToolIds): bool {
                if (\in_array($tool->getId(), $addedToolIds, true)) {
                    return false;
                }

                if ($this->isToolInCourse($tool, $course)) {
                    return false;
                }

                return $this->isGlobalTool($tool);
            }
        ));

        return [
            'title' => $title,
            'added_tools' => $addedTools,
            'global_tools' => $globalTools,
            'form' => $form,
            'form_action_url' => $formActionUrl,
            'course' => $course,
            'first_gradebook_category_id' => $firstGradebookCategoryId,
            'legacy_context_query' => $this->getLegacyContextQueryString($request, $course),
        ];
    }

    private function buildGradebookIndexUrl(Course $course, int $categoryId, Request $request): string
    {
        $params = $this->getLegacyContextParams($request, $course);
        $params['selectcat'] = $categoryId;

        return api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.http_build_query($params);
    }

    private function getLegacyContextParams(Request $request, Course $course): array
    {
        return [
            'cid' => (string) $course->getId(),
            'sid' => (string) ($request->query->get('sid') ?? 0),
            'gid' => (string) ($request->query->get('gid') ?? 0),
            'gradebook' => (string) ($request->query->get('gradebook') ?? 0),
            'origin' => (string) ($request->query->get('origin') ?? ''),
        ];
    }

    private function getLegacyContextQueryString(Request $request, Course $course): string
    {
        return http_build_query($this->getLegacyContextParams($request, $course));
    }

    private function buildRouteUrlWithLegacyContext(
        string $route,
        array $routeParams,
        Request $request,
        Course $course
    ): string {
        $url = $this->generateUrl($route, $routeParams);
        $query = $this->getLegacyContextQueryString($request, $course);

        if (empty($query)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').$query;
    }

    private function variableSubstitution(
        array $params,
        array &$customParams,
        User $user,
        Course $course,
        ?Session $session = null
    ): void {
        $replaceable = self::getReplaceableVariables($user, $course, $session);
        $variables = array_keys($replaceable);

        foreach ($customParams as $customKey => $customValue) {
            if (!\in_array($customValue, $variables, true)) {
                continue;
            }

            $val = $replaceable[$customValue];

            if (\is_array($val)) {
                $val = current($val);

                if (\array_key_exists($val, $params)) {
                    $customParams[$customKey] = $params[$val];

                    continue;
                }

                $val = false;
            }

            if (false === $val) {
                $customParams[$customKey] = $customValue;

                continue;
            }

            $customParams[$customKey] = $replaceable[$customValue];
        }
    }

    private static function getReplaceableVariables(User $user, Course $course, ?Session $session = null): array
    {
        return [
            '$User.id' => $user->getId(),
            '$User.image' => ['user_image'],
            '$User.username' => $user->getUsername(),

            '$Person.sourcedId' => false,
            '$Person.name.full' => $user->getFullName(),
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
            '$Person.address.timezone' => false,
            '$Person.phone.mobile' => false,
            '$Person.phone.primary' => $user->getPhone(),
            '$Person.phone.home' => false,
            '$Person.phone.work' => false,
            '$Person.email.primary' => $user->getEmail(),
            '$Person.email.personal' => false,
            '$Person.webaddress' => false,
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
                ? $session->getDisplayStartDate()->format('Y-m-d\TH:i:sP')
                : false,
            '$CourseSection.timeFrame.end' => $session && $session->getDisplayEndDate()
                ? $session->getDisplayEndDate()->format('Y-m-d\TH:i:sP')
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

    private function createLtiLink(array &$contentItem, ExternalTool $baseTool): ExternalTool
    {
        $newTool = clone $baseTool;
        $newTool->setToolParent($baseTool);
        $newTool->setActiveDeepLinking(false);
        $newTool->setResourceNode(null);

        if (!empty($contentItem['title'])) {
            $newTool->setTitle($contentItem['title']);
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

        $em = $this->managerRegistry->getManager();
        $course = $this->getCourse();
        $session = $this->getCourseSession();

        $newTool->addCourseLink($course, $session);

        $em->persist($newTool);
        $em->flush();

        $user = $this->userHelper->getCurrent();
        $this->shortcutRepository->addShortCut($newTool, $user, $course);

        return $newTool;
    }
}
