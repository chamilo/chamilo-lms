<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Repository\ColorThemeRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

use const PHP_EOL;

class ColorThemeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ParameterBagInterface $parameterBag,
        private readonly ColorThemeRepository $colorThemeRepository,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert($data instanceof ColorTheme);

        $data->setActive(true);

        /** @var ColorTheme $colorTheme */
        $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($colorTheme) {
            $this->colorThemeRepository->deactivateAllExcept($colorTheme);

            $projectDir = $this->parameterBag->get('kernel.project_dir');

            $contentParts = [];
            $contentParts[] = ':root {';

            foreach ($colorTheme->getVariables() as $variable => $value) {
                $contentParts[] = "  $variable: $value;";
            }

            $contentParts[] = '}';

            $dirName = $projectDir."/var/theme/{$colorTheme->getSlug()}";

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
