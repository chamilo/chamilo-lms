<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Prevents direct API Platform course creation from bypassing the approval setting.
 *
 * @implements ProcessorInterface<Course, Course|void>
 */
final readonly class CourseCreationPolicyProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$this->security->isGranted('ROLE_ADMIN')
            && 'true' === $this->settingsManager->getSetting('course.course_validation', true)
        ) {
            throw new AccessDeniedHttpException('Course creation requires administrator approval. Submit a course request instead.');
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
