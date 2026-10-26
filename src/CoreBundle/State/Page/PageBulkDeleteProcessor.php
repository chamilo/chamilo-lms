<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Page;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Page\PageBulkDeleteAction;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/** @implements ProcessorInterface<PageBulkDeleteAction, PageBulkDeleteAction> */
final readonly class PageBulkDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): PageBulkDeleteAction {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only platform administrators can delete CMS pages.');
        }
        if (!$data instanceof PageBulkDeleteAction) {
            throw new BadRequestHttpException('Invalid page deletion payload.');
        }

        $ids = $data->pageIds;
        if (0 === \count($ids) || \count($ids) > 100) {
            throw new BadRequestHttpException('Select between 1 and 100 pages.');
        }
        foreach ($ids as $id) {
            if (!\is_int($id) || $id <= 0) {
                throw new BadRequestHttpException('Every page identifier must be a positive integer.');
            }
        }
        if (\count(array_unique($ids)) !== \count($ids)) {
            throw new BadRequestHttpException('Duplicate page identifiers are not permitted.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl || null === $accessUrl->getId()) {
            throw new NotFoundHttpException('The current portal was not found.');
        }

        // Validate the entire selection before touching the database.
        $pages = [];
        foreach ($ids as $id) {
            $page = $this->entityManager->find(Page::class, $id);
            if (!$page instanceof Page || $page->getUrl()->getId() !== $accessUrl->getId()) {
                throw new NotFoundHttpException('A selected page does not belong to this portal.');
            }
            $pages[] = $page;
        }

        $this->entityManager->beginTransaction();

        try {
            foreach ($pages as $page) {
                $this->entityManager->remove($page);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $data->deletedCount = \count($pages);

        return $data;
    }
}
