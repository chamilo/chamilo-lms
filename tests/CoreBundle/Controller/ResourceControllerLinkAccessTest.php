<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * The integer-id /r/{tool}/{type}/{id}/link and /r/resource_files/{id}/variants
 * routes must apply the same ResourceNodeVoter::VIEW check as view()/download().
 */
class ResourceControllerLinkAccessTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const PRIVATE_URL = 'https://private.example.invalid/secret';

    public function testLinkRedirectIsDeniedForUserOutsideTheCourse(): void
    {
        $client = static::createClient();
        $link = $this->createPrivateCourseLink();

        $client->loginUser($this->createUser('link_stranger'));
        $client->request('GET', $this->linkUrl($link));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertFalse($client->getResponse()->isRedirection(), 'The private URL must not be disclosed.');
    }

    public function testLinkRedirectIsDeniedForAnonymousVisitor(): void
    {
        $client = static::createClient();
        $link = $this->createPrivateCourseLink();

        $client->request('GET', $this->linkUrl($link));

        $this->assertStringNotContainsString(
            self::PRIVATE_URL,
            (string) $client->getResponse()->headers->get('Location')
        );
    }

    public function testLinkRedirectIsAllowedForAdmin(): void
    {
        $client = static::createClient();
        $link = $this->createPrivateCourseLink();

        $client->loginUser($this->getUser('admin'));
        $client->request('GET', $this->linkUrl($link));

        $this->assertResponseRedirects(self::PRIVATE_URL);
    }

    public function testVariantsAreDeniedForUserOutsideTheCourse(): void
    {
        $client = static::createClient();
        $link = $this->createPrivateCourseLink();

        $client->loginUser($this->createUser('variants_stranger'));
        $client->request('GET', '/r/resource_files/'.$link->getResourceNode()->getId().'/variants');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createPrivateCourseLink(): CLink
    {
        $course = $this->createCourse('PrivateLinkCourse');
        $course->setVisibility(Course::CLOSED);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $link = (new CLink())
            ->setUrl(self::PRIVATE_URL)
            ->setTitle('Private link')
            ->setTarget('_blank')
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($course)
        ;
        self::getContainer()->get(CLinkRepository::class)->create($link);

        return $link;
    }

    /**
     * No cid on purpose: the route is reachable without a course context, which is
     * what exposed the private URL.
     */
    private function linkUrl(CLink $link): string
    {
        return '/r/course_tool/links/'.$link->getResourceNode()->getId().'/link';
    }
}
