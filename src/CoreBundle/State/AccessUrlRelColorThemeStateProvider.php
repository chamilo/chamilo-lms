<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Utils\AccessUrlUtil;
use Doctrine\Common\Collections\Collection;

/**
 * @template-implements ProviderInterface<AccessUrlRelColorTheme>
 */
readonly class AccessUrlRelColorThemeStateProvider implements ProviderInterface
{
    public function __construct(
        private AccessUrlUtil $accessUrlUtil,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Collection
    {
        $colorThemes = $this->accessUrlUtil->getCurrent()->getColorThemes();

        if (0 == $colorThemes->count()) {
            $colorThemes = $this->accessUrlUtil->getFirstAccessUrl()->getColorThemes();
        }

        return $colorThemes;
    }
}
