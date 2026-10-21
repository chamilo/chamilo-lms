<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use CourseCategory as LegacyCourseCategory;
use Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Regression test for the course category advisory.
 *
 * The administration list of course categories printed the name and the code as they were
 * stored, so a category named with markup ran in the browser of every administrator.
 */
final class CourseCategoryListEscapingTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCategoryNameAndCodeAreEscapedInTheAdministrationList(): void
    {
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());

        // The legacy helpers read the user and the language from the session of the current request.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        $category = (new CourseCategory())
            ->setCode('XSS<i>')
            ->setTitle('<img src=x onerror=alert(1)>')
            ->setDescription('')
            ->setTreePos(99)
            ->setChildrenCount(0)
            ->setAuthCourseChild('TRUE')
            ->setAuthCatChild('TRUE')
        ;
        $category->addUrl($this->getAccessUrl());
        self::getContainer()->get(CourseCategoryRepository::class)->update($category);

        $html = LegacyCourseCategory::listCategories();

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html, 'The name is shown, escaped.');
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringNotContainsString('XSS<i>', $html);
    }
}
