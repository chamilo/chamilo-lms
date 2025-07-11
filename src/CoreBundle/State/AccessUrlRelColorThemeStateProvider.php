<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\Common\Collections\Collection;

/**
 * @template-implements ProviderInterface<AccessUrlRelColorTheme>
 */
readonly class AccessUrlRelColorThemeStateProvider implements ProviderInterface
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Collection
    {
        $colorThemes = $this->accessUrlHelper->getCurrent()->getColorThemes();

        if (0 == $colorThemes->count()) {
            $colorThemes = $this->accessUrlHelper->getFirstAccessUrl()->getColorThemes();
        }

        return $colorThemes;
    }
}
