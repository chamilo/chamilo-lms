<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Public marketing cards are display-only. Never pass them to checkout or sales APIs.
 */
const BUYCOURSES_PUBLIC_PLANS_SETTING = 'public_plan_cards_json';
const BUYCOURSES_PUBLIC_PLAN_LANGUAGES = ['en_US', 'fr_FR', 'es'];

function buycoursesPublicPlanDefaults(): array
{
    return [
        'show_free' => true,
        'free' => [
            'title' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
            'description' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
            'features' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
        ],
        'cards' => [
            [
                'enabled' => false,
                'position' => 10,
                'title' => ['en_US' => 'Chamilo Pro', 'fr_FR' => 'Chamilo Pro', 'es' => 'Chamilo Pro'],
                'description' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'features' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'button' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'url' => '',
                'image_url' => '',
            ],
            [
                'enabled' => false,
                'position' => 20,
                'title' => ['en_US' => 'Chamilo.io', 'fr_FR' => 'Chamilo.io', 'es' => 'Chamilo.io'],
                'description' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'features' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'button' => ['en_US' => '', 'fr_FR' => '', 'es' => ''],
                'url' => '',
                'image_url' => '',
            ],
        ],
    ];
}

function buycoursesPublicPlanLocalizedInput($input, int $maxLength): array
{
    $input = is_array($input) ? $input : [];
    $result = [];
    foreach (BUYCOURSES_PUBLIC_PLAN_LANGUAGES as $language) {
        $value = str_replace("\r\n", "\n", trim((string) ($input[$language] ?? '')));
        $result[$language] = function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    return $result;
}

/**
 * Normalize settings from storage or POST; never trust arbitrary configuration keys.
 */
function buycoursesNormalizePublicPlanSettings(array $input): array
{
    $defaults = buycoursesPublicPlanDefaults();
    $freeInput = is_array($input['free'] ?? null) ? $input['free'] : [];
    $output = [
        'show_free' => !empty($input['show_free']),
        'free' => [
            'title' => buycoursesPublicPlanLocalizedInput($freeInput['title'] ?? [], 120),
            'description' => buycoursesPublicPlanLocalizedInput($freeInput['description'] ?? [], 2000),
            'features' => buycoursesPublicPlanLocalizedInput($freeInput['features'] ?? [], 2500),
        ],
        'cards' => [],
    ];

    $rows = is_array($input['cards'] ?? null) ? array_values($input['cards']) : [];
    foreach (array_slice($rows, 0, 8) as $index => $row) {
        if (!is_array($row) || !empty($row['remove'])) {
            continue;
        }
        $url = trim((string) ($row['url'] ?? ''));
        $imageUrl = trim((string) ($row['image_url'] ?? ''));
        // Public-facing links must use HTTPS. Empty links are allowed for drafts.
        $url = buycoursesIsHttpsPublicPlanUrl($url) ? $url : '';
        $imageUrl = buycoursesIsHttpsPublicPlanUrl($imageUrl) ? $imageUrl : '';

        $output['cards'][] = [
            'enabled' => !empty($row['enabled']) && '' !== $url,
            'position' => max(0, min(999, (int) ($row['position'] ?? (($index + 1) * 10)))),
            'title' => buycoursesPublicPlanLocalizedInput($row['title'] ?? [], 120),
            'description' => buycoursesPublicPlanLocalizedInput($row['description'] ?? [], 2000),
            'features' => buycoursesPublicPlanLocalizedInput($row['features'] ?? [], 2500),
            'button' => buycoursesPublicPlanLocalizedInput($row['button'] ?? [], 100),
            'url' => $url,
            'image_url' => $imageUrl,
        ];
    }

    usort($output['cards'], static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

    return $output;
}

function buycoursesIsHttpsPublicPlanUrl(string $url): bool
{
    if ('' === $url || strlen($url) > 2048 || preg_match('/[\x00-\x1f\x7f]/', $url)) {
        return false;
    }

    $parts = parse_url($url);

    return is_array($parts)
        && 'https' === strtolower((string) ($parts['scheme'] ?? ''))
        && !empty($parts['host'])
        && !isset($parts['user'])
        && !isset($parts['pass']);
}

function buycoursesGetPublicPlanSettings(BuyCoursesPlugin $plugin): array
{
    $raw = $plugin->get(BUYCOURSES_PUBLIC_PLANS_SETTING);
    if (!is_string($raw) || '' === $raw) {
        return buycoursesPublicPlanDefaults();
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded)
        ? buycoursesNormalizePublicPlanSettings($decoded)
        : buycoursesPublicPlanDefaults();
}

/**
 * Store per-access-URL settings inside the plugin's existing configuration.
 */
function buycoursesSavePublicPlanSettings(BuyCoursesPlugin $plugin, array $settings): void
{
    $pluginEntity = Container::getPluginRepository()->findOneByTitle($plugin->get_name());
    if (null === $pluginEntity) {
        throw new RuntimeException('BuyCourses plugin configuration was not found.');
    }

    $configEntity = $pluginEntity->getOrCreatePluginConfiguration(Container::getAccessUrlUtil()->getCurrent());
    $current = $configEntity->getConfiguration() ?? [];
    $current[BUYCOURSES_PUBLIC_PLANS_SETTING] = json_encode(
        buycoursesNormalizePublicPlanSettings($settings),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $configEntity->setConfiguration($current);
    $em = Database::getManager();
    $em->persist($configEntity);
    $em->flush();
}

function buycoursesPublicPlanLanguage(): string
{
    $iso = str_replace('-', '_', (string) api_get_language_isocode());
    if (in_array($iso, BUYCOURSES_PUBLIC_PLAN_LANGUAGES, true)) {
        return $iso;
    }

    $rootLanguage = strtolower((string) strtok($iso, '_'));
    if ('fr' === $rootLanguage) {
        return 'fr_FR';
    }
    if ('es' === $rootLanguage) {
        return 'es';
    }

    return 'en_US';
}

function buycoursesPublicPlanText(array $translations, string $language, string $fallback): string
{
    foreach ([$language, 'en_US', 'fr_FR', 'es'] as $code) {
        $value = trim((string) ($translations[$code] ?? ''));
        if ('' !== $value) {
            return $value;
        }
    }

    return $fallback;
}

function buycoursesBuildPublicPlanCards(BuyCoursesPlugin $plugin): array
{
    $settings = buycoursesGetPublicPlanSettings($plugin);
    $language = buycoursesPublicPlanLanguage();
    $free = null;
    if ($settings['show_free']) {
        $isAnonymous = api_is_anonymous();
        $isAdministrator = api_is_platform_admin();
        $isTeacher = !$isAnonymous && api_is_teacher();
        $teacherCreationEnabled = 'true' === (string) api_get_setting('workflows.allow_users_to_create_courses');
        $hasCreationPermission = $isAdministrator || ($isTeacher && $teacherCreationEnabled);
        $canCreateStandardCourse = $hasCreationPermission;
        $freeActionNotice = '';

        if (!$isAnonymous && !$hasCreationPermission) {
            $freeActionNotice = $plugin->get_lang('PublicFreePlanTeachersOnly');
        } elseif ($hasCreationPermission && !$isAdministrator) {
            try {
                $standard = $plugin->getStandardCourseCreationOptionForUser((int) api_get_user_id());
                $canCreateStandardCourse = !empty($standard['available']);
                if (!$canCreateStandardCourse) {
                    $freeActionNotice = $plugin->get_lang('PublicFreePlanLimitReached');
                }
            } catch (Throwable $exception) {
                error_log('[BuyCourses][PublicPlans] Failed to check standard course availability: '.$exception->getMessage());
                $canCreateStandardCourse = false;
                $freeActionNotice = $plugin->get_lang('PublicFreePlanUnavailable');
            }
        }

        $free = [
            'action_enabled' => $isAnonymous || $canCreateStandardCourse,
            'action_notice' => $freeActionNotice,
            'title' => buycoursesPublicPlanText($settings['free']['title'], $language, $plugin->get_lang('PublicFreePlanTitle')),
            'description' => buycoursesPublicPlanText($settings['free']['description'], $language, $plugin->get_lang('PublicFreePlanDescription')),
            'features' => buycoursesPublicPlanText($settings['free']['features'], $language, ''),
            'max_courses' => max(0, (int) api_get_setting('platform.max_courses_per_user')),
            'hosting_limit' => max(0, (int) api_get_setting('platform.hosting_limit_users_per_course')),
            'document_quota_mb' => max(0, (int) api_get_setting('document.default_document_quotum')),
            'url' => api_is_anonymous()
                ? api_get_path(WEB_CODE_PATH).'auth/registration.php'
                : api_get_path(WEB_PATH).'resources/courses/new',
            'button' => $plugin->get_lang(api_is_anonymous() ? 'PublicFreePlanRegister' : 'PublicFreePlanCreateCourse'),
        ];
    }

    $cards = [];
    foreach ($settings['cards'] as $row) {
        if (empty($row['enabled']) || '' === $row['url']) {
            continue;
        }
        $title = buycoursesPublicPlanText($row['title'], $language, '');
        if ('' === $title) {
            continue;
        }
        $cards[] = [
            'title' => $title,
            'description' => buycoursesPublicPlanText($row['description'], $language, ''),
            'features' => buycoursesPublicPlanText($row['features'], $language, ''),
            'button' => buycoursesPublicPlanText($row['button'], $language, $plugin->get_lang('PublicExternalPlanButton')),
            'url' => $row['url'],
            'image_url' => $row['image_url'],
        ];
    }

    return ['free' => $free, 'promos' => $cards];
}
