<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chamilo\CourseBundle\Settings\SettingsFormFactory;
use CourseManager;
use Database;
use DateTimeInterface;
use Display;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Event;
use Exception;
use Exercise;
use ExtraFieldValue;
use Graphp\GraphViz\GraphViz;
use IntlDateFormatter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/course')]
class CourseController extends ToolBaseController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SerializerInterface $serializer,
        private readonly UserHelper $userHelper,
        private readonly ManagerRegistry $doctrine,
    ) {}

    /**
     * Extend the controller service locator to include "doctrine".
     * This prevents runtime errors when legacy code tries to access it through the controller container.
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => ManagerRegistry::class,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{cid}/checkLegal.json', name: 'chamilo_core_course_check_legal_json')]
    public function checkTermsAndConditionJson(
        Request $request,
        LegalRepository $legalTermsRepo,
        LanguageRepository $languageRepository,
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        SettingsManager $settingsManager
    ): Response {
        $user = $this->userHelper->getCurrent();
        $course = $this->getCourse();
        $sid = $this->getSessionId();

        $responseData = [
            'redirect' => false,
            'url' => '#',
        ];

        if ($user->isStudent()
            && 'true' === $settingsManager->getSetting('registration.allow_terms_conditions', true)
            && 'course' === $settingsManager->getSetting('workflows.load_term_conditions_section', true)
        ) {
            $termAndConditionStatus = false;
            $extraValue = $extraFieldValuesRepository->findLegalAcceptByItemId($user->getId());
            if (!empty($extraValue['value'])) {
                $result = $extraValue['value'];
                $userConditions = explode(':', $result);
                $version = $userConditions[0];
                $langId = (int) $userConditions[1];
                $realVersion = $legalTermsRepo->getLastVersion($langId);
                $termAndConditionStatus = ($version >= $realVersion);
            }

            if (false === $termAndConditionStatus) {
                $request->getSession()->set('term_and_condition', ['user_id' => $user->getId()]);

                $redirect = true;

                if ('true' === $settingsManager->getSetting('course.allow_public_course_with_no_terms_conditions', true)
                    && Course::OPEN_WORLD === $course->getVisibility()
                ) {
                    $redirect = false;
                }

                if ($redirect && !$this->isGranted('ROLE_ADMIN')) {
                    $request->getSession()->remove('cid');
                    $request->getSession()->remove('course');

                    // Build return URL
                    $returnUrl = '/course/'.$course->getId().'/home?sid='.$sid;

                    $responseData = [
                        'redirect' => true,
                        'url' => '/main/auth/tc.php?return='.urlencode($returnUrl),
                    ];
                }
            } else {
                $request->getSession()->remove('term_and_condition');
            }
        }

        return new JsonResponse($responseData);
    }

    #[Route('/{cid}/home.json', name: 'chamilo_core_course_home_json')]
    public function indexJson(
        Request $request,
        CShortcutRepository $shortcutRepository,
        EntityManagerInterface $em,
        AssetRepository $assetRepository
    ): Response {
        // Handle drag & drop sort for course tools
        if ($request->isMethod('POST')) {
            $requestData = json_decode($request->getContent() ?: '', true) ?? [];

            if (isset($requestData['toolId'], $requestData['index'])) {
                $course = $this->getCourse();
                if (null === $course) {
                    return $this->json(
                        ['success' => false, 'message' => 'Course not found.'],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $sessionId = $this->getSessionId();
                $toolId = (int) $requestData['toolId'];
                $newIndex = (int) $requestData['index'];

                $result = $this->reorderCourseTools($em, $course, $sessionId, $toolId, $newIndex);
                $statusCode = $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

                return $this->json($result, $statusCode);
            }
        }

        $course = $this->getCourse();
        $sessionId = $this->getSessionId();
        $isInASession = $sessionId > 0;

        if (null === $course) {
            throw $this->createAccessDeniedException();
        }

        if (empty($sessionId)) {
            $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
        }

        $sessionHandler = $request->getSession();

        $userId = 0;

        $user = $this->userHelper->getCurrent();
        if (null !== $user) {
            $userId = $user->getId();
        }

        $courseCode = $course->getCode();
        $courseId = $course->getId();

        if ($user && $user->isInvitee()) {
            $isSubscribed = CourseManager::is_user_subscribed_in_course(
                $userId,
                $courseCode,
                $isInASession,
                $sessionId
            );

            if (!$isSubscribed) {
                throw $this->createAccessDeniedException();
            }
        }

        $isSpecialCourse = CourseManager::isSpecialCourse($courseId);

        if ($user && $isSpecialCourse && (isset($_GET['autoreg']) && 1 === (int) $_GET['autoreg'])
            && CourseManager::subscribeUser($userId, $courseId, STUDENT)
        ) {
            $sessionHandler->set('is_allowed_in_course', true);
        }

        $logInfo = [
            'tool' => 'course-main',
        ];
        Event::registerLog($logInfo);

        // Deleting the objects
        $sessionHandler->remove('toolgroup');
        $sessionHandler->remove('_gid');
        $sessionHandler->remove('oLP');
        $sessionHandler->remove('lpobject');

        api_remove_in_gradebook();
        Exercise::cleanSessionVariables();

        $shortcuts = [];
        if (null !== $user) {
            $shortcutQuery = $shortcutRepository->getResources($course->getResourceNode());
            $shortcuts = $shortcutQuery->getQuery()->getResult();

            $courseNodeId = $course->getResourceNode()->getId();
            $cid = $course->getId();
            $sid = $this->getSessionId() ?: null;

            /** @var CShortcut $shortcut */
            foreach ($shortcuts as $shortcut) {
                $resourceNode = $shortcut->getShortCutNode();

                // Try as CLink
                $cLink = $em->getRepository(CLink::class)->findOneBy(['resourceNode' => $resourceNode]);
                if ($cLink) {
                    // Image (if any)
                    $shortcut->setCustomImageUrl(
                        $cLink->getCustomImage()
                            ? $assetRepository->getAssetUrl($cLink->getCustomImage())
                            : null
                    );

                    // External link behavior
                    $shortcut->setUrlOverride($cLink->getUrl()); // open external URL
                    $shortcut->setIcon(null);                    // keep default icon for links
                    $shortcut->target = $cLink->getTarget();     // e.g. "_blank"

                    continue;
                }

                // Try as CBlog
                $cBlog = $em->getRepository(CBlog::class)
                    ->findOneBy(['resourceNode' => $resourceNode])
                ;

                if ($cBlog) {
                    $courseNodeId = $course->getResourceNode()->getId();
                    $cid = $course->getId();
                    $sid = $this->getSessionId() ?: null;

                    $qs = http_build_query(array_filter([
                        'cid' => $cid,
                        'sid' => $sid ?: null,
                        'gid' => 0,
                    ], static fn ($v) => null !== $v));

                    $shortcut->setUrlOverride(\sprintf(
                        '/resources/blog/%d/%d/posts?%s',
                        $courseNodeId,
                        $cBlog->getIid(),
                        $qs
                    ));
                    $shortcut->setIcon('mdi-notebook-outline');  // blog icon
                    $shortcut->setCustomImageUrl(null);          // blogs use icon by default
                    $shortcut->target = '_self';

                    continue;
                }

                // Fallback
                $shortcut->setCustomImageUrl(null);
                $shortcut->setUrlOverride(null);
                $shortcut->setIcon(null);
                $shortcut->target = '_self';
            }
        }
        $responseData = [
            'shortcuts' => $shortcuts,
            'diagram' => '',
        ];

        $json = $this->serializer->serialize(
            $responseData,
            'json',
            [
                'groups' => ['course:read', 'ctool:read', 'tool:read', 'cshortcut:read'],
            ]
        );

        return new Response(
            $json,
            Response::HTTP_OK,
            [
                'Content-type' => 'application/json',
            ]
        );
    }

    #[Route('/{cid}/thematic_progress.json', name: 'chamilo_core_course_thematic_progress_json', methods: ['GET'])]
    public function thematicProgressJson(
        Request $request,
        CThematicRepository $thematicRepository,
        UserHelper $userHelper,
        CidReqHelper $cidReqHelper,
        TranslatorInterface $translator,
        SettingsCourseManager $courseSettingsManager
    ): JsonResponse {
        $course = $this->getCourse();
        if (null === $course) {
            throw $this->createAccessDeniedException();
        }

        if (0 === $this->getSessionId()) {
            $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
        }

        $courseSettingsManager->setCourse($course);
        $displayMode = (string) $courseSettingsManager->getCourseSettingValue('display_info_advance_inside_homecourse');

        if ('' === $displayMode || '0' === $displayMode || '4' === $displayMode) {
            return new JsonResponse(['enabled' => false]);
        }

        $sessionEntity = $cidReqHelper->getSessionEntity();
        $currentUser = $userHelper->getCurrent();

        $advance1 = null;
        $advance2 = null;
        $subtitle1 = '';
        $subtitle2 = '';

        if ('1' === $displayMode) {
            // Last completed topic only
            $advance1 = $thematicRepository->findLastDoneAdvanceForCourse($course, $sessionEntity);
            if (null !== $advance1) {
                $subtitle1 = $translator->trans('Current topic');
            }
        } elseif ('2' === $displayMode) {
            // Two next not done topics
            $nextList = $thematicRepository->findNextNotDoneAdvancesForCourse($course, $sessionEntity, 2);

            if (isset($nextList[0]) && $nextList[0] instanceof CThematicAdvance) {
                $advance1 = $nextList[0];
                $subtitle1 = $translator->trans('Next topic');
            }

            if (isset($nextList[1]) && $nextList[1] instanceof CThematicAdvance) {
                $advance2 = $nextList[1];
                $subtitle2 = $translator->trans('Next topic');
            }
        } elseif ('3' === $displayMode) {
            // Current (last done) + next not done
            $advance1 = $thematicRepository->findLastDoneAdvanceForCourse($course, $sessionEntity);
            $nextList = $thematicRepository->findNextNotDoneAdvancesForCourse($course, $sessionEntity, 1);

            if (null !== $advance1) {
                $subtitle1 = $translator->trans('Current topic');
            }

            if (isset($nextList[0]) && $nextList[0] instanceof CThematicAdvance) {
                $advance2 = $nextList[0];
                $subtitle2 = $translator->trans('Next topic');
            }
        } else {
            return new JsonResponse(['enabled' => false]);
        }

        if (null === $advance1 && null === $advance2) {
            return new JsonResponse(['enabled' => false]);
        }

        $locale = $request->getLocale();
        $timezoneId = null;

        if ($currentUser && method_exists($currentUser, 'getTimezone') && $currentUser->getTimezone()) {
            $timezoneId = $currentUser->getTimezone();
        }

        if (empty($timezoneId)) {
            $timezoneId = date_default_timezone_get();
        }

        $dateFormatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            $timezoneId
        );

        $buildItem = function (CThematicAdvance $advance, string $type, string $label) use ($dateFormatter): array {
            $thematic = $advance->getThematic();

            $startDate = $advance->getStartDate();
            $formattedDate = $startDate instanceof DateTimeInterface
                ? (string) $dateFormatter->format($startDate)
                : '';

            return [
                'type' => $type,
                'label' => $label,
                'title' => strip_tags($thematic->getTitle() ?? ''),
                'startDate' => $formattedDate,
                'content' => strip_tags($advance->getContent() ?? ''),
                'duration' => (float) $advance->getDuration(),
            ];
        };

        $items = [];

        if (null !== $advance1) {
            $firstType = ('1' === $displayMode || '3' === $displayMode) ? 'current' : 'next';
            $items[] = $buildItem($advance1, $firstType, $subtitle1);
        }

        if (null !== $advance2) {
            $items[] = $buildItem($advance2, 'next', $subtitle2);
        }

        $userPayload = null;
        if ($currentUser) {
            $name = method_exists($currentUser, 'getCompleteName')
                ? $currentUser->getCompleteName()
                : trim(\sprintf('%s %s', $currentUser->getFirstname(), $currentUser->getLastname()));

            $userPayload = [
                'name' => $name,
                'avatar' => null,
            ];
        }

        $thematicUrl = '/main/course_progress/index.php?cid='.$course->getId().'&sid='.$this->getSessionId().'&action=thematic_details';
        $thematicScoreRaw = $thematicRepository->calculateTotalAverageForCourse($course, $sessionEntity);
        $thematicScore = $thematicScoreRaw.'%';

        $payload = [
            'enabled' => true,
            'displayMode' => (int) $displayMode,
            'title' => $translator->trans('Thematic advance'),
            'score' => $thematicScore,
            'scoreRaw' => $thematicScoreRaw,
            'user' => $userPayload,
            'items' => $items,
            'detailUrl' => $thematicUrl,
            'labels' => [
                'duration' => $translator->trans('Duration in hours'),
                'seeDetail' => $translator->trans('See detail'),
            ],
        ];

        return new JsonResponse($payload);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{courseId}/next-course', name: 'chamilo_course_next_course')]
    public function getNextCourse(
        int $courseId,
        Request $request,
        SequenceResourceRepository $repo,
        Security $security,
        SettingsManager $settingsManager,
        EntityManagerInterface $em
    ): JsonResponse {
        $sessionId = $request->query->getInt('sid');
        $useDependents = $request->query->getBoolean('dependents', false);
        $user = $security->getUser();

        if (null === $user || !method_exists($user, 'getId')) {
            return new JsonResponse(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();

        if ($useDependents) {
            $sequences = $repo->getDependents($courseId, SequenceResource::COURSE_TYPE);
            $checked = $repo->checkDependentsForUser($sequences, SequenceResource::COURSE_TYPE, $userId, $sessionId, $courseId);
            $isUnlocked = $repo->checkSequenceAreCompleted($checked);
            $sequenceResource = $repo->findRequirementForResource($courseId, SequenceResource::COURSE_TYPE);
        } else {
            $sequences = $repo->getRequirements($courseId, SequenceResource::COURSE_TYPE);

            $hasValidRequirement = false;
            foreach ($sequences as $sequence) {
                foreach ($sequence['requirements'] ?? [] as $resource) {
                    if ($resource instanceof Course) {
                        $hasValidRequirement = true;

                        break 2;
                    }
                }
            }

            if (!$hasValidRequirement) {
                return new JsonResponse([]);
            }

            $checked = $repo->checkRequirementsForUser($sequences, SequenceResource::COURSE_TYPE, $userId, $sessionId);
            $isUnlocked = $repo->checkSequenceAreCompleted($checked);
            $sequenceResource = $repo->findRequirementForResource($courseId, SequenceResource::COURSE_TYPE);
        }

        $graphImage = null;

        if ($sequenceResource && $sequenceResource->hasGraph()) {
            $graph = $sequenceResource->getSequence()->getUnSerializeGraph();
            if (null !== $graph) {
                $graph->setAttribute('graphviz.node.fontname', 'arial');
                $graphviz = new GraphViz();
                $graphImage = $graphviz->createImageSrc($graph);
            }
        }

        return new JsonResponse([
            'sequenceList' => array_values($checked),
            'allowSubscription' => $isUnlocked,
            'graph' => $graphImage,
        ]);
    }

    /**
     * Redirects the page to a tool, following the tools settings.
     */
    #[Route('/{cid}/tool/{toolName}', name: 'chamilo_core_course_redirect_tool')]
    public function redirectTool(
        Request $request,
        string $toolName,
        CToolRepository $repo,
        ToolChain $toolChain
    ): RedirectResponse {
        /** @var CTool|null $tool */
        $tool = $repo->findOneBy([
            'title' => $toolName,
        ]);

        if (null === $tool) {
            throw new NotFoundHttpException($this->trans('Tool not found'));
        }

        $tool = $toolChain->getToolFromName($tool->getTool()->getTitle());
        $link = $tool->getLink();

        if (null === $this->getCourse()) {
            throw new NotFoundHttpException($this->trans('Course not found'));
        }
        $optionalParams = '';

        $optionalParams = $request->query->get('cert') ? '&cert='.$request->query->get('cert') : '';

        if (strpos($link, 'nodeId')) {
            $nodeId = (string) $this->getCourse()->getResourceNode()->getId();
            $link = str_replace(':nodeId', $nodeId, $link);
        }

        $url = $link.'?'.$this->getCourseUrlQuery().$optionalParams;

        return $this->redirect($url);
    }

    /**
     * Edit configuration with given namespace.
     */
    #[Route('/{course}/settings/{namespace}', name: 'chamilo_core_course_settings')]
    public function updateSettings(
        Request $request,
        #[MapEntity(expr: 'repository.find(cid)')]
        Course $course,
        string $namespace,
        SettingsCourseManager $manager,
        SettingsFormFactory $formFactory
    ): Response {
        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $schemaAlias = $manager->convertNameSpaceToService($namespace);
        $settings = $manager->load($namespace);

        $form = $formFactory->create($schemaAlias);

        $form->setData($settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageType = 'success';

            try {
                $manager->setCourse($course);
                $manager->save($form->getData());
                $message = $this->trans('Update');
            } catch (ValidatorException $validatorException) {
                $message = $this->trans($validatorException->getMessage());
                $messageType = 'error';
            }
            $this->addFlash($messageType, $message);

            if ($request->headers->has('referer')) {
                return $this->redirect($request->headers->get('referer'));
            }
        }

        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloCore/Course/settings.html.twig',
            [
                'course' => $course,
                'schemas' => $schemas,
                'settings' => $settings,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/about', name: 'chamilo_core_course_about')]
    public function about(
        Course $course,
        IllustrationRepository $illustrationRepository,
        CCourseDescriptionRepository $courseDescriptionRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $courseId = $course->getId();

        $user = $this->userHelper->getCurrent();

        $fieldsRepo = $em->getRepository(ExtraField::class);

        /** @var TagRepository $tagRepo */
        $tagRepo = $em->getRepository(Tag::class);

        $courseDescriptions = $courseDescriptionRepository->getResourcesByCourse($course)->getQuery()->getResult();

        $courseValues = new ExtraFieldValue('course');

        $urlCourse = api_get_path(WEB_PATH).\sprintf('course/%s/about', $courseId);
        $courseTeachers = $course->getTeachersSubscriptions();
        $teachersData = [];

        foreach ($courseTeachers as $teacherSubscription) {
            $teacher = $teacherSubscription->getUser();
            $userData = [
                'complete_name' => UserManager::formatUserFullName($teacher),
                'image' => $illustrationRepository->getIllustrationUrl($teacher),
                'diploma' => $teacher->getDiplomas(),
                'openarea' => $teacher->getOpenarea(),
            ];

            $teachersData[] = $userData;
        }

        /** @var ExtraField $tagField */
        $tagField = $fieldsRepo->findOneBy([
            'itemType' => ExtraField::COURSE_FIELD_TYPE,
            'variable' => 'tags',
        ]);

        $courseTags = [];
        if (null !== $tagField) {
            $courseTags = $tagRepo->getTagsByItem($tagField, $courseId);
        }

        $courseDescription = $courseObjectives = $courseTopics = $courseMethodology = '';
        $courseMaterial = $courseResources = $courseAssessment = '';
        $courseCustom = [];
        foreach ($courseDescriptions as $descriptionTool) {
            switch ($descriptionTool->getDescriptionType()) {
                case CCourseDescription::TYPE_DESCRIPTION:
                    $courseDescription = $descriptionTool->getContent();

                    break;

                case CCourseDescription::TYPE_OBJECTIVES:
                    $courseObjectives = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_TOPICS:
                    $courseTopics = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_METHODOLOGY:
                    $courseMethodology = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_COURSE_MATERIAL:
                    $courseMaterial = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_RESOURCES:
                    $courseResources = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_ASSESSMENT:
                    $courseAssessment = $descriptionTool;

                    break;

                case CCourseDescription::TYPE_CUSTOM:
                    $courseCustom[] = $descriptionTool;

                    break;
            }
        }

        $topics = [
            'objectives' => $courseObjectives,
            'topics' => $courseTopics,
            'methodology' => $courseMethodology,
            'material' => $courseMaterial,
            'resources' => $courseResources,
            'assessment' => $courseAssessment,
            'custom' => array_reverse($courseCustom),
        ];

        $subscriptionUser = false;

        if ($user) {
            $subscriptionUser = CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode());
        }

        $allowSubscribe = CourseManager::canUserSubscribeToCourse($course->getCode());

        $image = Container::getIllustrationRepository()->getIllustrationUrl($course, 'course_picture_medium');

        $params = [
            'course' => $course,
            'description' => $courseDescription,
            'image' => $image,
            'syllabus' => $topics,
            'tags' => $courseTags,
            'teachers' => $teachersData,
            'extra_fields' => $courseValues->getAllValuesForAnItem(
                $course->getId(),
                null,
                true
            ),
            'subscription' => $subscriptionUser,
            'url' => '',
            'is_premium' => '',
            'token' => '',
            'base_url' => $request->getSchemeAndHttpHost(),
            'allow_subscribe' => $allowSubscribe,
        ];

        $metaInfo = '<meta property="og:url" content="'.$urlCourse.'" />';
        $metaInfo .= '<meta property="og:type" content="website" />';
        $metaInfo .= '<meta property="og:title" content="'.$course->getTitle().'" />';
        $metaInfo .= '<meta property="og:description" content="'.strip_tags($courseDescription).'" />';
        $metaInfo .= '<meta property="og:image" content="'.$image.'" />';

        $htmlHeadXtra[] = $metaInfo;
        $htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');

        return $this->render('@ChamiloCore/Course/about.html.twig', $params);
    }

    #[Route('/{id}/welcome', name: 'chamilo_core_course_welcome')]
    public function welcome(Course $course): Response
    {
        return $this->render('@ChamiloCore/Course/welcome.html.twig', [
            'course' => $course,
        ]);
    }

    private function findIntroOfCourse(Course $course): ?CTool
    {
        $qb = $this->em->createQueryBuilder();

        $query = $qb->select('ct')
            ->from(CTool::class, 'ct')
            ->where('ct.course = :c_id')
            ->andWhere('ct.title = :title')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('ct.session', ':session_id'),
                    $qb->expr()->isNull('ct.session')
                )
            )
            ->setParameters([
                'c_id' => $course->getId(),
                'title' => 'course_homepage',
                'session_id' => 0,
            ])
            ->getQuery()
        ;

        $results = $query->getResult();

        return \count($results) > 0 ? $results[0] : null;
    }

    #[Route('/{id}/getToolIntro', name: 'chamilo_core_course_gettoolintro')]
    public function getToolIntro(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        $sessionId = (int) $request->query->get('sid', 0);

        $session = null;
        if ($sessionId > 0) {
            $session = $em->getRepository(Session::class)->find($sessionId);
        }

        $ctoolRepo = $em->getRepository(CTool::class);
        $ctoolintroRepo = $em->getRepository(CToolIntro::class);

        // Base tool + base intro (course context, no session).
        $baseTool = $this->findIntroOfCourse($course);
        if (!$baseTool) {
            // ensure the base tool exists (should rarely happen).
            $baseTool = $this->ensureCourseHomepageTool($course, null, $em);
        }

        $baseIntro = null;
        if ($baseTool) {
            $baseIntro = $ctoolintroRepo->findOneBy(
                ['courseTool' => $baseTool],
                ['iid' => 'DESC']
            );
        }

        $activeTool = $baseTool;
        $activeIntro = $baseIntro;
        $createInSession = false;

        if ($session) {
            // Ensure the session tool exists so the frontend can create the intro in the right context.
            $sessionTool = $ctoolRepo->findOneBy([
                'title' => 'course_homepage',
                'course' => $course,
                'session' => $session,
            ]);

            if (!$sessionTool) {
                $sessionTool = $this->ensureCourseHomepageTool($course, $session, $em);
            }

            // Use session tool for editing/creation in session context.
            if ($sessionTool) {
                $activeTool = $sessionTool;

                $sessionIntro = $ctoolintroRepo->findOneBy(
                    ['courseTool' => $sessionTool],
                    ['iid' => 'DESC']
                );

                if ($sessionIntro) {
                    // Session-specific intro exists: show it.
                    $activeIntro = $sessionIntro;
                    $createInSession = false;
                } else {
                    // No session-specific intro yet: show base intro (if any), but allow creating in session.
                    $activeIntro = $baseIntro;
                    $createInSession = true;
                }
            } else {
                // If session tool cannot be created, fallback to base (display only).
                $activeTool = $baseTool;
                $activeIntro = $baseIntro;
                $createInSession = false;
            }
        }

        $responseData = [
            'createInSession' => $createInSession,
        ];

        if ($activeTool) {
            $responseData['cToolId'] = $activeTool->getIid();
            $responseData['c_tool'] = [
                'iid' => $activeTool->getIid(),
                'title' => $activeTool->getTitle(),
            ];
        }

        if ($activeIntro) {
            $responseData['iid'] = $activeIntro->getIid();
            $responseData['introText'] = $activeIntro->getIntroText();
        }

        return new JsonResponse($responseData);
    }

    #[Route('/{id}/addToolIntro', name: 'chamilo_core_course_addtoolintro')]
    public function addToolIntro(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent());
        $sessionId = $data->sid ?? ($data->resourceLinkList[0]->sid ?? 0);
        $introText = $data->introText ?? null;

        $session = $sessionId ? $em->getRepository(Session::class)->find($sessionId) : null;
        $ctoolRepo = $em->getRepository(CTool::class);
        $ctoolintroRepo = $em->getRepository(CToolIntro::class);

        $ctoolSession = $ctoolRepo->findOneBy([
            'title' => 'course_homepage',
            'course' => $course,
            'session' => $session,
        ]);

        if (!$ctoolSession) {
            $toolEntity = $em->getRepository(Tool::class)->findOneBy(['title' => 'course_homepage']);
            if ($toolEntity) {
                $ctoolSession = (new CTool())
                    ->setTool($toolEntity)
                    ->setTitle('course_homepage')
                    ->setCourse($course)
                    ->setPosition(1)
                    ->setParent($course)
                    ->setCreator($course->getCreator())
                    ->setSession($session)
                    ->addCourseLink($course)
                ;

                $em->persist($ctoolSession);
                $em->flush();
            }
        }

        $ctoolIntro = $ctoolintroRepo->findOneBy(['courseTool' => $ctoolSession]);
        if (!$ctoolIntro) {
            $ctoolIntro = (new CToolIntro())
                ->setCourseTool($ctoolSession)
                ->setIntroText($introText ?? '')
                ->setParent($course)
            ;

            $em->persist($ctoolIntro);
            $em->flush();

            return new JsonResponse([
                'status' => 'created',
                'cToolId' => $ctoolSession->getIid(),
                'iid' => $ctoolIntro->getIid(),
                'introIid' => $ctoolIntro->getIid(),
                'introText' => $ctoolIntro->getIntroText(),
            ]);
        }

        if (null !== $introText) {
            $ctoolIntro->setIntroText($introText);
            $em->persist($ctoolIntro);
            $em->flush();

            return new JsonResponse([
                'status' => 'updated',
                'cToolId' => $ctoolSession->getIid(),
                'iid' => $ctoolIntro->getIid(),
                'introIid' => $ctoolIntro->getIid(),
                'introText' => $ctoolIntro->getIntroText(),
            ]);
        }

        return new JsonResponse(['status' => 'no_action']);
    }

    #[Route('/check-enrollments', name: 'chamilo_core_check_enrollments', methods: ['GET'])]
    public function checkEnrollments(EntityManagerInterface $em, SettingsManager $settingsManager): JsonResponse
    {
        $user = $this->userHelper->getCurrent();

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $isEnrolledInCourses = $this->isUserEnrolledInAnyCourse($user, $em);
        $isEnrolledInSessions = $this->isUserEnrolledInAnySession($user, $em);

        if (!$isEnrolledInCourses && !$isEnrolledInSessions) {
            $defaultMenuEntry = $settingsManager->getSetting('workflows.default_menu_entry_for_course_or_session');
            $isEnrolledInCourses = 'my_courses' === $defaultMenuEntry;
            $isEnrolledInSessions = 'my_sessions' === $defaultMenuEntry;
        }

        return new JsonResponse([
            'isEnrolledInCourses' => $isEnrolledInCourses,
            'isEnrolledInSessions' => $isEnrolledInSessions,
        ]);
    }

    #[Route('/categories', name: 'chamilo_core_course_form_lists')]
    public function getCategories(
        SettingsManager $settingsManager,
        AccessUrlHelper $accessUrlHelper,
        CourseCategoryRepository $courseCategoriesRepo
    ): JsonResponse {
        $allowBaseCourseCategory = 'true' === $settingsManager->getSetting('course.allow_base_course_category');
        $accessUrlId = $accessUrlHelper->getCurrent()->getId();

        $categories = $courseCategoriesRepo->findAllInAccessUrl(
            $accessUrlId,
            $allowBaseCourseCategory
        );

        $data = [];
        $categoryToAvoid = '';
        if (!$this->isGranted('ROLE_ADMIN')) {
            $categoryToAvoid = $settingsManager->getSetting('course.course_category_code_to_use_as_model');
        }

        foreach ($categories as $category) {
            $categoryCode = $category->getCode();
            if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
                continue;
            }
            $data[] = ['id' => $category->getId(), 'name' => $category->__toString()];
        }

        return new JsonResponse($data);
    }

    #[Route('/search_templates', name: 'chamilo_core_course_search_templates')]
    public function searchCourseTemplates(
        Request $request,
        AccessUrlHelper $accessUrlUtil,
        CourseRepository $courseRepository
    ): JsonResponse {
        $searchTerm = $request->query->get('search', '');
        $accessUrl = $accessUrlUtil->getCurrent();

        $user = $this->userHelper->getCurrent();

        $courseList = $courseRepository->getCoursesInfoByUser($user, $accessUrl, 1, $searchTerm);
        $results = ['items' => []];
        foreach ($courseList as $course) {
            $title = $course['title'];
            $results['items'][] = [
                'id' => $course['id'],
                'name' => $title.' ('.$course['code'].') ',
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/create', name: 'chamilo_core_course_create')]
    public function createCourse(
        Request $request,
        TranslatorInterface $translator,
        CourseHelper $courseHelper
    ): JsonResponse {
        $courseData = json_decode($request->getContent(), true);

        $title = $courseData['name'] ?? null;
        $wantedCode = $courseData['code'] ?? null;
        $courseLanguage = $courseData['language'] ?? null;
        $categoryCode = $courseData['category'] ?? null;
        $exemplaryContent = $courseData['fillDemoContent'] ?? false;
        $template = $courseData['template'] ?? '';

        $params = [
            'title' => $title,
            'wanted_code' => $wantedCode,
            'course_language' => $courseLanguage,
            'exemplary_content' => $exemplaryContent,
            'course_template' => $template,
        ];

        if ($categoryCode) {
            $params['course_categories'] = $categoryCode;
        }

        try {
            $course = $courseHelper->createCourse($params);
            if ($course) {
                return new JsonResponse([
                    'success' => true,
                    'message' => $translator->trans('Course created successfully.'),
                    'courseId' => $course->getId(),
                ]);
            }
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $translator->trans($e->getMessage()),
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => false, 'message' => $translator->trans('An error occurred while creating the course.')]);
    }

    #[Route('/{id}/getAutoLaunchExerciseId', name: 'chamilo_core_course_get_auto_launch_exercise_id', methods: ['GET'])]
    public function getAutoLaunchExerciseId(
        Request $request,
        Course $course,
        CQuizRepository $quizRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $request->getContent();
        $data = json_decode($data);
        $sessionId = $data->sid ?? 0;

        $sessionRepo = $em->getRepository(Session::class);
        $session = null;
        if (!empty($sessionId)) {
            $session = $sessionRepo->find($sessionId);
        }

        $autoLaunchExerciseId = $quizRepository->findAutoLaunchableQuizByCourseAndSession($course, $session);

        return new JsonResponse(['exerciseId' => $autoLaunchExerciseId], Response::HTTP_OK);
    }

    #[Route('/{id}/getAutoLaunchLPId', name: 'chamilo_core_course_get_auto_launch_lp_id', methods: ['GET'])]
    public function getAutoLaunchLPId(
        Request $request,
        Course $course,
        CLpRepository $lpRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = $request->getContent();
        $data = json_decode($data);
        $sessionId = $data->sid ?? 0;

        $sessionRepo = $em->getRepository(Session::class);
        $session = null;
        if (!empty($sessionId)) {
            $session = $sessionRepo->find($sessionId);
        }

        $autoLaunchLPId = $lpRepository->findAutoLaunchableLPByCourseAndSession($course, $session);

        return new JsonResponse(['lpId' => $autoLaunchLPId], Response::HTTP_OK);
    }

    private function autoLaunch(): void
    {
        $autoLaunchWarning = '';
        $showAutoLaunchLpWarning = false;
        $course_id = api_get_course_int_id();
        $lpAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
        $session_id = api_get_session_id();
        $allowAutoLaunchForCourseAdmins =
            api_is_platform_admin()
            || api_is_allowed_to_edit(true, true)
            || api_is_coach();

        if (!empty($lpAutoLaunch)) {
            if (2 === $lpAutoLaunch) {
                // LP list
                if ($allowAutoLaunchForCourseAdmins) {
                    $showAutoLaunchLpWarning = true;
                } else {
                    $session_key = 'lp_autolaunch_'.$session_id.'_'.$course_id.'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        // Redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq();
                        $_SESSION[$session_key] = true;
                        header(\sprintf('Location: %s', $url));

                        exit;
                    }
                }
            } else {
                $lp_table = Database::get_course_table(TABLE_LP_MAIN);
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT id FROM {$lp_table}
                            WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                            LIMIT 1";
                    $result = Database::query($sql);
                    // If we found nothing in the session we just called the session_id =  0 autolaunch
                    if (0 === Database::num_rows($result)) {
                        $condition = '';
                    }
                }

                $sql = "SELECT iid FROM {$lp_table}
                        WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                        LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $lp_data = Database::fetch_array($result);
                    if (!empty($lp_data['iid'])) {
                        if ($allowAutoLaunchForCourseAdmins) {
                            $showAutoLaunchLpWarning = true;
                        } else {
                            $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                            if (!isset($_SESSION[$session_key])) {
                                // Redirecting to the LP
                                $url = api_get_path(WEB_CODE_PATH).
                                    'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['iid'];

                                $_SESSION[$session_key] = true;
                                header(\sprintf('Location: %s', $url));

                                exit;
                            }
                        }
                    }
                }
            }
        }

        if ($showAutoLaunchLpWarning) {
            $autoLaunchWarning = get_lang(
                'The learning path auto-launch setting is ON. When learners enter this course, they will be automatically redirected to the learning path marked as auto-launch.'
            );
        }

        $forumAutoLaunch = (int) api_get_course_setting('enable_forum_auto_launch');
        if (1 === $forumAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        "The forum's auto-launch setting is on. Students will be redirected to the forum tool when entering this course."
                    );
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
                header(\sprintf('Location: %s', $url));

                exit;
            }
        }

        $exerciseAutoLaunch = (int) api_get_course_setting('enable_exercise_auto_launch');
        if (2 === $exerciseAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToTheExerciseList'
                    );
                }
            } else {
                // Redirecting to the document
                $url = api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq();
                header(\sprintf('Location: %s', $url));

                exit;
            }
        } elseif (1 === $exerciseAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        'TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise'
                    );
                }
            } else {
                // Redirecting to an exercise
                $table = Database::get_course_table(TABLE_QUIZ_TEST);
                $condition = '';
                if (!empty($session_id)) {
                    $condition = api_get_session_condition($session_id);
                    $sql = "SELECT iid FROM {$table}
                            WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                            LIMIT 1";
                    $result = Database::query($sql);
                    // If we found nothing in the session we just called the session_id = 0 autolaunch
                    if (0 === Database::num_rows($result)) {
                        $condition = '';
                    }
                }

                $sql = "SELECT iid FROM {$table}
                        WHERE c_id = {$course_id} AND autolaunch = 1 {$condition}
                        LIMIT 1";
                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    $row = Database::fetch_array($result);
                    $exerciseId = $row['iid'];
                    $url = api_get_path(WEB_CODE_PATH).
                        'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq();
                    header(\sprintf('Location: %s', $url));

                    exit;
                }
            }
        }

        $documentAutoLaunch = (int) api_get_course_setting('enable_document_auto_launch');
        if (1 === $documentAutoLaunch) {
            if ($allowAutoLaunchForCourseAdmins) {
                if (empty($autoLaunchWarning)) {
                    $autoLaunchWarning = get_lang(
                        'The document auto-launch feature configuration is enabled. Learners will be automatically redirected to document tool.'
                    );
                }
            } else {
                // Redirecting to the document
                $url = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq();
                header("Location: $url");

                exit;
            }
        }

        /*  SWITCH TO A DIFFERENT HOMEPAGE VIEW
         the setting homepage_view is adjustable through
         the platform administration section */
        if (!empty($autoLaunchWarning)) {
            $this->addFlash(
                'warning',
                Display::return_message(
                    $autoLaunchWarning,
                    'warning'
                )
            );
        }
    }

    /**
     * Ensure a "course_homepage" CTool exists for the given course + session context.
     * - If $session is null, it creates/returns the base tool.
     * - If $session is not null, it creates/returns the session tool.
     */
    private function ensureCourseHomepageTool(Course $course, ?Session $session, EntityManagerInterface $em): ?CTool
    {
        $ctoolRepo = $em->getRepository(CTool::class);

        $existing = $ctoolRepo->findOneBy([
            'title' => 'course_homepage',
            'course' => $course,
            'session' => $session,
        ]);

        if ($existing) {
            return $existing;
        }

        $toolEntity = $em->getRepository(Tool::class)->findOneBy(['title' => 'course_homepage']);
        if (!$toolEntity) {
            return null;
        }

        $ctool = (new CTool())
            ->setTool($toolEntity)
            ->setTitle('course_homepage')
            ->setCourse($course)
            ->setPosition(1)
            ->setParent($course)
            ->setCreator($course->getCreator())
            ->setSession($session)
            ->addCourseLink($course)
        ;

        $em->persist($ctool);
        $em->flush();

        return $ctool;
    }

    // Implement the real logic to check course enrollment
    private function isUserEnrolledInAnyCourse(User $user, EntityManagerInterface $em): bool
    {
        $enrollmentCount = $em
            ->getRepository(CourseRelUser::class)
            ->count(['user' => $user])
        ;

        return $enrollmentCount > 0;
    }

    // Implement the real logic to check session enrollment
    private function isUserEnrolledInAnySession(User $user, EntityManagerInterface $em): bool
    {
        $enrollmentCount = $em->getRepository(SessionRelUser::class)
            ->count(['user' => $user])
        ;

        return $enrollmentCount > 0;
    }

    /**
     * Reorders all course tools for a given course / session after drag & drop.
     *
     * @return array<string, mixed>
     */
    private function reorderCourseTools(
        EntityManagerInterface $em,
        Course $course,
        int $sessionId,
        int $toolId,
        int $newIndex
    ): array {
        if ($toolId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid tool id.',
            ];
        }

        /** @var CToolRepository $toolRepo */
        $toolRepo = $em->getRepository(CTool::class);

        // Load all tools for this course + (optional) session ordered by current position
        $qb = $toolRepo->createQueryBuilder('t')
            ->andWhere('t.course = :course')
            ->setParameter('course', $course->getId())
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.iid', 'ASC')
        ;

        if ($sessionId > 0) {
            $qb
                ->andWhere('IDENTITY(t.session) = :sessionId')
                ->setParameter('sessionId', $sessionId)
            ;
        } else {
            $qb->andWhere('t.session IS NULL');
        }

        /** @var CTool[] $tools */
        $tools = $qb->getQuery()->getResult();

        if (0 === \count($tools)) {
            return [
                'success' => false,
                'message' => 'No tools found for course / session.',
            ];
        }

        // Build an array of IDs to manipulate positions easily
        $ids = array_map(static fn (CTool $tool): int => $tool->getIid() ?? 0, $tools);

        $currentIndex = array_search($toolId, $ids, true);
        if (false === $currentIndex) {
            return [
                'success' => false,
                'message' => 'Tool not found in current course / session.',
            ];
        }

        // Clamp the new index into a valid range
        $newIndex = max(0, min($newIndex, \count($tools) - 1));

        if ($newIndex === $currentIndex) {
            return [
                'success' => true,
                'unchanged' => true,
                'from' => $currentIndex,
                'to' => $newIndex,
                'total' => \count($tools),
            ];
        }

        // Move the ID in the array (remove at old index, insert at new index)
        $idToMove = $ids[$currentIndex];
        array_splice($ids, $currentIndex, 1);
        array_splice($ids, $newIndex, 0, [$idToMove]);

        // Rewrite all positions in DB using a simple DQL UPDATE per tool
        // Positions will be 0-based: 0,1,2,...
        foreach ($ids as $pos => $id) {
            $em->createQueryBuilder()
                ->update(CTool::class, 't')
                ->set('t.position', ':pos')
                ->where('t.iid = :iid')
                ->setParameter('pos', $pos)
                ->setParameter('iid', $id)
                ->getQuery()
                ->execute()
            ;
        }

        return [
            'success' => true,
            'from' => $currentIndex,
            'to' => $newIndex,
            'total' => \count($tools),
            'courseId' => $course->getId(),
            'sessionId' => $sessionId,
        ];
    }
}
