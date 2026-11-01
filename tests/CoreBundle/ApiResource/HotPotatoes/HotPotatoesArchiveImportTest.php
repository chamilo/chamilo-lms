<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\ApiResource\HotPotatoes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\ApiResource\HotPotatoes\HotPotatoesArchiveImport;
use Chamilo\CoreBundle\State\HotPotatoes\HotPotatoesArchiveImportProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class HotPotatoesArchiveImportTest extends TestCase
{
    public function testImportOperationIsCourseScopedAndWriteProtected(): void
    {
        $attributes = (new ReflectionClass(HotPotatoesArchiveImport::class))->getAttributes(ApiResource::class);
        self::assertCount(1, $attributes);

        $resource = $attributes[0]->newInstance();
        $operations = $resource->getOperations();
        self::assertNotNull($operations);
        self::assertCount(1, $operations);

        $operation = array_values(iterator_to_array($operations))[0];
        self::assertInstanceOf(Post::class, $operation);
        self::assertSame('/hotpotatoes/archives/import', $operation->getUriTemplate());
        self::assertSame(HotPotatoesArchiveImportProcessor::class, $operation->getProcessor());
        self::assertStringContainsString('ROLE_CURRENT_COURSE_TEACHER', (string) $operation->getSecurity());
        self::assertStringContainsString('ROLE_CURRENT_COURSE_SESSION_TEACHER', (string) $operation->getSecurity());
        self::assertStringNotContainsString('ROLE_CURRENT_COURSE_STUDENT', (string) $operation->getSecurity());
        $parameters = $operation->getParameters();
        self::assertNotNull($parameters);
        $courseParameter = $parameters->get('cid');
        self::assertInstanceOf(QueryParameter::class, $courseParameter);
        self::assertTrue($courseParameter->getRequired());
        self::assertInstanceOf(QueryParameter::class, $parameters->get('sid'));
        self::assertInstanceOf(QueryParameter::class, $parameters->get('gid'));
    }
}
