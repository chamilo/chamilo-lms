<?php

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;

/**
 * @template-implements ProviderInterface<AccessUrlRelColorTheme>
 */
class AccessUrlRelColorThemeStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @inheritdoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $colorThemes = $this->accessUrlHelper->getCurrent()->getColorThemes();

        if (0 == $colorThemes->count()) {
            $colorThemes = $this->accessUrlHelper->getFirstAccessUrl()->getColorThemes();
        }

        return $colorThemes;
    }
}
