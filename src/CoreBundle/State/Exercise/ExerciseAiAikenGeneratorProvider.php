<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseAiAikenGenerator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ExerciseAiAikenGenerator>
 */
final readonly class ExerciseAiAikenGeneratorProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseAiAikenGenerator
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to use the AI Aiken generator in this context.');
        }

        $course = $this->getCourse($request);
        $textProviders = $this->getTextProviderOptions();
        $documentProviders = $this->getDocumentProviderOptions();
        $courseGeneratorEnabled = $this->isCourseSettingEnabled($course, 'exercise_generator');
        $aiHelpersEnabled = $this->isSettingEnabled('ai_helpers.enable_ai_helpers');

        $response = new ExerciseAiAikenGenerator();
        $response->canManage = true;
        $response->courseExerciseGeneratorEnabled = $courseGeneratorEnabled;
        $response->aiHelpersEnabled = $aiHelpersEnabled;
        $response->enabled = $courseGeneratorEnabled && $aiHelpersEnabled && [] !== $textProviders;
        $response->language = $this->getCourseLanguage($course);
        $response->csrfToken = $this->csrfTokenManager->getToken(ExerciseQuestionImportProvider::CSRF_TOKEN_ID)->getValue();
        $response->textProviders = $this->toOptionList($textProviders);
        $response->documentProviders = $this->toOptionList($documentProviders);
        $response->questionTypes = [
            ['value' => 'multiple_choice', 'label' => 'Multiple answer'],
        ];
        $response->documents = [] !== $documentProviders ? $this->getDocumentItems($course) : [];
        $response->message = $this->getStatusMessage($courseGeneratorEnabled, $aiHelpersEnabled, $textProviders);

        return $response;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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

    private function isSettingEnabled(string $settingName): bool
    {
        return 'true' === $this->settingsManager->getSetting($settingName, true);
    }

    private function isCourseSettingEnabled(Course $course, string $settingName): bool
    {
        if (!\function_exists('api_get_course_setting')) {
            return false;
        }

        $value = api_get_course_setting($settingName, $course);

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function getCourseLanguage(Course $course): string
    {
        if (!\function_exists('api_get_course_info_by_id')) {
            return 'en';
        }

        $courseInfo = api_get_course_info_by_id((int) $course->getId());
        if (!\is_array($courseInfo)) {
            return 'en';
        }

        $language = trim((string) ($courseInfo['language'] ?? ''));

        return '' !== $language ? $language : 'en';
    }

    /**
     * @return array<string, mixed>
     */
    private function getAiProviderConfig(): array
    {
        $providers = json_decode((string) $this->settingsManager->getSetting('ai_helpers.ai_providers', true), true);

        return \is_array($providers) ? $providers : [];
    }

    /**
     * @return array<string, string>
     */
    private function getTextProviderOptions(): array
    {
        $providerOptions = [];

        foreach ($this->getAiProviderConfig() as $key => $config) {
            if (!\is_array($config)) {
                continue;
            }

            $supportsText = false;
            if ((!isset($config['document']) || !\is_array($config['document']))
                && (!isset($config['document_process']) || !\is_array($config['document_process']))
            ) {
                continue;
            }

            $model = '';
            if (isset($config['document']['model'])) {
                $model = (string) $config['document']['model'];
            } elseif (isset($config['document_process']['model'])) {
                $model = (string) $config['document_process']['model'];
            }

            if (isset($config['text']) && \is_array($config['text'])) {
                $supportsText = true;
                $model = (string) ($config['text']['model'] ?? '');
            } elseif (isset($config['model']) || isset($config['url'])) {
                $supportsText = true;
                $model = (string) ($config['model'] ?? '');
            }

            if (!$supportsText) {
                continue;
            }

            $label = (string) $key;
            if ('' !== trim($model)) {
                $label .= ' ('.$model.')';
            }

            $providerOptions[(string) $key] = $label;
        }

        return $providerOptions;
    }

    /**
     * @return array<string, string>
     */
    private function getDocumentProviderOptions(): array
    {
        $providerOptions = [];

        foreach ($this->getAiProviderConfig() as $key => $config) {
            if (!\is_array($config) || !isset($config['document_process']) || !\is_array($config['document_process'])) {
                continue;
            }

            $label = (string) $key;
            $model = (string) ($config['document_process']['model'] ?? '');
            if ('' !== trim($model)) {
                $label .= ' ('.$model.')';
            }

            $providerOptions[(string) $key] = $label;
        }

        return $providerOptions;
    }

    /**
     * @param array<string, string> $items
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function toOptionList(array $items): array
    {
        $options = [];
        foreach ($items as $value => $label) {
            $options[] = [
                'value' => (string) $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDocumentItems(Course $course): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceFiles', 'file')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->where('document.filetype = :fileType')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.deletedAt IS NULL')
            ->andWhere('resourceLink.endVisibilityAt IS NULL')
            ->setParameter('fileType', 'file', Types::STRING)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('document.iid', 'DESC')
        ;

        $documents = [];
        foreach ($queryBuilder->getQuery()->getResult() as $document) {
            if (!$document instanceof CDocument) {
                continue;
            }

            $node = $document->getResourceNode();
            if (null === $node || $node->getResourceFiles()->isEmpty()) {
                continue;
            }

            $file = $node->getResourceFiles()->first();
            if (!$file instanceof ResourceFile) {
                continue;
            }

            $filename = (string) $file->getOriginalName();
            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
            if (!\in_array($extension, ['pdf', 'txt'], true)) {
                continue;
            }

            $title = trim((string) $node->getTitle());
            if ('' === $title) {
                $title = $filename;
            }

            $documents[] = [
                'documentId' => (int) $document->getIid(),
                'resourceFileId' => (int) $file->getId(),
                'resourceNodeId' => (int) $node->getId(),
                'title' => $title,
                'filename' => $filename,
                'mimeType' => (string) $file->getMimeType(),
                'extension' => $extension,
                'size' => null,
            ];
        }

        return $documents;
    }

    /**
     * @param array<string, string> $textProviders
     */
    private function getStatusMessage(bool $courseGeneratorEnabled, bool $aiHelpersEnabled, array $textProviders): string
    {
        if (!$courseGeneratorEnabled) {
            return 'The AI Aiken generator is disabled in this course.';
        }

        if (!$aiHelpersEnabled) {
            return 'The AI helper tool is disabled on the platform.';
        }

        if ([] === $textProviders) {
            return 'No AI text providers configured.';
        }

        return '';
    }
}
