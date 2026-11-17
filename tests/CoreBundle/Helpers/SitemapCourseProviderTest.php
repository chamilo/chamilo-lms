<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUserCatalogue;
use Chamilo\CoreBundle\Helpers\SitemapCourseProvider;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SitemapCourseProviderTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testOnlyUnrestrictedPublicCoursesAppearInAnonymousSitemap(): void
    {
        self::bootKernel();

        $accessUrl = $this->getAccessUrl();
        $public = $this->createCourse('Sitemap visible public course');
        $registered = $this->createCourse('Sitemap registered course');
        $hidden = $this->createCourse('Sitemap hidden course');
        $restricted = $this->createCourse('Sitemap group restricted course');
        $userSpecific = $this->createCourse('Sitemap user specific course');

        $public->setVisibility(Course::OPEN_WORLD);
        $registered->setVisibility(Course::REGISTERED);
        $hidden->setVisibility(Course::HIDDEN);
        $restricted->setVisibility(Course::OPEN_WORLD);
        $userSpecific->setVisibility(Course::OPEN_WORLD);

        $group = $this->createUserGroup('Sitemap private audience');

        $publicRule = (new CatalogueCourseRelAccessUrlRelUsergroup())
            ->setAccessUrl($accessUrl)
            ->setCourse($public)
            ->setUsergroup(null)
        ;
        $restrictedRule = (new CatalogueCourseRelAccessUrlRelUsergroup())
            ->setAccessUrl($accessUrl)
            ->setCourse($restricted)
            ->setUsergroup($group)
        ;
        $userRule = (new CourseRelUserCatalogue())
            ->setCourse($userSpecific)
            ->setUser($this->getUser('admin'))
            ->setVisible(1)
        ;

        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ([$public, $registered, $hidden, $restricted, $userSpecific, $publicRule, $restrictedRule, $userRule] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        $provider = self::getContainer()->get(SitemapCourseProvider::class);
        $ids = $provider->findPublicCourseIds($accessUrl, 1);

        self::assertContains((int) $public->getId(), $ids);
        self::assertNotContains((int) $registered->getId(), $ids);
        self::assertNotContains((int) $hidden->getId(), $ids);
        self::assertNotContains((int) $restricted->getId(), $ids);
        self::assertNotContains((int) $userSpecific->getId(), $ids);
        self::assertGreaterThanOrEqual(\count($ids), $provider->countPublicCourses($accessUrl));
    }
}
