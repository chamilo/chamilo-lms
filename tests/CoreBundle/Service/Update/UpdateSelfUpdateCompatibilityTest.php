<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Controller\Admin\SystemUpdateController;
use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\UpdateApplyPlanner;
use Chamilo\CoreBundle\Service\Update\UpdateArchiveInspector;
use Chamilo\CoreBundle\Service\Update\UpdateAvailabilityChecker;
use Chamilo\CoreBundle\Service\Update\UpdateFileApplier;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use Chamilo\CoreBundle\Service\Update\UpdateMigrationSafetyChecker;
use Chamilo\CoreBundle\Service\Update\UpdateOperationLogger;
use Chamilo\CoreBundle\Service\Update\UpdatePackageDownloader;
use Chamilo\CoreBundle\Service\Update\UpdatePackageRemovalManifest;
use Chamilo\CoreBundle\Service\Update\UpdatePostApplyChecker;
use Chamilo\CoreBundle\Service\Update\UpdatePostApplyCommandRunner;
use Chamilo\CoreBundle\Service\Update\UpdatePreflightChecker;
use Chamilo\CoreBundle\Service\Update\UpdateStagingManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateSelfUpdateCompatibilityTest extends TestCase
{
    /**
     * @dataProvider legacyConstructorSignatures
     *
     * @param string[] $legacyParameterNames
     */
    public function testUpdaterConstructorsRemainCompatibleWithPreviouslyCompiledContainer(
        string $className,
        array $legacyParameterNames
    ): void {
        $constructor = (new ReflectionClass($className))->getConstructor();

        self::assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        self::assertSame(
            [...$legacyParameterNames, 'translator'],
            array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters)
        );

        $parametersByName = [];
        foreach ($parameters as $parameter) {
            $parametersByName[$parameter->getName()] = $parameter;
        }

        self::assertArrayHasKey('translator', $parametersByName);

        $translatorParameter = $parametersByName['translator'];
        $translatorType = $translatorParameter->getType();

        if (!$translatorType instanceof ReflectionNamedType) {
            self::fail('The translator constructor argument must use a named type.');
        }

        self::assertSame(TranslatorInterface::class, $translatorType->getName());
        self::assertTrue($translatorType->allowsNull());
        self::assertTrue($translatorParameter->isDefaultValueAvailable());
        self::assertNull($translatorParameter->getDefaultValue());
    }

    /**
     * @return iterable<string, array{class-string, string[]}>
     */
    public static function legacyConstructorSignatures(): iterable
    {
        yield 'controller' => [SystemUpdateController::class, [
            'manifestClient',
            'packageDownloader',
            'packageVerifier',
            'preflightChecker',
            'stagingManager',
            'updateConfiguration',
            'migrationSafetyChecker',
            'applyPlanner',
            'availabilityChecker',
            'fileApplier',
            'postApplyChecker',
            'postApplyCommandRunner',
            'operationLogger',
            'installedVersionProvider',
            'trustedKeyring',
        ]];

        yield 'apply planner' => [UpdateApplyPlanner::class, ['migrationPolicy', 'packageRemovalManifest', 'projectDir']];

        yield 'archive inspector' => [UpdateArchiveInspector::class, []];

        yield 'availability checker' => [UpdateAvailabilityChecker::class, ['installedVersionProvider']];

        yield 'file applier' => [UpdateFileApplier::class, ['projectDir', 'operationLogger', 'updateConfiguration', 'packageRemovalManifest']];

        yield 'manifest client' => [UpdateManifestClient::class, ['httpClient', 'updateConfiguration']];

        yield 'migration safety checker' => [UpdateMigrationSafetyChecker::class, ['updateConfiguration', 'migrationPolicy', 'projectDir']];

        yield 'operation logger' => [UpdateOperationLogger::class, ['projectDir']];

        yield 'package downloader' => [UpdatePackageDownloader::class, ['httpClient', 'projectDir', 'updateConfiguration']];

        yield 'package removal manifest' => [UpdatePackageRemovalManifest::class, []];

        yield 'post apply checker' => [UpdatePostApplyChecker::class, ['updateConfiguration', 'migrationPolicy', 'projectDir']];

        yield 'post apply command runner' => [UpdatePostApplyCommandRunner::class, ['operationLogger', 'updateConfiguration', 'migrationPolicy', 'projectDir']];

        yield 'preflight checker' => [UpdatePreflightChecker::class, ['kernel', 'installedVersionProvider']];

        yield 'staging manager' => [UpdateStagingManager::class, ['archiveInspector', 'packageRemovalManifest', 'projectDir']];
    }

    public function testManifestFactoryStillAcceptsPreviousSingleArgumentCall(): void
    {
        $method = new ReflectionMethod(UpdateManifest::class, 'fromArray');
        $parameters = $method->getParameters();

        self::assertCount(2, $parameters);
        self::assertSame('translator', $parameters[1]->getName());
        self::assertTrue($parameters[1]->isDefaultValueAvailable());
        self::assertNull($parameters[1]->getDefaultValue());

        $manifest = UpdateManifest::fromArray([
            'channel' => 'stable',
            'version' => '3.0.1',
            'released_at' => '2026-09-06T00:00:00+00:00',
            'package' => [
                'url' => 'https://updates.chamilo.org/assets/chamilo-3.0.1.zip',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        self::assertSame('3.0.1', $manifest->getVersion());
    }
}
