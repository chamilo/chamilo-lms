<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Repository\PageCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class PageCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $pageCategoryRepo = self::getContainer()->get(PageCategoryRepository::class);
        $defaultCount = $pageCategoryRepo->count([]);

        $user = $this->getAdmin();
        $category = (new PageCategory())
            ->setCreator($user)
            ->setTitle('category1')
            ->setType('simple')
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
        ;
        $this->assertHasNoEntityViolations($category);
        $em->persist($category);
        $em->flush();

        $this->assertSame($defaultCount + 1, $pageCategoryRepo->count([]));
    }
}
