<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CStudentPublicationCorrectionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $publicationRepo = self::getContainer()->get(CStudentPublicationRepository::class);
        $correctionRepo = self::getContainer()->get(CStudentPublicationCorrectionRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $publication = (new CStudentPublication())
            ->setTitle('publi')
            ->setDescription('desc')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
        ;
        $em->persist($publication);

        $file = $this->getUploadedFile();

        $correction = (new CStudentPublicationCorrection())
            ->setParent($publication)
            ->setCreator($teacher)
            ->setTitle($file->getClientOriginalName())
        ;
        $correctionRepo->create($correction);
        $correctionRepo->addFile($correction, $file);
        $correctionRepo->update($correction);
        $em->flush();

        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');
        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $documentRepo->create($document);

        $document = (new CStudentPublicationRelDocument())
            ->setPublication($publication)
            ->setDocument($document)
        ;
        $em->persist($document);
        $em->flush();
        $em->clear();

        /** @var CStudentPublication $publication */
        $publication = $publicationRepo->find($publication->getIid());

        $this->assertNotNull($publication->getCorrection());
        $this->assertSame(1, $publicationRepo->count([]));
        $this->assertSame(1, $correctionRepo->count([]));

        $em->remove($publication);
        $em->flush();

        $this->assertSame(0, $publicationRepo->count([]));
        $this->assertSame(0, $correctionRepo->count([]));
    }
}
