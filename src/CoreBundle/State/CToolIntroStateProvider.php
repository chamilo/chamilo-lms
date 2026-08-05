<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Helpers\CourseLinkSessionHelper;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @template-implements ProviderInterface<CToolIntro>
 */
final readonly class CToolIntroStateProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
        private CourseLinkSessionHelper $courseLinkSessionHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $result = $this->collectionProvider->provide($operation, $uriVariables, $context);

            /** @var CToolIntro $intro */
            foreach ($result as $intro) {
                $this->rewriteIntroText($intro);
            }

            return $result;
        }

        $intro = $this->itemProvider->provide($operation, $uriVariables, $context);

        if ($intro instanceof CToolIntro) {
            $this->rewriteIntroText($intro);
        }

        return $intro;
    }

    private function rewriteIntroText(CToolIntro $intro): void
    {
        $courseId = (int) $intro->getCourseTool()->getCourse()->getId();

        $intro->setIntroText(
            $this->courseLinkSessionHelper->rewriteSessionForCourse($intro->getIntroText(), $courseId)
        );
    }
}
