<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<CStudentPublication, void>
 */
final class CStudentPublicationDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(
        $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): void {
        if (!$data instanceof CStudentPublication) {
            return;
        }

        foreach ($data->getChildren() as $child) {
            $this->entityManager->remove($child);
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
