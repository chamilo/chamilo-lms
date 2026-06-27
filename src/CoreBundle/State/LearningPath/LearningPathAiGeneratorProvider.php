<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathAiGenerator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<LearningPathAiGenerator> */
final readonly class LearningPathAiGeneratorProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private AiProviderFactory $aiProviderFactory,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): LearningPathAiGenerator {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $this->getContextSession($this->entityManager, $request, $course);
        $this->getContextGroup($this->entityManager, $request, $course);

        $result = new LearningPathAiGenerator();
        $result->enabled = $this->isFeatureEnabled($course);
        $result->language = $course->getCourseLanguage();
        $result->csrfToken = $this->csrfTokenManager
            ->getToken(self::ACTION_TOKEN_INTENTION)
            ->getValue()
        ;

        if ($result->enabled) {
            $result->providers = $this->getProviderOptions();
        }

        return $result;
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

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getProviderOptions(): array
    {
        $configured = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);
        if (\is_string($configured)) {
            $decoded = json_decode($configured, true);
            $configured = \is_array($decoded) ? $decoded : [];
        }
        if (!\is_array($configured)) {
            $configured = [];
        }

        $options = [];
        foreach ($this->aiProviderFactory->getProvidersForType('text') as $providerName) {
            $providerName = trim((string) $providerName);
            if ('' === $providerName) {
                continue;
            }

            $providerConfig = $configured[$providerName] ?? [];
            $model = '';
            if (\is_array($providerConfig)) {
                if (isset($providerConfig['text']) && \is_array($providerConfig['text'])) {
                    $model = trim((string) ($providerConfig['text']['model'] ?? ''));
                } else {
                    $model = trim((string) ($providerConfig['model'] ?? ''));
                }
            }

            $options[] = [
                'label' => '' !== $model ? $providerName.' ('.$model.')' : $providerName,
                'value' => $providerName,
            ];
        }

        return $options;
    }

    private function isTruthy(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
