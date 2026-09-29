<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseSettings\CourseSettingsConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<CourseSettingsConfiguration, CourseSettingsConfiguration>
 */
final readonly class CourseSettingsConfigurationProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseSettingsManager $manager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseSettingsConfiguration
    {
        if (!$data instanceof CourseSettingsConfiguration) {
            throw new BadRequestHttpException('Invalid course settings payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $this->manager->saveConfiguration($data->values);
        $freshData = $this->manager->getConfiguration();
        $data->courseId = (int) $freshData['courseId'];
        $data->sessionId = isset($freshData['sessionId']) ? (int) $freshData['sessionId'] : null;
        $data->resourceNodeId = (int) $freshData['resourceNodeId'];
        $data->values = (array) $freshData['values'];
        $data->sections = (array) $freshData['sections'];
        $data->permissions = (array) $freshData['permissions'];
        $data->media = (array) $freshData['media'];
        $data->integrations = (array) $freshData['integrations'];
        $data->success = true;
        $data->message = 'Update successful';

        return $data;
    }
}
