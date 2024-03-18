<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

use const PHP_EOL;

class ColorThemeProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert($data instanceof ColorTheme);

        $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($colorTheme) {
            $projectDir = $this->parameterBag->get('kernel.project_dir');

            $contentParts = [];
            $contentParts[] = ':root {';

            foreach ($data->getVariables() as $variable => $value) {
                $contentParts[] = "  $variable: $value;";
            }

            $contentParts[] = '}';

            $fs = new Filesystem();
            $fs->mkdir($projectDir.'/var/theme');
            $fs->dumpFile(
                $projectDir.'/var/theme/colors.css',
                implode(PHP_EOL, $contentParts)
            );
        }

        return $colorTheme;
    }
}
