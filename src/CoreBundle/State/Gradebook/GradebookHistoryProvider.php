<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookHistory;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookLinkevalLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<GradebookHistory>
 */
final readonly class GradebookHistoryProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookHistory
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $kind = strtolower(trim((string) $request->query->get('kind', '')));
        if (!\in_array($kind, ['evaluation', 'link'], true)) {
            throw new BadRequestHttpException('The Gradebook history kind is invalid.');
        }

        $itemId = $request->query->getInt('itemId');
        if ($itemId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook item id is required.');
        }

        if ('evaluation' === $kind) {
            $item = $this->entityManager->getRepository(GradebookEvaluation::class)->find($itemId);
            if (!$item instanceof GradebookEvaluation) {
                throw new NotFoundHttpException('The requested evaluation was not found.');
            }
            if ((int) $item->getCourse()->getId() !== (int) $resolved['course']->getId()) {
                throw new NotFoundHttpException('The requested evaluation was not found.');
            }
            $category = $item->getCategory();
            $title = (string) $item->getTitle();
        } else {
            $item = $this->entityManager->getRepository(GradebookLink::class)->find($itemId);
            if (!$item instanceof GradebookLink) {
                throw new NotFoundHttpException('The requested Gradebook link was not found.');
            }
            if ((int) $item->getCourse()->getId() !== (int) $resolved['course']->getId()) {
                throw new NotFoundHttpException('The requested Gradebook link was not found.');
            }
            $category = $item->getCategory();
            $normalized = $this->linkResourceResolver->normalizeLink(
                $item,
                $resolved['course'],
                $resolved['session'],
                $resolved['groupId'],
                true,
            );
            $title = (string) ($normalized['title'] ?? ('Gradebook link #'.(int) $item->getId()));
        }

        $this->contextResolver->getCategoryInGradebook(
            (int) $category->getId(),
            $rootCategory,
            $resolved['course'],
            $resolved['session'],
        );

        $logs = $this->entityManager->getRepository(GradebookLinkevalLog::class)
            ->createQueryBuilder('log')
            ->andWhere('log.idLinkevalLog = :itemId')
            ->andWhere('LOWER(log.type) = :kind')
            ->setParameter('itemId', $itemId)
            ->setParameter('kind', $kind)
            ->orderBy('log.createdAt', 'DESC')
            ->addOrderBy('log.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $resource = new GradebookHistory();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->kind = $kind;
        $resource->itemId = $itemId;
        $resource->itemTitle = $title;

        foreach ($logs as $log) {
            if (!$log instanceof GradebookLinkevalLog) {
                continue;
            }
            $user = $log->getUser();
            $resource->rows[] = [
                'id' => (int) $log->getId(),
                'title' => (string) $log->getTitle(),
                'description' => (string) ($log->getDescription() ?? ''),
                'weight' => null !== $log->getWeight() ? (float) $log->getWeight() : null,
                'visible' => (bool) $log->getVisible(),
                'type' => (string) $log->getType(),
                'createdAt' => $log->getCreatedAt()?->format(DATE_ATOM),
                'user' => null !== $user ? [
                    'id' => (int) $user->getId(),
                    'fullName' => $user->getFullName(),
                    'username' => $user->getUsername(),
                ] : null,
            ];
        }

        return $resource;
    }
}
