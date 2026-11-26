<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ThemeControllerTest extends WebTestCase
{
    public function testValidAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/themes/chamilo/colors.css');

        $this->assertResponseIsSuccessful();
    }

    public function testInvalidAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/themes/chamilo/nonexistent.css');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAccessToSystemFiles(): void
    {
        $client = static::createClient();
        $client->request('GET', '/themes/chamilo/../../../../../../etc/passwd');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('GET', 'themes/chamilo/../../../.env');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * A theme created on the colors page only has its palette and the logos uploaded there. The
     * files it lacks must come from chamilo3, which holds the current Chamilo favicon and logo,
     * and not from the "chamilo" theme, which still holds the previous ones: activating a new
     * theme brought the previous Chamilo favicon back on every page.
     */
    public function testThemeWithoutItsOwnFilesShowsTheCurrentChamiloFaviconAndLogo(): void
    {
        $client = static::createClient();

        /** @var FilesystemOperator $filesystem */
        $filesystem = self::getContainer()->get('oneup_flysystem.themes_filesystem');
        $theme = 'zz-theme-without-files';
        $this->assertFalse($filesystem->directoryExists($theme));

        $currentFavicon = $filesystem->read('chamilo3/images/favicon.ico');
        $this->assertNotSame($filesystem->read('chamilo/images/favicon.ico'), $currentFavicon, 'Both themes ship a different favicon, or this test proves nothing.');

        $client->request('GET', '/themes/'.$theme.'/images/favicon.ico');
        $this->assertResponseIsSuccessful();
        $this->assertSame($currentFavicon, $client->getInternalResponse()->getContent());

        $client->request('GET', '/themes/'.$theme.'/logo/header');
        $this->assertResponseIsSuccessful();
        $this->assertSame($filesystem->read('chamilo3/images/header-logo.svg'), $client->getInternalResponse()->getContent());
    }
}
