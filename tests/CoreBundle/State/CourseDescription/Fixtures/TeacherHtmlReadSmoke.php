<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace ApiPlatform\Metadata {
    class Operation {}
}

namespace ApiPlatform\State {
    use ApiPlatform\Metadata\Operation;

    interface ProviderInterface
    {
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): mixed;
    }
}

namespace Chamilo\CoreBundle\ApiResource\CourseDescription {
    class CourseDescriptionList {}
    class CourseDescriptionItem {}
}

namespace Chamilo\CourseBundle\Entity {
    class CCourseDescription
    {
        public const TYPE_DESCRIPTION = 1;
        public const TYPE_OBJECTIVES = 2;
        public const TYPE_TOPICS = 3;
        public const TYPE_METHODOLOGY = 4;
        public const TYPE_COURSE_MATERIAL = 5;
        public const TYPE_RESOURCES = 6;
        public const TYPE_ASSESSMENT = 7;
        public const TYPE_CUSTOM = 8;
    }
}

namespace {
    use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionItemProvider;
    use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionListProvider;

    $root = getcwd();
    $purifierPath = $root.'/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
    $securityPath = $root.'/public/main/inc/lib/security.lib.php';
    $providerPath = $root.'/src/CoreBundle/State/CourseDescription/CourseDescriptionListProvider.php';
    $itemProviderPath = $root.'/src/CoreBundle/State/CourseDescription/CourseDescriptionItemProvider.php';

    if (!is_file($purifierPath) || !is_file($securityPath) || !is_file($providerPath) || !is_file($itemProviderPath)) {
        fwrite(\STDERR, "ERROR: run this script from the Chamilo checkout with installed dependencies.\n");

        exit(2);
    }

    require_once $purifierPath;

    define('COURSEMANAGERLOWSECURITY', 10);
    define('COURSEMANAGER', 1);
    define('STUDENT', 5);
    define('ANONYMOUS', 6);
    define('SYS_ARCHIVE_PATH', 'archive');

    $GLOBALS['teacher_js_setting'] = 'true';
    $GLOBALS['allowed_html_teacher'] = 'p,strong,b,em,span,div,br';
    $GLOBALS['allowed_html_student'] = 'p,strong,b,em,span,div,br';
    $GLOBALS['allowed_html_anonymous'] = 'p,strong,b,em,span,div,br';

    function api_get_setting(string $name, bool $isArray = false, mixed $key = null): mixed
    {
        return match ($name) {
            'security.teacher_use_js' => $GLOBALS['teacher_js_setting'],
            'enable_iframe_inclusion' => 'false',
            default => '',
        };
    }

    function api_is_anonymous(): bool
    {
        return false;
    }
    function api_is_allowed_to_edit(): bool
    {
        return true;
    }
    function api_get_path(mixed $path): string
    {
        return sys_get_temp_dir().'/chamilo-9436-read-';
    }
    function api_get_permissions_for_new_directories(): int
    {
        return 0755;
    }
    function api_get_system_encoding(): string
    {
        return 'UTF-8';
    }

    require_once $securityPath;

    require_once $providerPath;

    require_once $itemProviderPath;

    $assert = static function (bool $pass, string $message): void {
        if (!$pass) {
            fwrite(\STDERR, "FAIL: $message\n");

            exit(1);
        }
    };

    $scenario = $argv[1] ?? '';
    if (!in_array($scenario, ['policy', 'markup'], true)) {
        fwrite(\STDERR, "ERROR: expected 'policy' or 'markup' scenario.\n");

        exit(2);
    }

    $input = '<p onclick="alert(1)">Hello <strong>teacher</strong><script>alert(2)</script></p>';

    foreach ([CourseDescriptionListProvider::class, CourseDescriptionItemProvider::class] as $providerType) {
        $providerClass = new ReflectionClass($providerType);
        $provider = $providerClass->newInstanceWithoutConstructor();
        $filter = $providerClass->getMethod('sanitizeHtmlForDisplay');

        if ('policy' === $scenario) {
            $GLOBALS['teacher_js_setting'] = 'true';
            $unrestricted = $filter->invoke($provider, $input);
            $assert($unrestricted === $input, "$providerType: Yes must preserve existing HTML read behavior.");

            $GLOBALS['teacher_js_setting'] = 'false';
            $restricted = $filter->invoke($provider, $input);
            $assert(!str_contains($restricted, '<script'), "$providerType: No must remove stored scripts.");
            $assert(!str_contains($restricted, 'onclick='), "$providerType: No must remove stored event handlers.");
            $assert(str_contains($restricted, '<strong>teacher</strong>'), "$providerType: No must retain safe formatting.");
            $assert($input !== $restricted, "$providerType: Read filtering must not modify its source.");

            $GLOBALS['teacher_js_setting'] = 'true';
            $restored = $filter->invoke($provider, $input);
            $assert($restored === $input, "$providerType: Switching back to Yes must preserve original markup.");
        } else {
            $safe = '<p>Safe <strong>formatting</strong></p>';
            foreach (['true', 'false'] as $setting) {
                $GLOBALS['teacher_js_setting'] = $setting;
                $result = $filter->invoke($provider, $safe);
                $assert(str_contains($result, 'Safe'), "$providerType: Safe text must survive ($setting).");
                $assert(str_contains($result, '<strong>formatting</strong>'), "$providerType: Harmless formatting must survive ($setting).");
            }
        }
    }

    echo "PASS: Course Description $scenario scenario validated both read paths.\n";
}
