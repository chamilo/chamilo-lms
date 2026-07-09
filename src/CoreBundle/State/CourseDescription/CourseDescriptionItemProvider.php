<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseDescriptionItem>
 */
final readonly class CourseDescriptionItemProvider implements ProviderInterface
{
    public const CSRF_TOKEN_ID = 'course_description_item';

    /**
     * @var array<int, string>
     */
    private const TYPE_LABELS = [
        CCourseDescription::TYPE_DESCRIPTION => 'Description',
        CCourseDescription::TYPE_OBJECTIVES => 'Objectives',
        CCourseDescription::TYPE_TOPICS => 'Topics',
        CCourseDescription::TYPE_METHODOLOGY => 'Methodology',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Course material',
        CCourseDescription::TYPE_RESOURCES => 'Resources',
        CCourseDescription::TYPE_ASSESSMENT => 'Assessment',
        CCourseDescription::TYPE_CUSTOM => 'Other',
    ];

    /**
     * @var array<int, string>
     */
    private const TYPE_ICONS = [
        CCourseDescription::TYPE_DESCRIPTION => 'image-text',
        CCourseDescription::TYPE_OBJECTIVES => 'flag-checkered',
        CCourseDescription::TYPE_TOPICS => 'table-of-contents',
        CCourseDescription::TYPE_METHODOLOGY => 'strategy',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'laptop',
        CCourseDescription::TYPE_RESOURCES => 'human-male-board',
        CCourseDescription::TYPE_ASSESSMENT => 'order-bool-ascending-variant',
        CCourseDescription::TYPE_CUSTOM => 'magic-staff',
    ];

    /**
     * @var array<int, string>
     */
    private const HELP_TEXTS = [
        CCourseDescription::TYPE_DESCRIPTION => 'What is the goal of the course? Are there prerequisites? How is this training connected to other courses?',
        CCourseDescription::TYPE_OBJECTIVES => 'What should the end results be when the learner has completed the course? What are the activities performed during the course?',
        CCourseDescription::TYPE_TOPICS => 'How does the course progress? Where should the learner pay special care? Are there identifiable problems in understanding different areas? How much time should one dedicate to the different areas of the course?',
        CCourseDescription::TYPE_METHODOLOGY => 'What methods and activities help achieve the objectives of the course?  What would the schedule be?',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Is there a course book, a collection of papers, a bibliography, a list of links on the internet?',
        CCourseDescription::TYPE_RESOURCES => 'Consider the courses, coaches, a technical helpdesk, teachers, and/or materials available.',
        CCourseDescription::TYPE_ASSESSMENT => 'How will learners be assessed? Are there strategies to develop in order to master the topic?',
    ];

    /**
     * @var array<int, string>
     */
    private const INFORMATION_TEXTS = [
        CCourseDescription::TYPE_DESCRIPTION => 'Describe the course (number of hours, serial number, location) and teacher (name, office, Tel., e-mail, office hours . . . .).',
        CCourseDescription::TYPE_OBJECTIVES => 'What are the objectives of the course (competences, skills, outcomes)?',
        CCourseDescription::TYPE_TOPICS => 'List of topics included in the training. Importance of each topic. Level of difficulty. Structure and inter-dependence of the different parts.',
        CCourseDescription::TYPE_METHODOLOGY => 'Presentation of the activities (conference, papers, group research, labs...).',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Short description of the course materials.',
        CCourseDescription::TYPE_RESOURCES => 'Describe the course (number of hours, serial number, location) and teacher (name, office, Tel., e-mail, office hours . . . .).',
        CCourseDescription::TYPE_ASSESSMENT => 'Criteria for skills acquisition.',
    ];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseDescriptionItem
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        if ($this->isStudentView($request) || !$this->canManage($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course descriptions in this context.');
        }
        $descriptionId = isset($uriVariables['iid'])
            ? (int) $uriVariables['iid']
            : $request->query->getInt('id');
        $descriptionType = $request->query->getInt('descriptionType');

        $description = null;
        if ($descriptionId > 0) {
            $description = $this->getDescriptionFromOwnContext($descriptionId, $course, $session);
            $descriptionType = (int) $description->getDescriptionType();
        } elseif ($descriptionType > 0 && CCourseDescription::TYPE_CUSTOM !== $descriptionType) {
            $description = $this->findOwnDescriptionByType($descriptionType, $course, $session);
        }

        if (!\in_array($descriptionType, CCourseDescription::getTypes(), true)) {
            $descriptionType = CCourseDescription::TYPE_DESCRIPTION;
        }

        $item = new CourseDescriptionItem();
        $item->descriptionType = $descriptionType;
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $item->canEdit = true;
        $item->isNew = !$description instanceof CCourseDescription;
        $item->defaultTitle = self::TYPE_LABELS[$descriptionType] ?? self::TYPE_LABELS[CCourseDescription::TYPE_CUSTOM];
        $item->help = self::HELP_TEXTS[$descriptionType] ?? '';
        $item->information = self::INFORMATION_TEXTS[$descriptionType] ?? '';
        $item->types = $this->getTypes();
        $item->languages = $this->getLanguages();
        $item->settings = $this->getSettings();
        $item->enableSearch = $item->settings['searchEnabled'];

        if ($description instanceof CCourseDescription) {
            $item->iid = $description->getIid();
            $item->title = (string) $description->getTitle();
            $item->content = (string) $description->getContent();
            $item->progress = (int) $description->getProgress();
            $item->language = $this->getResourceLanguage($description);
        }

        return $item;
    }

    private function canManage(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (null === $session) {
            return $course->hasUserAsTeacher($user);
        }

        return $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getDescriptionFromOwnContext(int $descriptionId, Course $course, ?Session $session): CCourseDescription
    {
        $description = $this->courseDescriptionRepository->find($descriptionId);
        if (!$description instanceof CCourseDescription) {
            throw new NotFoundHttpException('The requested course description was not found.');
        }

        if (!$this->belongsToExactContext($description, $course, $session)) {
            throw new AccessDeniedHttpException('The requested course description does not belong to the current course context.');
        }

        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this course description.');
        }

        return $description;
    }

    private function findOwnDescriptionByType(int $descriptionType, Course $course, ?Session $session): ?CCourseDescription
    {
        $descriptions = $this->courseDescriptionRepository->findByTypeInCourse($descriptionType, $course, $session);
        foreach ($descriptions as $description) {
            if (!$description instanceof CCourseDescription) {
                continue;
            }

            if ($this->belongsToExactContext($description, $course, $session)) {
                return $description;
            }
        }

        return null;
    }

    private function belongsToExactContext(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTypes(): array
    {
        $types = [];
        foreach (self::TYPE_LABELS as $value => $label) {
            $types[] = [
                'value' => $value,
                'label' => $label,
                'icon' => self::TYPE_ICONS[$value] ?? 'information',
            ];
        }

        return $types;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getLanguages(): array
    {
        $languages = [
            [
                'value' => '',
                'label' => 'No specific language',
            ],
        ];

        $availableLanguages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($availableLanguages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $label = $language->getOriginalName() ?: $language->getEnglishName();
            $languages[] = [
                'value' => $language->getIsocode(),
                'label' => $label ?: $language->getIsocode(),
            ];
        }

        return $languages;
    }

    /**
     * @return array<string, bool>
     */
    private function getSettings(): array
    {
        return [
            'searchEnabled' => $this->isSettingEnabled('search.search_enabled'),
            'saveTitlesAsHtml' => $this->isSettingEnabled('editor.save_titles_as_html'),
        ];
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function isStudentView(Request $request): bool
    {
        if ($request->query->has('isStudentView')) {
            return $request->query->getBoolean('isStudentView');
        }

        if (!$request->hasSession()) {
            return false;
        }

        return 'studentview' === $request->getSession()->get('studentview');
    }

    private function getResourceLanguage(CCourseDescription $description): string
    {
        $language = $description->getResourceNode()?->getLanguage();

        return null !== $language ? (string) $language->getIsocode() : '';
    }
}
