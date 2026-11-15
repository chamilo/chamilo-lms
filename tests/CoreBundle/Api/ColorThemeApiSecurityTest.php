<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\ColorTheme;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\ColorTheme\ColorThemeArchive;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Regression test for the CSS-injection fix on /api/color_themes/{id}.
 *
 * Before the fix, ColorThemeStateProcessor interpolated the client-supplied
 * theme variables straight into colors.css without validation, so an admin
 * could close the :root block and append arbitrary CSS rules to a stylesheet
 * that every portal page loads. The processor also never checked that the
 * theme belonged to the current portal, so an admin of portal B could rewrite
 * the stylesheet of a theme used by portal A.
 *
 * Each write must now validate the variables (rejecting with 400), store only
 * the colour variables, and refuse a theme bound to another portal with 404.
 */
class ColorThemeApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Slugs whose theme directory a successful write created and must be removed.
     *
     * @var list<string>
     */
    private array $writtenSlugs = [];

    protected function tearDown(): void
    {
        $filesystem = $this->themesFilesystem();
        foreach ($this->writtenSlugs as $slug) {
            try {
                if ($filesystem->directoryExists($slug)) {
                    $filesystem->deleteDirectory($slug);
                }
            } catch (Throwable) {
                // Best-effort cleanup; the themes filesystem is not transactional.
            }
        }
        $this->writtenSlugs = [];

        parent::tearDown();
    }

    public function testValueBreakingOutOfTheRootBlockIsRejected(): void
    {
        $theme = $this->createBoundTheme('theme_sec_break', $this->getAccessUrl());

        $this->patchVariables($theme->getId(), [
            '--color-primary-base' => "0 0 0} body{background:url('//evil.example/p')} :root{--x:1",
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testKeyNotMatchingThePatternIsRejected(): void
    {
        $theme = $this->createBoundTheme('theme_sec_key', $this->getAccessUrl());

        $this->patchVariables($theme->getId(), [
            '--color-primary-base' => '0 0 0',
            'color-primary' => '1 2 3',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testValueThatIsNotThreeRgbChannelsIsRejected(): void
    {
        $theme = $this->createBoundTheme('theme_sec_value', $this->getAccessUrl());

        $this->patchVariables($theme->getId(), [
            '--color-primary-base' => 'red',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMoreThan64VariablesAreRejected(): void
    {
        $theme = $this->createBoundTheme('theme_sec_count', $this->getAccessUrl());

        $variables = ['--color-primary-base' => '0 0 0'];
        for ($i = 0; $i < 65; $i++) {
            $variables['--color-x'.$i] = '1 2 3';
        }

        $this->patchVariables($theme->getId(), $variables);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testValidPaletteIsStoredWithoutExtraRules(): void
    {
        $theme = $this->createBoundTheme('theme_sec_valid', $this->getAccessUrl());
        $palette = $this->validPalette();

        $this->patchVariables($theme->getId(), $palette);

        $this->assertResponseIsSuccessful();

        $slug = $this->reloadSlug($theme->getId());
        $this->writtenSlugs[] = $slug;

        $css = $this->themesFilesystem()->read($slug.'/colors.css');

        // The stylesheet holds exactly the :root block for the palette, nothing else. The line order is not
        // compared: a MySQL JSON column reorders the keys of the stored variables (MariaDB keeps them).
        $expectedLines = explode("\n", ColorThemeArchive::colorCss($palette));
        $actualLines = explode("\n", $css);
        sort($expectedLines);
        sort($actualLines);
        $this->assertSame($expectedLines, $actualLines);
        $this->assertStringNotContainsString('body', $css);
        $this->assertStringNotContainsString('url(', $css);
    }

    public function testThemeBoundToAnotherPortalIsNotFound(): void
    {
        $otherUrl = $this->createAccessUrl('https://other-portal.example.org/');
        $foreignTheme = $this->createBoundTheme('theme_sec_foreign', $otherUrl);

        // The admin acts on the default portal (http://localhost/), so the
        // foreign theme is invisible to it and the write must 404.
        $this->patchVariables($foreignTheme->getId(), $this->validPalette());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string, string>
     */
    private function validPalette(): array
    {
        return [
            '--color-primary-base' => '46 117 163',
            '--color-primary-gradient' => '36 77 103',
            '--color-secondary-base' => '243 126 47',
        ];
    }

    private function createBoundTheme(string $title, AccessUrl $url): ColorTheme
    {
        $em = $this->getEntityManager();

        $theme = (new ColorTheme())
            ->setTitle($title)
            ->setVariables($this->validPalette())
        ;
        $em->persist($theme);

        $binding = (new AccessUrlRelColorTheme())
            ->setColorTheme($theme)
            ->setUrl($url)
            ->setActive(false)
        ;
        $em->persist($binding);
        $em->flush();

        return $theme;
    }

    private function createAccessUrl(string $url): AccessUrl
    {
        /** @var AccessUrlRepository $repo */
        $repo = static::getContainer()->get(AccessUrlRepository::class);

        $accessUrl = (new AccessUrl())
            ->setUrl($url)
            ->setActive(1)
            ->setCreator($this->getAdmin())
        ;
        $repo->create($accessUrl);

        return $accessUrl;
    }

    /**
     * @param array<string, string> $variables
     */
    private function patchVariables(int $themeId, array $variables): void
    {
        $admin = $this->createUser('theme_admin', '', '', 'ROLE_ADMIN');

        $this
            ->createClientWithCredentials($this->getUserTokenFromUser($admin))
            ->request('PATCH', '/api/color_themes/'.$themeId, [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['variables' => $variables],
            ])
        ;
    }

    private function reloadSlug(int $themeId): string
    {
        $em = $this->getEntityManager();
        $em->clear();

        $theme = $em->getRepository(ColorTheme::class)->find($themeId);
        self::assertInstanceOf(ColorTheme::class, $theme);

        return (string) $theme->getSlug();
    }

    private function themesFilesystem(): FilesystemOperator
    {
        /** @var FilesystemOperator $filesystem */
        $filesystem = static::getContainer()->get('oneup_flysystem.themes_filesystem');

        return $filesystem;
    }
}
