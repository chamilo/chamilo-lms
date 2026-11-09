<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The colors page lets an administrator replace the images at the bottom of some pages (My
 * courses, the administration, the social network...) or remove them, in the active visual theme,
 * as they do for the logos. The image the theme ships stays as the default: restoring it shows it
 * again.
 */
final class ThemePageBackgroundTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    // 1x1 transparent PNG
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private string $theme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = self::getContainer()->get(ThemeHelper::class)->getVisualTheme();
        $this->removeChoices();
    }

    protected function tearDown(): void
    {
        $this->removeChoices();

        parent::tearDown();
    }

    public function testAdministratorReplacesHidesAndRestoresAPageBackground(): void
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser(
            $this->createUser('page_background_admin', '', '', 'ROLE_ADMIN'),
        ));
        $url = '/themes/'.$this->theme.'/page-backgrounds/landscape';

        $client->request('POST', $url, ['extra' => ['files' => ['image' => $this->createUpload(base64_decode(self::PNG))]]]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertStringContainsString('/images/bg-landscape-custom.png?v=', $this->getPageBackgrounds($client)['landscape']);
        $this->assertTrue($this->getThemeFilesystem()->fileExists($this->theme.'/images/bg-landscape.png'), 'The image the theme ships is kept as the default.');

        $client->request('PATCH', $url, ['json' => ['hidden' => true]]);
        $this->assertResponseIsSuccessful();
        $this->assertNull($this->getPageBackgrounds($client)['landscape'], 'A hidden image is not shown on the pages.');

        $client->request('PATCH', $url, ['json' => ['hidden' => false]]);
        $this->assertStringContainsString('bg-landscape-custom.png', $this->getPageBackgrounds($client)['landscape'], 'Showing it again keeps the uploaded image.');

        $client->request('DELETE', $url);
        $this->assertResponseIsSuccessful();
        $this->assertStringEndsWith('/images/bg-landscape.png', $this->getPageBackgrounds($client)['landscape']);
        $this->assertFalse($this->getThemeFilesystem()->fileExists($this->theme.'/images/bg-landscape-custom.png'));
    }

    public function testOnlyImagesAreAccepted(): void
    {
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser(
            $this->createUser('page_background_admin_type', '', '', 'ROLE_ADMIN'),
        ));

        // Named as an image, but not one: the content decides
        $client->request('POST', '/themes/'.$this->theme.'/page-backgrounds/landscape', [
            'extra' => ['files' => ['image' => $this->createUpload('<script>alert(1)</script>')]],
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertSame([], self::getContainer()->get(ThemeHelper::class)->getPageBackgroundChoices($this->theme));
    }

    public function testOnlyAdministratorsChangeTheBackgroundsOfTheActiveTheme(): void
    {
        $teacher = $this->createClientWithCredentials($this->getUserTokenFromUser(
            $this->createUser('page_background_teacher', '', '', 'ROLE_TEACHER'),
        ));
        $teacher->request('PATCH', '/themes/'.$this->theme.'/page-backgrounds/landscape', ['json' => ['hidden' => true]]);
        $this->assertResponseStatusCodeSame(403);

        $admin = $this->createClientWithCredentials($this->getUserTokenFromUser(
            $this->createUser('page_background_admin_theme', '', '', 'ROLE_ADMIN'),
        ));
        $admin->request('PATCH', '/themes/another-theme/page-backgrounds/landscape', ['json' => ['hidden' => true]]);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @return array<string, string|null>
     */
    private function getPageBackgrounds(mixed $client): array
    {
        return $client->request('GET', '/platform-config/list')->toArray()['page_backgrounds'];
    }

    private function createUpload(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bg');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'background.png', 'image/png', null, true);
    }

    private function getThemeFilesystem(): FilesystemOperator
    {
        return self::getContainer()->get('oneup_flysystem.themes_filesystem');
    }

    /**
     * The tests share the theme directory with the platform: they leave it as they found it.
     */
    private function removeChoices(): void
    {
        $fs = $this->getThemeFilesystem();
        foreach (ThemeHelper::PAGE_BACKGROUND_SLOTS as $slot) {
            foreach (['png', 'jpg', 'webp'] as $extension) {
                if ($fs->fileExists($this->theme.'/images/bg-'.$slot.'-custom.'.$extension)) {
                    $fs->delete($this->theme.'/images/bg-'.$slot.'-custom.'.$extension);
                }
            }
        }
        if ($fs->fileExists($this->theme.'/page-backgrounds.json')) {
            $fs->delete($this->theme.'/page-backgrounds.json');
        }
    }
}
