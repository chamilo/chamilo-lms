<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class McpCourseAiFeatureManager
{
    private const string CATEGORY = 'ai_helpers';

    /**
     * Titles verified against the current AI settings fixtures.
     *
     * @var array<string, string>
     */
    private const array SUPPORTED_FEATURES = [
        'learning_path_generator' => 'Learning paths generator',
        'exercise_generator' => 'Exercise generator',
        'image_generator' => 'Image generator',
        'course_analyser' => 'Course analyser',
    ];

    public function __construct(
        private SettingsManager $settingsManager,
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private CCourseSettingRepository $courseSettingRepository,
        private EntityManagerInterface $entityManager,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return list<string> features changed from disabled/missing to enabled
     */
    public function ensureEnabled(
        Course $course,
        User $user,
        string $feature,
        string $operation,
    ): array {
        return $this->ensureAllEnabled(
            $course,
            $user,
            [$feature],
            $operation,
        );
    }

    /**
     * Checks every platform-level permission before changing the course.
     *
     * @param list<string> $features
     *
     * @return list<string> features changed from disabled/missing to enabled
     */
    public function ensureAllEnabled(
        Course $course,
        User $user,
        array $features,
        string $operation,
    ): array {
        $courseId = (int) $course->getId();
        $userId = (int) $user->getId();

        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('The authenticated user ID is invalid.');
        }

        $features = array_values(array_unique($features));
        if ([] === $features) {
            return [];
        }

        /*
         * Complete preflight first. This is important for compound operations
         * such as a learning path, which needs both page and test generation.
         * No course setting is changed if any required global feature is off.
         */
        $this->assertGlobalAiHelpersEnabled();

        foreach ($features as $feature) {
            $this->assertSupportedFeature($feature);
            $this->assertFeatureAllowedGlobally($feature, $courseId);
        }

        $enabledFeatures = [];

        foreach ($features as $feature) {
            if ($this->upsertEnabledCourseSetting($courseId, $feature)) {
                $enabledFeatures[] = $feature;
            }
        }

        $this->entityManager->flush();

        if ([] !== $enabledFeatures) {
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$courseId.':mcp_ai_features',
                userId: $userId,
                meta: [
                    'feature' => 'mcp_course_ai_feature_enablement',
                    'operation' => $operation,
                    'requested_features' => $features,
                    'enabled_features' => $enabledFeatures,
                ],
                courseId: $courseId,
                sessionId: 0,
            );
        }

        return $enabledFeatures;
    }

    private function assertGlobalAiHelpersEnabled(): void
    {
        $value = $this->settingsManager->getSetting(
            'ai_helpers.enable_ai_helpers',
            true,
        );

        if (!$this->isTruthy($value)) {
            throw new RuntimeException('AI helpers are disabled by the platform administrator.');
        }
    }

    private function assertSupportedFeature(string $feature): void
    {
        if (!isset(self::SUPPORTED_FEATURES[$feature])) {
            throw new InvalidArgumentException(\sprintf('Unsupported MCP AI course feature "%s".', $feature));
        }
    }

    private function assertFeatureAllowedGlobally(
        string $feature,
        int $courseId,
    ): void {
        $globalValue = $this->settingsManager->getSetting(
            'ai_helpers.'.$feature,
            true,
        );

        if (
            !$this->isTruthy($globalValue)
            || !$this->aiFeatureAccessHelper->isFeatureConfigurableForCourse(
                $feature,
                $courseId,
            )
        ) {
            throw new RuntimeException(\sprintf('The AI feature "%s" is disabled by the platform administrator.', $feature));
        }
    }

    /**
     * Returns true only when the effective setting changed from disabled or
     * missing to enabled.
     */
    private function upsertEnabledCourseSetting(
        int $courseId,
        string $feature,
    ): bool {
        /** @var CCourseSetting[] $allRows */
        $allRows = $this->courseSettingRepository->findBy(
            [
                'cId' => $courseId,
                'variable' => $feature,
            ],
            ['iid' => 'ASC'],
        );

        $rows = array_values(array_filter(
            $allRows,
            static fn (CCourseSetting $setting): bool => \in_array(
                (string) $setting->getCategory(),
                [self::CATEGORY, ''],
                true,
            ),
        ));

        if ([] === $rows) {
            $setting = (new CCourseSetting())
                ->setCId($courseId)
                ->setVariable($feature)
                ->setTitle(self::SUPPORTED_FEATURES[$feature])
                ->setCategory(self::CATEGORY)
                ->setValue('true')
            ;

            $this->entityManager->persist($setting);

            return true;
        }

        $canonical = null;

        foreach ($rows as $row) {
            if (self::CATEGORY === (string) $row->getCategory()) {
                $canonical = $row;

                break;
            }
        }

        if (!$canonical instanceof CCourseSetting) {
            $canonical = $rows[0];
        }

        $wasEnabled = $this->isTruthy($canonical->getValue());

        $canonical
            ->setCategory(self::CATEGORY)
            ->setTitle(self::SUPPORTED_FEATURES[$feature])
            ->setValue('true')
        ;

        $this->entityManager->persist($canonical);

        /*
         * Remove semantic duplicates, matching the normalization strategy
         * already used by SettingsManager course-setting propagation.
         */
        foreach ($rows as $row) {
            if ($row === $canonical) {
                continue;
            }

            $this->entityManager->remove($row);
        }

        return !$wasEnabled;
    }

    private function isTruthy(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(
            strtolower(trim($value)),
            ['1', 'true', 'yes', 'on'],
            true,
        );
    }
}
