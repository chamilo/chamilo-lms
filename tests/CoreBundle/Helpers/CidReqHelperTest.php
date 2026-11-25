<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The course of the current request must not depend on the session alone: the session is not locked, so a concurrent
 * request can overwrite the course id this request stored in it (the exercise recycle bin answered "A valid course id
 * is required." to a request that carried a valid cid, whenever such a request ran in parallel).
 */
final class CidReqHelperTest extends TestCase
{
    public function testTheCourseIdResolvedForTheRequestWinsOverAnEmptiedSession(): void
    {
        $request = $this->request();
        $request->attributes->set(CidReqHelper::COURSE_ID_ATTRIBUTE, 35);

        // The session lost its cid (overwritten by a concurrent request).
        $this->assertNull($request->getSession()->get('cid'));
        $this->assertSame(35, $this->helper($request)->getCourseId());
    }

    public function testWithoutAResolvedCourseTheSessionIsStillTheSource(): void
    {
        $request = $this->request();
        $request->getSession()->set('cid', 7);

        $this->assertSame(7, $this->helper($request)->getCourseId());
    }

    public function testWithNeitherThereIsNoCourse(): void
    {
        $this->assertNull($this->helper($this->request())->getCourseId());
    }

    private function request(): Request
    {
        $request = Request::create('/api/exercise/runtime/1/recycle-bin?cid=35');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function helper(Request $request): CidReqHelper
    {
        $stack = new RequestStack();
        $stack->push($request);

        return new CidReqHelper($stack, $this->createMock(EntityManagerInterface::class));
    }
}
