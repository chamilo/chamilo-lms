<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotRuntimeContext;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\SupportBotTreeRepository;
use Chamilo\CoreBundle\Service\SupportBot\SupportBotEngine;

/**
 * @implements ProviderInterface<SupportBotRuntimeContext>
 */
final readonly class SupportBotRuntimeContextProvider implements ProviderInterface
{
    public function __construct(
        private SupportBotEngine $engine,
        private AccessUrlHelper $accessUrlHelper,
        private SupportBotTreeRepository $treeRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupportBotRuntimeContext
    {
        $result = new SupportBotRuntimeContext();
        if (!$this->engine->isAvailable()) {
            return $result;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            return $result;
        }

        $tree = $this->treeRepository->findPublishedForAccessUrl($accessUrl);
        if (!$tree instanceof SupportBotTree) {
            return $result;
        }

        $result->enabled = true;
        $result->title = $tree->getTitle();

        return $result;
    }
}
