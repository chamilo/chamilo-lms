<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUserCatalogue;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMXPath;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const PHP_URL_PATH;

class SitemapControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testAnonymousSitemapEndpointsRespectIndexingConfiguration(): void
    {
        $client = self::createClient();
        $pluginEnabled = self::getContainer()->get(PluginHelper::class)->isPluginEnabled('NoSearchIndex');
        $settings = self::getContainer()->get(SettingsManager::class);
        $cataloguePublished = 'true' === (string) $settings->getSetting('catalog.course_catalog_published', true);
        $catalogueMode = (string) $settings->getSetting('catalog.show_courses_sessions', true);

        $client->request('GET', '/sitemap.xml');

        if ($pluginEnabled) {
            self::assertResponseStatusCodeSame(404);

            return;
        }

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/xml', (string) $client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('<sitemapindex ', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('/sitemap-pages.xml', (string) $client->getResponse()->getContent());

        $client->request('GET', '/sitemap-pages.xml');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<urlset ', (string) $client->getResponse()->getContent());

        $pages = (string) $client->getResponse()->getContent();
        self::assertSame($cataloguePublished && \in_array($catalogueMode, ['0', '2'], true), str_contains($pages, '/catalogue/courses'));
        self::assertSame($cataloguePublished && \in_array($catalogueMode, ['1', '2'], true), str_contains($pages, '/catalogue/sessions'));

        $client->request('GET', '/sitemap-courses-999999.xml');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * A public sitemap must not leak registered, hidden or audience-restricted courses.
     * These assertions exercise the anonymous HTTP route, not just its query provider.
     */
    public function testAnonymousHttpSitemapIncludesOnlyUnrestrictedPublicCourses(): void
    {
        $client = self::createClient();
        $this->assertSearchIndexingEnabledForHttpTests();

        $public = $this->createPublicCourse('Sitemap HTTP public '.uniqid());
        $closed = $this->createSitemapCourse('Sitemap HTTP closed '.uniqid());
        $registered = $this->createSitemapCourse('Sitemap HTTP registered '.uniqid());
        $platform = $this->createSitemapCourse('Sitemap HTTP platform '.uniqid());
        $hidden = $this->createSitemapCourse('Sitemap HTTP hidden '.uniqid());
        $restricted = $this->createSitemapCourse('Sitemap HTTP restricted '.uniqid());
        $userSpecific = $this->createSitemapCourse('Sitemap HTTP personal '.uniqid());

        $closed->setVisibility(Course::CLOSED);
        $registered->setVisibility(Course::REGISTERED);
        $platform->setVisibility(Course::OPEN_PLATFORM);
        $hidden->setVisibility(Course::HIDDEN);
        $restricted->setVisibility(Course::OPEN_WORLD);
        $userSpecific->setVisibility(Course::OPEN_WORLD);

        $restrictedRule = (new CatalogueCourseRelAccessUrlRelUsergroup())
            ->setAccessUrl($this->getAccessUrl())
            ->setCourse($restricted)
            ->setUsergroup($this->createUserGroup('Sitemap HTTP audience '.uniqid()))
        ;
        $userRule = (new CourseRelUserCatalogue())
            ->setCourse($userSpecific)
            ->setUser($this->getUser('admin'))
            ->setVisible(1)
        ;

        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ([$closed, $registered, $platform, $hidden, $restricted, $userSpecific, $restrictedRule, $userRule] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        $publicId = (int) $public->getId();
        $excludedIds = [
            (int) $closed->getId(),
            (int) $registered->getId(),
            (int) $platform->getId(),
            (int) $hidden->getId(),
            (int) $restricted->getId(),
            (int) $userSpecific->getId(),
        ];

        $this->setCatalogueSettings('true', '0');

        $indexLocations = $this->requestSitemapLocations($client, '/sitemap.xml', 'sitemapindex');
        self::assertTrue($this->hasPathSuffix($indexLocations, '/sitemap-courses-1.xml'));
        self::assertTrue($this->hasPathSuffix($indexLocations, '/sitemap-pages.xml'));

        $courseLocations = $this->requestSitemapLocations($client, '/sitemap-courses-1.xml', 'urlset');
        $courseIds = $this->courseIdsFromLocations($courseLocations);

        self::assertContains($publicId, $courseIds);
        foreach ($excludedIds as $excludedId) {
            self::assertNotContains($excludedId, $courseIds);
        }
        self::assertCount(\count(array_unique($courseIds)), $courseIds);
    }

    /**
     * Publishing and catalogue mode must gate the HTTP URLs on each request.
     * A previously visible course must immediately disappear when the setting changes.
     */
    public function testAnonymousHttpSitemapFollowsCataloguePublishingAndMode(): void
    {
        $client = self::createClient();
        $this->assertSearchIndexingEnabledForHttpTests();

        $publicId = (int) $this->createPublicCourse('Sitemap HTTP settings '.uniqid())->getId();

        $this->setCatalogueSettings('true', '0');
        $this->assertSitemapMode($client, true, false, $publicId);

        $this->setCatalogueSettings('false', '0');
        $this->assertSitemapMode($client, false, false);

        $this->setCatalogueSettings('true', '1');
        $this->assertSitemapMode($client, false, true);

        $this->setCatalogueSettings('true', '-1');
        $this->assertSitemapMode($client, false, false);

        $this->setCatalogueSettings('true', '2');
        $this->assertSitemapMode($client, true, true, $publicId);
    }

    private function assertSitemapMode(
        KernelBrowser $client,
        bool $coursesPublished,
        bool $sessionsPublished,
        ?int $publicCourseId = null,
    ): void {
        $indexLocations = $this->requestSitemapLocations($client, '/sitemap.xml', 'sitemapindex');
        self::assertTrue($this->hasPathSuffix($indexLocations, '/sitemap-pages.xml'));
        self::assertSame($coursesPublished, $this->hasPathSuffix($indexLocations, '/sitemap-courses-1.xml'));

        $pageLocations = $this->requestSitemapLocations($client, '/sitemap-pages.xml', 'urlset');
        self::assertSame($coursesPublished, $this->hasPathSuffix($pageLocations, '/catalogue/courses'));
        self::assertSame($sessionsPublished, $this->hasPathSuffix($pageLocations, '/catalogue/sessions'));

        if (!$coursesPublished) {
            $client->request('GET', '/sitemap-courses-1.xml');
            self::assertResponseStatusCodeSame(404);

            return;
        }

        $courseLocations = $this->requestSitemapLocations($client, '/sitemap-courses-1.xml', 'urlset');
        if (null !== $publicCourseId) {
            self::assertContains($publicCourseId, $this->courseIdsFromLocations($courseLocations));
        }
    }

    private function assertSearchIndexingEnabledForHttpTests(): void
    {
        self::assertFalse(
            self::getContainer()->get(PluginHelper::class)->isPluginEnabled('NoSearchIndex'),
            'NoSearchIndex must be disabled for the public sitemap HTTP tests. The existing test covers its 404 response.'
        );
    }

    private function createSitemapCourse(string $title): Course
    {
        $course = $this->createCourse($title);
        self::assertInstanceOf(Course::class, $course);

        return $course;
    }

    private function createPublicCourse(string $title): Course
    {
        $course = $this->createSitemapCourse($title);
        $course->setVisibility(Course::OPEN_WORLD);

        $rule = (new CatalogueCourseRelAccessUrlRelUsergroup())
            ->setAccessUrl($this->getAccessUrl())
            ->setCourse($course)
            ->setUsergroup(null)
        ;

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($course);
        $em->persist($rule);
        $em->flush();

        return $course;
    }

    private function setCatalogueSettings(string $published, string $mode): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('catalog.course_catalog_published', $published);
        $settings->updateSetting('catalog.show_courses_sessions', $mode);
        $settings->updateSetting('catalog.only_show_selected_courses', 'false');
        $settings->updateSetting('catalog.only_show_course_from_selected_category', '');
    }

    /**
     * @return list<string>
     */
    private function requestSitemapLocations(KernelBrowser $client, string $path, string $root): array
    {
        $client->request('GET', $path);
        self::assertResponseStatusCodeSame(200);
        self::assertStringContainsString('application/xml', (string) $client->getResponse()->headers->get('Content-Type'));

        $xml = new DOMDocument();
        self::assertTrue($xml->loadXML((string) $client->getResponse()->getContent()), 'The sitemap must contain valid XML.');
        self::assertSame($root, $xml->documentElement?->localName);
        self::assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->documentElement?->namespaceURI);

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $element = 'sitemapindex' === $root ? 'sitemap' : 'url';
        $nodes = $xpath->query('/s:'.$root.'/s:'.$element.'/s:loc');
        if (false === $nodes) {
            self::fail('The sitemap XML location query failed.');
        }

        $locations = [];
        foreach ($nodes as $node) {
            $locations[] = trim($node->textContent);
        }

        return $locations;
    }

    /**
     * @param list<string> $locations
     */
    private function hasPathSuffix(array $locations, string $suffix): bool
    {
        foreach ($locations as $location) {
            if (str_ends_with((string) parse_url($location, PHP_URL_PATH), $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $locations
     *
     * @return list<int>
     */
    private function courseIdsFromLocations(array $locations): array
    {
        $ids = [];
        foreach ($locations as $location) {
            $path = parse_url($location, PHP_URL_PATH);
            self::assertIsString($path);
            self::assertSame(1, preg_match('#/course/([0-9]+)/about$#', $path, $matches), $location);
            $ids[] = (int) $matches[1];
        }

        return $ids;
    }
}
