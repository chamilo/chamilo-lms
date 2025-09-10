<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<CStudentPublication, void>
 */
final class CStudentPublicationDeleteProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof CStudentPublication) {
            return;
        }

        $node = $data->hasResourceNode() ? $data->getResourceNode() : null;

        $this->em->beginTransaction();
        try {
            try { $this->em->refresh($data); } catch (\Throwable) {}

            $this->em->remove($data);
            $this->em->flush();

            if ($node instanceof ResourceNode) {
                foreach ($node->getResourceLinks() as $link) {
                    $this->em->remove($link);
                }
                $this->em->flush();

                foreach ($node->getResourceFiles() as $file) {
                    $this->em->remove($file);
                }
                $this->em->flush();
            }

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
