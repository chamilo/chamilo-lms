<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Page;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Page\PageLanguageDeleteAction;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use const FILTER_VALIDATE_INT;

/** @implements ProcessorInterface<PageLanguageDeleteAction, PageLanguageDeleteAction> */
final readonly class PageLanguageDeleteProcessor implements ProcessorInterface
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
    ): PageLanguageDeleteAction {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only platform administrators can delete CMS pages.');
        }

        if (!$data instanceof PageLanguageDeleteAction) {
            throw new BadRequestHttpException('Invalid page deletion payload.');
        }

        $pageId = filter_var($uriVariables['pageId'] ?? null, FILTER_VALIDATE_INT);
        if (false === $pageId || $pageId <= 0) {
            throw new BadRequestHttpException('A valid source page is required.');
        }

        $ids = $data->pageIds;
        if (\count($ids) < 2 || \count($ids) > 100) {
            throw new BadRequestHttpException('Select between 2 and 100 pages from different languages.');
        }
        foreach ($ids as $id) {
            if (!\is_int($id) || $id <= 0) {
                throw new BadRequestHttpException('Every page identifier must be a positive integer.');
            }
        }
        if (\count(array_unique($ids)) !== \count($ids) || !\in_array($pageId, $ids, true)) {
            throw new BadRequestHttpException('The selected pages must be unique and include the source page.');
        }

        $url = $this->accessUrlHelper->getCurrent();
        if (null === $url || null === $url->getId()) {
            throw new NotFoundHttpException('The current platform was not found.');
        }

        $source = $this->entityManager->find(Page::class, $pageId);
        if (!$source instanceof Page
            || $source->getUrl()->getId() !== $url->getId()
            || null === $source->getCategory()
        ) {
            throw new NotFoundHttpException('The source page was not found in this platform.');
        }

        $pages = [];
        $locales = [];
        $categoryId = $source->getCategory()->getId();
        foreach ($ids as $id) {
            $page = $this->entityManager->find(Page::class, $id);
            if (!$page instanceof Page
                || $page->getUrl()->getId() !== $url->getId()
                || $page->getCategory()?->getId() !== $categoryId
            ) {
                throw new NotFoundHttpException('A selected page does not belong to this platform and category.');
            }
            $pages[] = $page;
            $locales[$page->getLocale()] = true;
        }
        if (\count($locales) < 2) {
            throw new BadRequestHttpException('Select pages in at least two different languages.');
        }

        // Validate every selected item before mutating anything.
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

        $data->pageId = $pageId;
        $data->deletedCount = \count($pages);

        return $data;
    }
}
