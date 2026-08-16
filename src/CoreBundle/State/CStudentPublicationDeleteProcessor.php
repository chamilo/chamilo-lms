<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<CStudentPublication, void>
 */
final class CStudentPublicationDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GradebookLinkManager $gradebookLinkManager,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof CStudentPublication) {
            return;
        }

        $node = $data->hasResourceNode() ? $data->getResourceNode() : null;
        $resourceLink = $data->getFirstResourceLink();
        $course = $resourceLink->getCourse();
        $publicationId = (int) ($data->getIid() ?? 0);

        $this->em->beginTransaction();

        try {
            try {
                $this->em->refresh($data);
            } catch (Throwable) {
            }

            if ($publicationId > 0) {
                $this->gradebookLinkManager->removeAllCourseLinks(
                    $course,
                    GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION,
                    $publicationId,
                );
            }

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
        } catch (Throwable $e) {
            $this->em->rollback();

            throw $e;
        }
    }
}
