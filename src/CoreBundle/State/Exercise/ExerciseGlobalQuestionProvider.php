<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseGlobalQuestion;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ExerciseGlobalQuestion>
 */
final readonly class ExerciseGlobalQuestionProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseGlobalQuestion
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to create exercise questions in this context.');
        }

        $response = new ExerciseGlobalQuestion();
        $response->title = 'Add a question';
        $response->questionTypes = $this->getQuestionTypes();
        $response->exercises = $this->getExercises($course, $session);
        $response->canManage = true;

        return $response;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
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
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExercises(Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('quiz.title', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $quiz) {
            if (!$quiz instanceof CQuiz || null === $quiz->getIid()) {
                continue;
            }

            $quizId = (int) $quiz->getIid();
            $items[$quizId] = [
                'id' => $quizId,
                'title' => $this->formatTitle((string) $quiz->getTitle()),
                'feedbackType' => (int) $quiz->getFeedbackType(),
            ];
        }

        return array_values($items);
    }

    private function formatTitle(string $title): string
    {
        $plainTitle = trim(html_entity_decode(strip_tags($title), ENT_QUOTES, 'UTF-8'));

        return '' !== $plainTitle ? mb_substr($plainTitle, 0, 80) : 'Untitled';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionTypes(): array
    {
        $types = [];
        foreach ($this->getLegacyQuestionTypeDefinitions() as $definition) {
            $type = (int) $definition['type'];
            if (!$this->isQuestionTypeVisibleInSelector($type)) {
                continue;
            }

            $types[] = [
                'type' => $type,
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'enabled' => true,
                'requiresImmediateFeedback' => false,
                'migratedToVue' => true,
                'editor' => 'vue',
            ];
        }

        return $types;
    }

    /**
     * @return array<int, array{type: int, label: string, icon: string}>
     */
    private function getLegacyQuestionTypeDefinitions(): array
    {
        return [
            ['type' => 1, 'label' => 'Multiple choice', 'icon' => 'mcua.png'],
            ['type' => 2, 'label' => 'Multiple answer', 'icon' => 'mcma.png'],
            ['type' => 3, 'label' => 'Fill blanks or form', 'icon' => 'fill_in_blanks.png'],
            ['type' => 4, 'label' => 'Matching', 'icon' => 'matching.png'],
            ['type' => 5, 'label' => 'Open question', 'icon' => 'open_answer.png'],
            ['type' => 6, 'label' => 'Image zones', 'icon' => 'hotspot.png'],
            ['type' => 9, 'label' => 'Exact Selection', 'icon' => 'mcmac.png'],
            ['type' => 10, 'label' => 'Unique answer with unknown', 'icon' => 'mcuao.png'],
            ['type' => 11, 'label' => "Multiple answer true/false/don't know", 'icon' => 'mcmao.png'],
            ['type' => 12, 'label' => "Combination true/false/don't-know", 'icon' => 'mcmaco.png'],
            ['type' => 13, 'label' => 'Oral expression', 'icon' => 'audio_question.png'],
            ['type' => 14, 'label' => 'Global multiple answer', 'icon' => 'mcmagl.png'],
            ['type' => 15, 'label' => 'Media question', 'icon' => 'media.png'],
            ['type' => 16, 'label' => 'Calculated answer', 'icon' => 'calculated_answer.png'],
            ['type' => 17, 'label' => 'Unique answer with images', 'icon' => 'uaimg.png'],
            ['type' => 18, 'label' => 'Sequence ordering', 'icon' => 'ordering.png'],
            ['type' => 19, 'label' => 'Match by dragging', 'icon' => 'matchingdrag.png'],
            ['type' => 20, 'label' => 'Annotation', 'icon' => 'annotation.png'],
            ['type' => 21, 'label' => 'Reading comprehension', 'icon' => 'reading_comprehension.png'],
            ['type' => 22, 'label' => 'Multiple answer true/false with degree of certainty', 'icon' => 'mccert.png'],
            ['type' => 23, 'label' => 'Upload Answer', 'icon' => 'file_upload_question.png'],
            ['type' => 24, 'label' => 'Matching combination', 'icon' => 'matching_co.png'],
            ['type' => 25, 'label' => 'Matching draggable combination', 'icon' => 'matchingdrag_co.png'],
            ['type' => 26, 'label' => 'Hotspot combination', 'icon' => 'hotspot_co.png'],
            ['type' => 27, 'label' => 'Fill in blanks combination', 'icon' => 'fill_in_blanks_co.png'],
            ['type' => 28, 'label' => 'Multiple Answer Dropdown Combination', 'icon' => 'mcma_dropdown_co.png'],
            ['type' => 29, 'label' => 'Multiple Answer Dropdown', 'icon' => 'mcma_dropdown.png'],
            ['type' => 31, 'label' => 'Page break', 'icon' => 'page_end.png'],
        ];
    }

    private function isQuestionTypeVisibleInSelector(int $type): bool
    {
        return 8 !== $type;
    }
}
