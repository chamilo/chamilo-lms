<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathAiGenerator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<LearningPathAiGenerator, LearningPathAiGenerator> */
final readonly class LearningPathAiGeneratorProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private CLpRepository $lpRepository,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): LearningPathAiGenerator {
        if (!$data instanceof LearningPathAiGenerator) {
            throw new BadRequestHttpException('Invalid AI learning path payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        if (!$this->isFeatureEnabled($course)) {
            throw new AccessDeniedHttpException('AI learning path generation is disabled in this course.');
        }

        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $lpData = $this->normalizeGeneratedData($data->lpData);
        $learningPath = $this->prepareLearningPath(
            $lpData['topic'],
            $course,
            $session?->getId(),
            $group,
        );

        try {
            require_once api_get_path(SYS_CODE_PATH).'lp/LpAiHelper.php';

            $helper = new \LpAiHelper();
            $result = $helper->createLearningPathFromAI(
                $lpData,
                $course->getCode(),
                $this->aiDisclosureHelper->isDisclosureEnabled(),
                null,
                $learningPath,
            );
        } catch (Throwable $exception) {
            error_log('[AI][learnpath] Persistence failed: '.$exception->getMessage());

            throw new HttpException(500, 'An error occurred while creating the learning path.');
        }

        if (true !== ($result['success'] ?? false) || (int) ($result['lp_id'] ?? 0) <= 0) {
            throw new HttpException(
                500,
                (string) ($result['text'] ?? 'Error creating learning path'),
            );
        }

        $response = new LearningPathAiGenerator();
        $response->enabled = true;
        $response->language = $course->getCourseLanguage();
        $response->id = (int) $result['lp_id'];
        $response->title = $lpData['topic'];
        $response->csrfToken = $this->csrfTokenManager
            ->getToken(self::ACTION_TOKEN_INTENTION)
            ->getValue()
        ;

        return $response;
    }

    private function isFeatureEnabled(Course $course): bool
    {
        if (!$this->isTruthy($this->settingsManager->getSetting('ai_helpers.enable_ai_helpers'))) {
            return false;
        }

        $this->settingsCourseManager->setCourse($course);

        return $this->isTruthy(
            $this->settingsCourseManager->getCourseSettingValue('learning_path_generator')
        );
    }

    private function prepareLearningPath(
        string $title,
        Course $course,
        ?int $sessionId,
        ?CGroup $group,
    ): CLp {
        $courseNode = $course->getResourceNode();
        if (!$courseNode instanceof ResourceNode) {
            throw new BadRequestHttpException('Course resource node is missing.');
        }

        $resourceLink = [
            'cid' => (int) $course->getId(),
            'visibility' => 0,
        ];

        if (null !== $sessionId && $sessionId > 0) {
            $resourceLink['sid'] = $sessionId;
        }

        if ($group instanceof CGroup) {
            $resourceLink['gid'] = (int) $group->getIid();
        }

        $learningPath = new CLp();
        $learningPath->setLpType(CLp::LP_TYPE);
        $learningPath->setTitle($title);
        $learningPath->setDescription('');
        $learningPath->setParentResourceNode((int) $courseNode->getId());
        $learningPath->setResourceLinkArray([$resourceLink]);

        $this->lpRepository->createLp($learningPath);
        $this->entityManager->flush();

        return $learningPath;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     success: bool,
     *     topic: string,
     *     lp_items: array<int, array{title: string, content: string}>,
     *     quiz_items: array<int, array{title: string, content: string}>
     * }
     */
    private function normalizeGeneratedData(array $payload): array
    {
        if (isset($payload['success']) && false === (bool) $payload['success']) {
            throw new BadRequestHttpException('The AI response indicates a failed generation.');
        }

        $topic = trim(strip_tags((string) ($payload['topic'] ?? '')));
        if ('' === $topic) {
            throw new BadRequestHttpException('Topic not set in AI response.');
        }
        if (mb_strlen($topic) > 255) {
            throw new BadRequestHttpException('The learning path title is too long.');
        }

        $rawItems = $payload['lp_items'] ?? null;
        if (!\is_array($rawItems) || [] === $rawItems) {
            throw new BadRequestHttpException('Learning path items not set in AI response.');
        }

        $items = [];
        foreach ($rawItems as $rawItem) {
            if (!\is_array($rawItem)) {
                throw new BadRequestHttpException('Invalid learning path item in AI response.');
            }

            $title = trim(strip_tags((string) ($rawItem['title'] ?? '')));
            $content = (string) ($rawItem['content'] ?? '');

            if ('' === $title) {
                throw new BadRequestHttpException('A generated learning path item has no title.');
            }

            $items[] = [
                'title' => mb_substr($title, 0, 255),
                'content' => $content,
            ];
        }

        $quizItems = [];
        $rawQuizItems = $payload['quiz_items'] ?? [];
        if (!\is_array($rawQuizItems)) {
            throw new BadRequestHttpException('Invalid generated quiz data.');
        }

        foreach ($rawQuizItems as $rawQuizItem) {
            if (!\is_array($rawQuizItem)) {
                continue;
            }

            $quizItems[] = [
                'title' => mb_substr(
                    trim(strip_tags((string) ($rawQuizItem['title'] ?? ''))),
                    0,
                    255,
                ),
                'content' => trim((string) ($rawQuizItem['content'] ?? '')),
            ];
        }

        return [
            'success' => true,
            'topic' => $topic,
            'lp_items' => $items,
            'quiz_items' => $quizItems,
        ];
    }

    private function isTruthy(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
