<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\ColorTheme\ColorThemeArchive;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const DIRECTORY_SEPARATOR;

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
            $accessUrl = $this->accessUrlHelper->getCurrent();

            // An update may only touch a theme already bound to the current portal.
            // PUT deserializes a new ColorTheme with no id, so $data is not the row
            // from the database and getColorThemeByTheme($data) never matches (#9591).
            // previous_data is the clone API Platform stored before deserialization.
            if ($operation instanceof Put || $operation instanceof Patch) {
                $storedTheme = $context['previous_data'] ?? null;
                $storedId = $storedTheme instanceof ColorTheme ? $storedTheme->getId() : null;
                $managedTheme = null !== $storedId
                    ? $this->entityManager->find(ColorTheme::class, $storedId)
                    : null;
                if (!$managedTheme instanceof ColorTheme || !$accessUrl->getColorThemeByTheme($managedTheme)) {
                    throw new NotFoundHttpException('Color theme not found.');
                }

                // Copy only the writable fields onto the stored row. The default PUT
                // processor reads every property of the new object, and createdAt
                // is still uninitialized there.
                if ($operation instanceof Put) {
                    $title = $data->getTitle();
                    if (null === $title || '' === trim($title)) {
                        throw new BadRequestHttpException('The title is required.');
                    }
                    $managedTheme
                        ->setTitle($title)
                        ->setVariables($data->getVariables())
                    ;
                    $data = $managedTheme;
                }
            }

            /** @var ColorTheme|null $colorTheme */
            $colorTheme = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            if (null === $colorTheme) {
                return null;
            }

            $accessUrlRelColorTheme = $accessUrl->getColorThemeByTheme($colorTheme);
            if (!$accessUrlRelColorTheme) {
                $accessUrlRelColorTheme = (new AccessUrlRelColorTheme())->setColorTheme($colorTheme);
                $accessUrl->addColorTheme($accessUrlRelColorTheme);
            }
            $this->entityManager->flush();

            try {
                $css = ColorThemeArchive::colorCss($colorTheme->getVariables());
            } catch (InvalidArgumentException $exception) {
                throw new BadRequestHttpException($exception->getMessage(), $exception);
            }

            try {
                $slug = $colorTheme->getSlug();
                if (!$this->filesystem->directoryExists($slug)) {
                    $this->filesystem->createDirectory($slug);
                }
                $this->filesystem->write(
                    $slug.DIRECTORY_SEPARATOR.'colors.css',
                    $css
                );
            } catch (FilesystemException $exception) {
                throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'The themes storage is not writable. Check its file permissions before saving.', $exception);
            }

            return $colorTheme;
        });
    }
}
