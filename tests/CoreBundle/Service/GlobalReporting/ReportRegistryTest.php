<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\GlobalReporting;

use PHPUnit\Framework\TestCase;

/**
 * Safeguard the report inventory without booting legacy global.inc.php.
 *
 * Standalone Vue reports must have canonical registry URLs, and IDs must
 * remain unique so links and permission metadata cannot become ambiguous.
 */
final class ReportRegistryTest extends TestCase
{
    public function testRegistryIdentifiersAreUnique(): void
    {
        $entries = $this->registrySource();
        preg_match_all("/'id'\\s*=>\\s*'([^']+)'/", $entries, $matches);

        $ids = $matches[1];

        self::assertNotEmpty($ids);
        self::assertSame($ids, array_values(array_unique($ids)), 'Every registered report must have a distinct, stable ID.');
    }

    public function testEachStandaloneVueReportingRouteIsRegistered(): void
    {
        $entries = $this->registrySource();
        preg_match_all("/'(?:url|entry_url)'\\s*=>\\s*'([^']+)'/", $entries, $matches);

        $registeredPaths = array_unique(array_map(
            static fn (string $url): string => rtrim(explode('?', $url, 2)[0], '/'),
            $matches[1],
        ));

        foreach ([
            'globalReporting.js' => '/reporting',
            'courseReporting.js' => '/resources/course-reporting',
        ] as $routerFile => $basePath) {
            $router = $this->readFile('assets/vue/router/'.$routerFile);
            preg_match_all('/^[ ]{6}path:\s*"([^"]*)"/m', $router, $paths);

            self::assertNotEmpty($paths[1], 'Cannot extract reporting routes from '.$routerFile);

            foreach ($paths[1] as $relativePath) {
                // Detail views require user/course IDs and are not standalone reports.
                if (str_contains($relativePath, ':')) {
                    continue;
                }

                $path = rtrim($basePath.'/'.ltrim($relativePath, '/'), '/');
                self::assertContains(
                    $path,
                    $registeredPaths,
                    'The standalone Vue report '.$path.' is missing from ReportRegistry.',
                );
            }
        }
    }

    private function registrySource(): string
    {
        $source = $this->readFile('public/main/inc/lib/reports.lib.php');
        $found = preg_match('/public static function getReports\(\): array(.*?)public static function findReport/s', $source, $matches);

        self::assertSame(1, $found, 'The reports registry could not be located.');

        return $matches[1];
    }

    private function readFile(string $path): string
    {
        $filename = \dirname(__DIR__, 4).'/'.$path;
        $contents = file_get_contents($filename);

        self::assertNotFalse($contents, 'Cannot read '.$filename);

        return $contents;
    }
}
