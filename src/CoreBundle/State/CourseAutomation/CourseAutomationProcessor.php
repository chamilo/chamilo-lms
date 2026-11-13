<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseAutomation;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseAutomation\CourseAutomationConfiguration;
use Chamilo\CoreBundle\Service\CourseAutomation\CourseAutomationManager;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProcessorInterface<CourseAutomationConfiguration, CourseAutomationConfiguration> */
final readonly class CourseAutomationProcessor implements ProcessorInterface
{
    public function __construct(private CourseAutomationManager $manager) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CourseAutomationConfiguration
    {
        $name = $operation->getName();

        if ('delete_course_automation_action' === $name) {
            $this->manager->deleteAction((int) ($uriVariables['actionId'] ?? 0));

            return null;
        }

        if (!$data instanceof CourseAutomationConfiguration) {
            throw new BadRequestHttpException('Invalid course automation payload.');
        }

        if ('post_course_automation_action' === $name) {
            return $this->manager->addAction($data);
        }

        if ('post_course_automation_execute' === $name) {
            return $this->manager->executeEvent($data->eventType);
        }

        throw new BadRequestHttpException('Unsupported course automation operation.');
    }
}
