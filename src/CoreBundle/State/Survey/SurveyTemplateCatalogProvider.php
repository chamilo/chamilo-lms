<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateCatalog;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<SurveyTemplateCatalog>
 */
final readonly class SurveyTemplateCatalogProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateCatalog
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('template')
            ->from(CSurveyTemplate::class, 'template')
            ->andWhere('template.accessUrl = :accessUrl')
            ->setParameter('accessUrl', $accessUrl)
            ->orderBy('template.title', 'ASC')
        ;

        if (!$isAdmin) {
            $queryBuilder->andWhere('template.visible = :visible')->setParameter('visible', true);
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $template) {
            if (!$template instanceof CSurveyTemplate) {
                continue;
            }

            $item = [
                'id' => (int) $template->getId(),
                'title' => $template->getTitle(),
                'subtitle' => (string) $template->getSubtitle(),
            ];
            if ($isAdmin) {
                $item['visible'] = $template->isVisible();
                $item['questionCount'] = $template->getQuestions()->count();
                $item['updatedAt'] = $template->getUpdatedAt()->format(DATE_ATOM);
            }
            $items[] = $item;
        }

        $response = new SurveyTemplateCatalog();
        $response->templates = $items;
        $response->isAdmin = $isAdmin;

        return $response;
    }
}
