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
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // Keep the database update uncommitted until theme assets are written successfully.
        return $this->entityManager->wrapInTransaction(function () use ($data, $operation, $uriVariables, $context): ?ColorTheme {
            /** @var ColorTheme|null $colorTheme */
            $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            if (null === $colorTheme) {
                return null;
            }

            $accessUrl = $this->accessUrlHelper->getCurrent();
            $accessUrlRelColorTheme = $accessUrl->getColorThemeByTheme($colorTheme);
            if (!$accessUrlRelColorTheme) {
                $accessUrlRelColorTheme = (new AccessUrlRelColorTheme())->setColorTheme($colorTheme);
                $accessUrl->addColorTheme($accessUrlRelColorTheme);
            }
            $this->entityManager->flush();

            $contentParts = [':root {'];
            foreach ($colorTheme->getVariables() as $variable => $value) {
                $contentParts[] = "  $variable: $value;";
            }
            $contentParts[] = '}';

            try {
                $slug = $colorTheme->getSlug();
                if (!$this->filesystem->directoryExists($slug)) {
                    $this->filesystem->createDirectory($slug);
                }
                $this->filesystem->write(
                    $slug.DIRECTORY_SEPARATOR.'colors.css',
                    implode(PHP_EOL, $contentParts)
                );
            } catch (FilesystemException $exception) {
                throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'The themes storage is not writable. Check its file permissions before saving.', $exception);
            }

            return $colorTheme;
        });
    }
}
