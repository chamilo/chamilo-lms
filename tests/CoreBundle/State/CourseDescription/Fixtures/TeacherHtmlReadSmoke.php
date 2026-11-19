<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * @psalm-suppress-file DuplicateClass
 * @psalm-suppress-file DuplicateFunction
 */

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
    $root = getcwd();
    $purifierPath = $root.'/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
    $securityPath = $root.'/public/main/inc/lib/security.lib.php';

    if (!is_file($purifierPath) || !is_file($securityPath)) {
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

    $assert = static function (bool $pass, string $message): void {
        if (!$pass) {
            fwrite(\STDERR, "FAIL: $message\n");

            exit(1);
        }
    };

    $scenario = $argv[1] ?? '';
    if (!in_array($scenario, ['policy', 'markup', 'reader'], true)) {
        fwrite(\STDERR, "ERROR: expected 'policy', 'markup' or 'reader' scenario.\n");

        exit(2);
    }

    $input = '<p onclick="alert(1)">Hello <strong>teacher</strong><script>alert(2)</script></p>';
    $safe = '<p>Safe <strong>formatting</strong></p>';

    // The editor form (item provider) loads the stored HTML with the teacher policy.
    $itemFilter = static fn (string $html): string => (string) Security::remove_XSS($html, COURSEMANAGERLOWSECURITY);

    // The list is what every course member reads, so it is filtered with the reader's profile.
    $listFilter = static fn (string $html, int $profile): string => (string) Security::remove_XSS($html, $profile);
    $readerProfiles = ['student' => STUDENT, 'teacher' => COURSEMANAGER, 'anonymous' => ANONYMOUS];

    if ('policy' === $scenario) {
        $GLOBALS['teacher_js_setting'] = 'true';
        $unrestricted = $itemFilter($input);
        $assert($unrestricted === $input, 'Editor: Yes must preserve the stored HTML for the teacher.');

        $GLOBALS['teacher_js_setting'] = 'false';
        $restricted = $itemFilter($input);
        $assert(!str_contains($restricted, '<script'), 'Editor: No must remove stored scripts.');
        $assert(!str_contains($restricted, 'onclick='), 'Editor: No must remove stored event handlers.');
        $assert(str_contains($restricted, '<strong>teacher</strong>'), 'Editor: No must retain safe formatting.');

        $GLOBALS['teacher_js_setting'] = 'true';
        $restored = $itemFilter($input);
        $assert($restored === $input, 'Editor: Switching back to Yes must preserve original markup.');
    } elseif ('reader' === $scenario) {
        // Even when teachers may store JavaScript, readers of the list never receive it.
        $GLOBALS['teacher_js_setting'] = 'true';
        foreach ($readerProfiles as $reader => $profile) {
            $result = $listFilter($input, $profile);
            $assert(!str_contains($result, '<script'), "List ($reader): stored scripts must be removed.");
            $assert(!str_contains($result, 'onclick='), "List ($reader): stored event handlers must be removed.");
            $assert(str_contains($result, '<strong>teacher</strong>'), "List ($reader): safe formatting must survive.");
        }
    } else {
        foreach (['true', 'false'] as $setting) {
            $GLOBALS['teacher_js_setting'] = $setting;
            $results = ['editor' => $itemFilter($safe)];
            foreach ($readerProfiles as $reader => $profile) {
                $results["list ($reader)"] = $listFilter($safe, $profile);
            }
            foreach ($results as $path => $result) {
                $assert(str_contains($result, 'Safe'), "$path: Safe text must survive ($setting).");
                $assert(str_contains($result, '<strong>formatting</strong>'), "$path: Harmless formatting must survive ($setting).");
            }
        }
    }

    echo "PASS: Course Description $scenario scenario validated both read paths.\n";
}
