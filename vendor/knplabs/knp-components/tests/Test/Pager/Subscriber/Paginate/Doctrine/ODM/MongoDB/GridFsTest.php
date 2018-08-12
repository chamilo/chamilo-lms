<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Test\Tool\BaseTestCaseMongoODM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Document\Image;

class GridFsTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    function shouldPaginate()
    {
        $this->populate();

        $query = $this->dm
            ->createQueryBuilder('Test\Fixture\Document\Image')
            ->getQuery()
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);

        $cursor = $query->execute();
        $this->assertEquals(4, count($view->getItems()));
    }

    private function populate()
    {
        $mockFile = __DIR__.'/summer.gif';
        $dm = $this->getMockDocumentManager();
        $summer = new Image;
        $summer->setTitle('summer');
        $summer->setFile($mockFile);

        $winter = new Image;
        $winter->setTitle('winter');
        $winter->setFile($mockFile);

        $autumn = new Image;
        $autumn->setTitle('autumn');
        $autumn->setFile($mockFile);

        $spring = new Image;
        $spring->setTitle('spring');
        $spring->setFile($mockFile);

        $dm->persist($summer);
        $dm->persist($winter);
        $dm->persist($autumn);
        $dm->persist($spring);
        $dm->flush();
    }
}
