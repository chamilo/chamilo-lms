<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportCGlossaryAction
{
    public function __invoke(Request $request, CGlossaryRepository $repo, EntityManager $em): Response
    {
        $file = $request->files->get('file');
        $fileType = $request->request->get('file_type');
        $replace = $request->request->get('replace');
        $update = $request->request->get('update');
        $cid = $request->request->get('cid');
        $sid = $request->request->get('sid');

        $course = null;
        $session = null;
        if (0 !== $cid) {
            $course = $em->getRepository(Course::class)->find($cid);
        }
        if (0 !== $sid) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('Invalid file');
        }

        $data = [];
        if ('csv' === $fileType) {
            if (($handle = fopen($file->getPathname(), 'r')) !== false) {
                $header = fgetcsv($handle, 0, ';');
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $term = isset($row[0]) ? trim($row[0]) : '';
                    $definition = isset($row[1]) ? trim($row[1]) : '';
                    $data[$term] = $definition;
                }
                fclose($handle);
            }
        } elseif ('xls' === $fileType) {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $firstRow = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($firstRow) {
                    $firstRow = false;

                    continue;
                }
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $term = isset($rowData[0]) ? utf8_decode(trim($rowData[0])) : '';
                $definition = isset($rowData[1]) ? utf8_decode(trim($rowData[1])) : '';
                $data[$term] = $definition;
            }
        } else {
            throw new BadRequestHttpException('Invalid file type');
        }

        if (empty($data)) {
            throw new BadRequestHttpException('Invalid data');
        }

        if ('true' === $replace) {
            $qb = $repo->getResourcesByCourse($course, $session);
            $allGlossaries = $qb->getQuery()->getResult();
            if ($allGlossaries) {
                /** @var CGlossary $item */
                foreach ($allGlossaries as $item) {
                    $termToDelete = $repo->find($item->getIid());
                    if (null !== $termToDelete) {
                        $repo->delete($termToDelete);
                    }
                }
            }
        }

        if ('true' === $update) {
            foreach ($data as $termToUpdate => $descriptionToUpdate) {
                // Check if the term already exists
                $qb = $repo->getResourcesByCourse($course, $session)
                    ->andWhere('resource.name = :name')
                    ->setParameter('name', $termToUpdate)
                ;
                /** @var CGlossary $existingGlossaryTerm */
                $existingGlossaryTerm = $qb->getQuery()->getOneOrNullResult();
                if (null !== $existingGlossaryTerm) {
                    $existingGlossaryTerm->setDescription($descriptionToUpdate);
                    $repo->update($existingGlossaryTerm);
                    unset($data[$termToUpdate]);
                }
            }
        }

        foreach ($data as $term => $description) {
            $qb = $repo->getResourcesByCourse($course, $session)
                ->andWhere('resource.name = :name')
                ->setParameter('name', $term)
            ;
            /** @var CGlossary $existingNewGlossaryTerm */
            $existingNewGlossaryTerm = $qb->getQuery()->getOneOrNullResult();
            if (!$existingNewGlossaryTerm) {
                $newGlossary = (new CGlossary())
                    ->setName($term)
                    ->setDescription($description)
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $repo->create($newGlossary);
            }
        }

        return new Response(json_encode($data), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
