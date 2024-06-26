<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Repository\ColorThemeRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

use const PHP_EOL;

final class ColorThemeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ParameterBagInterface $parameterBag,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert($data instanceof ColorTheme);

        /** @var ColorTheme $colorTheme */
        $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($colorTheme) {
            $accessUrlRelColorTheme = (new AccessUrlRelColorTheme())->setColorTheme($colorTheme);

            $this->accessUrlHelper->getCurrent()->addColorTheme($accessUrlRelColorTheme);

            $this->entityManager->flush();

            $projectDir = $this->parameterBag->get('kernel.project_dir');

            $contentParts = [];
            $contentParts[] = ':root {';

            foreach ($colorTheme->getVariables() as $variable => $value) {
                $contentParts[] = "  $variable: $value;";
            }

            $contentParts[] = '}';

            $dirName = $projectDir."/var/themes/{$colorTheme->getSlug()}";

            $fs = new Filesystem();
            $fs->mkdir($dirName);
            $fs->dumpFile(
                $dirName.'/colors.css',
                implode(PHP_EOL, $contentParts)
            );
        }

        return $colorTheme;
    }
}
