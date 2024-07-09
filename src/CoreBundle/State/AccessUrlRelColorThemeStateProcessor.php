<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;

final class AccessUrlRelColorThemeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): AccessUrlRelColorTheme
    {
        \assert($data instanceof AccessUrlRelColorTheme);

        $accessUrl = $this->accessUrlHelper->getCurrent();
        $accessUrl->getActiveColorTheme()?->setActive(false);

        $accessUrlRelColorTheme = $accessUrl->getColorThemeByTheme($data->getColorTheme());

        if ($accessUrlRelColorTheme) {
            $accessUrlRelColorTheme->setActive(true);
        } else {
            $data->setActive(true);

            $accessUrl->addColorTheme($data);

            $accessUrlRelColorTheme = $data;
        }

        $this->entityManager->flush();

        return $accessUrlRelColorTheme;
    }
}
