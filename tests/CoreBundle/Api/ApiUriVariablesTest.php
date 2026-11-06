<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * An operation reads its resource from the variables of its URL. When a resource names its
 * identifier differently from the variable of the URL ({threadId}, {sessionId}...), API Platform
 * falls back to an "id" variable the URL does not have, and the operation answers 404 "Invalid
 * identifier value or configuration." for every request, so the screen that calls it never opens.
 * Declaring the URL variable with a Link avoids it.
 */
final class ApiUriVariablesTest extends KernelTestCase
{
    public function testEveryOperationFindsItsVariablesInItsUrl(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $names = $container->get(ResourceNameCollectionFactoryInterface::class)->create();
        $metadataFactory = $container->get(ResourceMetadataCollectionFactoryInterface::class);

        $broken = [];
        foreach ($names as $class) {
            foreach ($metadataFactory->create($class) as $resource) {
                foreach ($resource->getOperations() ?? [] as $operation) {
                    $uriTemplate = (string) $operation->getUriTemplate();
                    // The variables are resolved before a read and before a write (PATCH, POST...)
                    $reads = false !== $operation->canRead();
                    $writes = false !== $operation->canWrite() && !\in_array($operation->getMethod(), ['GET', 'HEAD'], true);
                    if ('' === $uriTemplate || (!$reads && !$writes)) {
                        continue;
                    }

                    preg_match_all('/\{(\w+)\}/', $uriTemplate, $matches);
                    $missing = array_diff(array_keys($operation->getUriVariables() ?? []), $matches[1]);
                    if ([] !== $missing) {
                        $broken[] = $operation->getMethod().' '.$uriTemplate.' expects '.implode(', ', $missing);
                    }
                }
            }
        }

        $this->assertSame([], $broken);
    }
}
