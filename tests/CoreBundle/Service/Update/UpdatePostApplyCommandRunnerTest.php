<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateMigrationPolicy;
use Chamilo\CoreBundle\Service\Update\UpdateOperationLogger;
use Chamilo\CoreBundle\Service\Update\UpdatePostApplyCommandRunner;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Translation\IdentityTranslator;

final class UpdatePostApplyCommandRunnerTest extends TestCase
{
    public function testBrowserRunnerOnlyAllowsCacheAndReviewedDatabaseMigrations(): void
    {
        $runner = $this->runner();
        $method = new ReflectionMethod(UpdatePostApplyCommandRunner::class, 'getActionDefinitions');
        $definitions = $method->invoke($runner);

        self::assertSame(['doctrine_migrations', 'cache_clear'], array_keys($definitions));
        self::assertFalse($definitions['cache_clear']['advanced']);
        self::assertTrue($definitions['doctrine_migrations']['advanced']);
    }

    public function testStaleComposerAndYarnRecommendationsAreNotExecutable(): void
    {
        $runner = $this->runner();
        $method = new ReflectionMethod(UpdatePostApplyCommandRunner::class, 'resolveRequestedActions');

        foreach (['composer_install', 'yarn_install', 'yarn_build'] as $key) {
            try {
                $method->invoke($runner, [$key], ['composer_install', 'frontend_build', 'cache_clear']);
                self::fail('Disallowed update command was accepted: '.$key);
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('not allowed', $exception->getMessage());
            }
        }
    }

    private function runner(): UpdatePostApplyCommandRunner
    {
        $path = sys_get_temp_dir().'/chamilo-update-runner-tests';

        return new UpdatePostApplyCommandRunner(
            new UpdateOperationLogger($path, new IdentityTranslator()),
            new UpdateConfiguration('test'),
            new UpdateMigrationPolicy(),
            $path,
            new IdentityTranslator(),
        );
    }
}
