<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Symfony\Component\Uid\Uuid;
use XApiPlugin;

/**
 * Class BaseStatement.
 */
abstract class BaseStatement
{
    /**
     * Build a plain xAPI statement payload.
     */
    abstract public function generate(): array;

    protected function generateStatementId(string $type): string
    {
        $type = trim($type);

        if ('' === $type) {
            return Uuid::v4()->toRfc4122();
        }

        $namespace = (string) XApiPlugin::create()->get(XApiPlugin::SETTING_UUID_NAMESPACE);

        if ('' !== trim($namespace)) {
            return Uuid::v5($namespace, $type.'-'.microtime(true).'-'.bin2hex(random_bytes(8)))->toRfc4122();
        }

        return Uuid::v4()->toRfc4122();
    }

    /**
     * Build a plain xAPI context payload.
     *
     * @param array<int, array<string, mixed>> $extraGroupingActivities
     */
    protected function generateContext(array $extraGroupingActivities = []): array
    {
        $platform = api_get_setting('Institution').' - '.api_get_setting('siteName');

        $groupingActivities = [];
        $siteActivity = $this->buildSiteGroupingActivity();

        if (!empty($siteActivity)) {
            $groupingActivities[] = $siteActivity;
        }

        $courseActivity = $this->buildCourseGroupingActivity();

        if (!empty($courseActivity)) {
            $groupingActivities[] = $courseActivity;
        }

        foreach ($extraGroupingActivities as $activity) {
            if (\is_array($activity) && !empty($activity['id'])) {
                $groupingActivities[] = $activity;
            }
        }

        $languageSource = function_exists('api_get_interface_language')
            ? api_get_interface_language()
            : api_get_setting('platformLanguage');

        $languageIso = !empty($languageSource)
            ? api_get_language_isocode($languageSource)
            : 'en';

        $context = [
            'platform' => trim((string) $platform),
            'language' => $languageIso,
        ];

        if (!empty($groupingActivities)) {
            $context['contextActivities'] = [
                'grouping' => array_values($groupingActivities),
            ];
        }

        return $context;
    }

    protected function mergeGroupingActivity(array $context, array $activity): array
    {
        if (empty($activity['id'])) {
            return $context;
        }

        if (!isset($context['contextActivities']) || !\is_array($context['contextActivities'])) {
            $context['contextActivities'] = [];
        }

        if (
            !isset($context['contextActivities']['grouping'])
            || !\is_array($context['contextActivities']['grouping'])
        ) {
            $context['contextActivities']['grouping'] = [];
        }

        $context['contextActivities']['grouping'][] = $activity;

        return $context;
    }

    protected function buildScore(
        ?float $scaled = null,
        ?float $raw = null,
        ?float $min = null,
        ?float $max = null
    ): array {
        $score = [];

        if (null !== $scaled) {
            $score['scaled'] = $scaled;
        }

        if (null !== $raw) {
            $score['raw'] = $raw;
        }

        if (null !== $min) {
            $score['min'] = $min;
        }

        if (null !== $max) {
            $score['max'] = $max;
        }

        return $score;
    }

    protected function buildResult(
        array $score = [],
        ?bool $success = null,
        ?bool $completion = null,
        ?string $response = null,
        ?string $duration = null
    ): array {
        $result = [];

        if (!empty($score)) {
            $result['score'] = $score;
        }

        if (null !== $success) {
            $result['success'] = $success;
        }

        if (null !== $completion) {
            $result['completion'] = $completion;
        }

        if (null !== $response && '' !== trim($response)) {
            $result['response'] = trim($response);
        }

        if (null !== $duration && '' !== trim($duration)) {
            $result['duration'] = trim($duration);
        }

        return $result;
    }

    private function buildSiteGroupingActivity(): array
    {
        $platformLanguage = api_get_setting('platformLanguage');
        $platformLanguageIso = !empty($platformLanguage)
            ? api_get_language_isocode($platformLanguage)
            : 'en';

        $platform = trim(api_get_setting('Institution').' - '.api_get_setting('siteName'));

        if ('' === $platform) {
            return [];
        }

        return [
            'objectType' => 'Activity',
            'id' => 'http://id.tincanapi.com/activitytype/lms',
            'definition' => [
                'name' => [
                    $platformLanguageIso => $platform,
                ],
            ],
        ];
    }

    private function buildCourseGroupingActivity(): array
    {
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        if (!$course) {
            return [];
        }

        $courseLanguage = method_exists($course, 'getCourseLanguage')
            ? $course->getCourseLanguage()
            : null;

        $languageIso = !empty($courseLanguage)
            ? api_get_language_isocode($courseLanguage)
            : 'en';

        $courseUrl = api_get_course_url(
            $course->getCode(),
            $session ? $session->getId() : 0
        );

        return [
            'objectType' => 'Activity',
            'id' => $courseUrl,
            'definition' => [
                'name' => [
                    $languageIso => (string) $course->getTitle(),
                ],
                'type' => 'http://id.tincanapi.com/activitytype/lms/course',
            ],
        ];
    }
}
