<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ImportCGlossaryAction
{
    public function __invoke(
        Request $request,
        CGlossaryRepository $repository,
        CGlossaryCategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $file = $request->files->get('file');
        $fileType = (string) $request->request->get('file_type', '');
        $replace = 'true' === (string) $request->request->get('replace', 'false');
        $update = 'true' === (string) $request->request->get('update', 'false');
        $courseId = (int) $request->request->get('cid', '0');
        $sessionId = (int) $request->request->get('sid', '0');

        $course = $courseId > 0 ? $entityManager->find(Course::class, $courseId) : null;
        $session = $sessionId > 0 ? $entityManager->find(Session::class, $sessionId) : null;

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }
        if ($sessionId > 0 && !$session instanceof Session) {
            throw new BadRequestHttpException('Session not found.');
        }
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('Invalid file.');
        }

        $rows = match ($fileType) {
            'csv' => $this->readCsv($file),
            'xls' => $this->readSpreadsheet($file),
            default => throw new BadRequestHttpException('Invalid file type.'),
        };

        if ([] === $rows) {
            throw new BadRequestHttpException('Invalid data.');
        }

        if ($replace) {
            $existingTerms = $repository->getResourcesByCourse($course, $session)->getQuery()->getResult();
            foreach ($existingTerms as $term) {
                if ($term instanceof CGlossary) {
                    $repository->delete($term);
                }
            }
        }

        $categoryCache = [];
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $title = trim($row['term']);
            if ('' === $title) {
                ++$skipped;

                continue;
            }

            $category = $this->resolveCategory(
                trim($row['category']),
                $course,
                $session,
                $categoryRepository,
                $entityManager,
                $categoryCache,
            );

            $existing = $repository->getResourcesByCourse($course, $session)
                ->andWhere('resource.title = :title')
                ->setParameter('title', $title)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if ($existing instanceof CGlossary) {
                if (!$update) {
                    ++$skipped;

                    continue;
                }

                $existing
                    ->setDescription($row['definition'])
                    ->setCategory($category)
                ;
                $entityManager->persist($existing);
                ++$updated;

                continue;
            }

            $term = (new CGlossary())
                ->setTitle($title)
                ->setDescription($row['definition'])
                ->setCategory($category)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $entityManager->persist($term);
            ++$imported;
        }

        $entityManager->flush();

        return new JsonResponse([
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array<int, array{term:string, definition:string, category:string}>
     */
    private function readCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');
        if (false === $handle) {
            return [];
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $term = isset($row[0]) ? trim((string) $row[0]) : '';
            $definition = isset($row[1]) ? trim((string) $row[1]) : '';
            $category = isset($row[2]) ? trim((string) $row[2]) : '';
            $normalizedTerm = preg_replace('/^\xEF\xBB\xBF/', '', $term) ?? $term;

            if ('term' === strtolower($normalizedTerm) && 'definition' === strtolower($definition)) {
                continue;
            }

            $rows[] = [
                'term' => $normalizedTerm,
                'definition' => $definition,
                'category' => $category,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array{term:string, definition:string, category:string}>
     */
    private function readSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $values = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $values[] = trim((string) $cell->getValue());
            }

            $term = $values[0] ?? '';
            $definition = $values[1] ?? '';
            $category = $values[2] ?? '';
            if ('term' === strtolower($term) && 'definition' === strtolower($definition)) {
                continue;
            }

            $rows[] = [
                'term' => $term,
                'definition' => $definition,
                'category' => $category,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, CGlossaryCategory> $cache
     */
    private function resolveCategory(
        string $title,
        Course $course,
        ?Session $session,
        CGlossaryCategoryRepository $repository,
        EntityManagerInterface $entityManager,
        array &$cache,
    ): ?CGlossaryCategory {
        if ('' === $title) {
            return null;
        }

        $key = mb_strtolower($title);
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $category = $repository->getResourcesByCourse($course, $session)
            ->andWhere('resource.title = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$category instanceof CGlossaryCategory) {
            $category = (new CGlossaryCategory())
                ->setTitle($title)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $entityManager->persist($category);
        }

        $cache[$key] = $category;

        return $category;
    }
}
