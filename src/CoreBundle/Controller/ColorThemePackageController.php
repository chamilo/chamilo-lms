<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Service\ColorTheme\ColorThemeArchive;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Throwable;

use const JSON_THROW_ON_ERROR;

#[Route('/api/color-theme-packages', name: 'color_theme_package_')]
#[IsGranted('ROLE_ADMIN')]
final class ColorThemePackageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        #[Autowire(param: 'theme_fallback')]
        private readonly string $themeFallback,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private readonly FilesystemOperator $filesystem,
    ) {}

    #[Route('/{id}/export', name: 'export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(int $id): BinaryFileResponse
    {
        $theme = $this->getTheme($id);
        $slug = $theme->getSlug();
        if (null === $slug || !preg_match('/^[a-z0-9][a-z0-9_-]{0,254}$/D', $slug)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'The selected theme has an invalid slug.');
        }

        $assets = [];
        foreach (ColorThemeArchive::assetPaths() as $path) {
            $file = $slug.'/'.$path;
            if ($this->filesystem->fileExists($file)) {
                if ($this->filesystem->fileSize($file) > 2 * 1024 * 1024) {
                    throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Theme asset is too large to export.');
                }
                $assets[$path] = $this->filesystem->read($file);
            }
        }

        $temporary = tempnam(sys_get_temp_dir(), 'chamilo_color_theme_');
        if (false === $temporary) {
            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'Cannot create temporary theme archive.');
        }

        try {
            ColorThemeArchive::write($temporary, (string) $theme->getTitle(), $theme->getVariables(), $assets);
        } catch (InvalidArgumentException $exception) {
            unlink($temporary);

            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $exception);
        } catch (Throwable $exception) {
            unlink($temporary);

            throw $exception;
        }

        $response = new BinaryFileResponse($temporary);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $slug.'.zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/import', name: 'import', methods: ['POST'], priority: 10)]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('archive');
        $name = $request->request->get('name');
        if (!$file instanceof UploadedFile || !$file->isValid() || 'zip' !== strtolower($file->getClientOriginalExtension())
            || !\is_string($name)) {
            throw new BadRequestHttpException('Provide a ZIP file and a new theme name.');
        }

        try {
            $package = ColorThemeArchive::read($file->getPathname());
            $name = ColorThemeArchive::validateTitle($name);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $slug = strtolower((new AsciiSlugger())->slug($name)->toString());
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/D', $slug)) {
            throw new BadRequestHttpException('Invalid theme name for a directory.');
        }
        if (null !== $this->entityManager->getRepository(ColorTheme::class)->findOneBy(['slug' => $slug])
            || $this->filesystem->directoryExists($slug)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'A theme with this name or directory already exists.');
        }

        $theme = (new ColorTheme())
            ->setTitle($name)
            ->setSlug($slug)
            ->setVariables($package['variables'])
        ;

        $createdDirectory = false;

        try {
            $this->entityManager->wrapInTransaction(function () use ($theme, $slug, $package, &$createdDirectory): void {
                $url = $this->accessUrlHelper->getCurrent();
                $relation = (new AccessUrlRelColorTheme())
                    ->setColorTheme($theme)
                    ->setActive(false)
                ;
                $url->addColorTheme($relation);

                $this->entityManager->persist($theme);
                $this->entityManager->persist($relation);
                $this->entityManager->flush();

                if ($theme->getSlug() !== $slug) {
                    throw new RuntimeException('The imported theme slug unexpectedly changed.');
                }
                $createdDirectory = true;
                $this->filesystem->createDirectory($slug);
                $this->filesystem->write($slug.'/colors.css', ColorThemeArchive::colorCss($package['variables']));
                foreach ($package['assets'] as $path => $content) {
                    $directory = \dirname($slug.'/'.$path);
                    if (!$this->filesystem->directoryExists($directory)) {
                        $this->filesystem->createDirectory($directory);
                    }
                    $this->filesystem->write($slug.'/'.$path, $content);
                }
            });
        } catch (Throwable $exception) {
            if ($createdDirectory) {
                try {
                    $this->filesystem->deleteDirectory($slug);
                } catch (Throwable) {
                    // Preserve the original exception; orphaned assets can be cleaned manually.
                }
            }

            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'Cannot save imported theme.', $exception);
        }

        return $this->json([
            'id' => $theme->getId(),
            'iri' => '/api/color_themes/'.$theme->getId(),
            'title' => $theme->getTitle(),
            'slug' => $slug,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/rename', name: 'rename', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function rename(int $id, Request $request): JsonResponse
    {
        $theme = $this->getTheme($id);

        try {
            $data = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
            if (!\is_array($data) || !\is_string($data['name'] ?? null)) {
                throw new InvalidArgumentException('A valid theme name is required.');
            }
            $name = ColorThemeArchive::validateTitle($data['name']);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException('A valid theme name is required.', $exception);
        }
        $theme->setTitle($name);
        $this->entityManager->flush();

        return $this->json(['id' => $theme->getId(), 'title' => $theme->getTitle(), 'slug' => $theme->getSlug()]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $theme = $this->getTheme($id);
        $slug = $theme->getSlug();
        if (null === $slug || !preg_match('/^[a-z0-9][a-z0-9_-]{0,254}$/D', $slug)
            || \in_array($slug, ['chamilo', 'chamilo3', $this->themeFallback], true)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'Built-in or invalid themes cannot be deleted.');
        }
        $currentUrlId = $this->accessUrlHelper->getCurrent()->getId();
        foreach ($theme->getUrls() as $relation) {
            if ($relation->isActive()) {
                throw new HttpException(Response::HTTP_CONFLICT, 'The theme is active on an access URL.');
            }
            // Do not silently remove another portal's available theme selection.
            if ($relation->getUrl()?->getId() !== $currentUrlId) {
                throw new HttpException(Response::HTTP_CONFLICT, 'The theme is associated with another access URL.');
            }
        }

        if ($this->hasReference(CCourseSetting::class, 'setting', 'setting.variable = :variable AND setting.value = :slug', $slug)
            || $this->hasReference(User::class, 'user', 'user.theme = :slug', $slug)
            || $this->hasReference(CLp::class, 'lp', 'lp.theme = :slug', $slug)) {
            throw new HttpException(Response::HTTP_CONFLICT, 'The theme is still referenced by a user, course or learning path.');
        }

        // Remove only inactive selections belonging to the current access URL.
        $this->entityManager->wrapInTransaction(function () use ($theme, $slug): void {
            foreach ($theme->getUrls()->toArray() as $relation) {
                $this->entityManager->remove($relation);
            }
            $this->entityManager->remove($theme);
            $this->entityManager->flush();
            if ($this->filesystem->directoryExists($slug)) {
                $this->filesystem->deleteDirectory($slug);
            }
        });

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function getTheme(int $id): ColorTheme
    {
        $theme = $this->entityManager->getRepository(ColorTheme::class)->find($id);
        if (!$theme instanceof ColorTheme) {
            throw new NotFoundHttpException('Theme not found.');
        }

        return $theme;
    }

    private function hasReference(string $entityClass, string $alias, string $condition, string $slug): bool
    {
        $idProperty = \in_array($entityClass, [CCourseSetting::class, CLp::class], true) ? 'iid' : 'id';
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT('.$alias.'.'.$idProperty.')')
            ->from($entityClass, $alias)
            ->where($condition)
            ->setParameter('slug', $slug)
        ;
        if (CCourseSetting::class === $entityClass) {
            $qb->setParameter('variable', 'course_theme');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
