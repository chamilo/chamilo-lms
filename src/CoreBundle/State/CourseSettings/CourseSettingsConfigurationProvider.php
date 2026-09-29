<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseSettings\CourseSettingsConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseSettingsConfiguration>
 */
final readonly class CourseSettingsConfigurationProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseSettingsManager $manager,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseSettingsConfiguration
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data = $this->manager->getConfiguration();
        $resource = new CourseSettingsConfiguration();
        $resource->courseId = (int) $data['courseId'];
        $resource->sessionId = isset($data['sessionId']) ? (int) $data['sessionId'] : null;
        $resource->resourceNodeId = (int) $data['resourceNodeId'];
        $resource->values = (array) $data['values'];
        $resource->sections = (array) $data['sections'];
        $resource->permissions = (array) $data['permissions'];
        $resource->media = (array) $data['media'];
        $resource->integrations = (array) $data['integrations'];

        return $resource;
    }
}
