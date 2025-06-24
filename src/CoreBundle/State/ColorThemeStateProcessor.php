<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @implements ProcessorInterface<ColorTheme, ColorTheme|void>
 */
final readonly class ColorThemeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private AccessUrlHelper $accessUrlHelper,
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private FilesystemOperator $filesystem,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?ColorTheme
    {
        \assert($data instanceof ColorTheme);

        /** @var ColorTheme $colorTheme */
        $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($colorTheme) {
            $accessUrl = $this->accessUrlHelper->getCurrent();

            $accessUrlRelColorTheme = $accessUrl->getColorThemeByTheme($colorTheme);

            if (!$accessUrlRelColorTheme) {
                $accessUrlRelColorTheme = (new AccessUrlRelColorTheme())->setColorTheme($colorTheme);

                $this->accessUrlHelper->getCurrent()->addColorTheme($accessUrlRelColorTheme);
            }

            $this->entityManager->flush();

            $contentParts = [];
            $contentParts[] = ':root {';

            foreach ($colorTheme->getVariables() as $variable => $value) {
                $contentParts[] = "  $variable: $value;";
            }

            $contentParts[] = '}';

            $this->filesystem->createDirectory($colorTheme->getSlug());
            $this->filesystem->write(
                $colorTheme->getSlug().DIRECTORY_SEPARATOR.'colors.css',
                implode(PHP_EOL, $contentParts)
            );
        }

        return $colorTheme;
    }
}
