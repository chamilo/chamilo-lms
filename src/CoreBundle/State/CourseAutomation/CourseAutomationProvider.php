<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseAutomation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseAutomation\CourseAutomationConfiguration;
use Chamilo\CoreBundle\Service\CourseAutomation\CourseAutomationManager;

/** @implements ProviderInterface<CourseAutomationConfiguration> */
final readonly class CourseAutomationProvider implements ProviderInterface
{
    public function __construct(private CourseAutomationManager $manager) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseAutomationConfiguration
    {
        return $this->manager->getConfiguration();
    }
}
