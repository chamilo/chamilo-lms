<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<CStudentPublication, void>
 */
final class CStudentPublicationDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TrackEDefaultRepository $trackRepo,
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

        if ($data->hasResourceNode()) {
            $this->trackRepo->registerResourceEvent(
                $data->getResourceNode(),
                'deletion'
            );
        }

        foreach ($data->getChildren() as $child) {
            $this->entityManager->remove($child);
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
