<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CourseHomeControllerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testIndexJsonAction(): void
    {
        $course = $this->createCourse('course 1');
        $this->getClientWithGuiCredentials('admin', 'admin')->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(
            [
                'course' => [
                    'code' => $course->getCode(),
                ],
            ]
        );
    }

    public function testRedirectTool(): void
    {
        $course = $this->createCourse('new');
        $this->getClientWithGuiCredentials('admin', 'admin')->request(
            'GET',
            '/course/'.$course->getId().'/tool/document'
        );

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseHasHeader('location');
        $this->assertResponseRedirects(
            '/resources/document/'.$course->getResourceNode()->getId().'/?cid='.$course->getId().'&sid=0&gid=0'
        );
    }
}
