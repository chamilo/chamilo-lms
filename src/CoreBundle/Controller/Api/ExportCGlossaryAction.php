<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CGlossaryCategory;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use Export;
use PDF;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

readonly class ExportCGlossaryAction
{
    private const array MIME_TYPES = [
        'csv' => 'text/csv; charset=UTF-8',
        'xls' => 'application/vnd.ms-excel',
        'pdf' => 'application/pdf',
    ];

    public function __construct(
        private TranslatorInterface $translator,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @throws Exception|NotSupported|ORMException|OptimisticLockException|TransactionRequiredException
     */
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        CGlossaryCategoryRepository $categoryRepository,
        EntityManager $em,
    ): Response {
        $format = (string) $request->request->get('format', '');
        $cid = $request->request->getInt('cid');
        // sid is sent as an empty string when no session is selected; getInt()
        // rejects that (FILTER_VALIDATE_INT has no FILTER_NULL_ON_FAILURE here),
        // so cast the raw value instead of using the strict accessor.
        $sid = (int) $request->request->get('sid', '0');
        $categoryId = (int) $request->request->get('categoryId', '0');

        if (!\in_array($format, ['csv', 'xls', 'pdf'], true)) {
            throw new BadRequestHttpException('Invalid export format.');
        }

        $course = null;
        $session = null;

        if ($cid > 0) {
            $course = $em->find(Course::class, $cid);
        }
        if ($sid > 0) {
            $session = $em->find(Session::class, $sid);
        }

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }

        $category = null;
        if ($categoryId > 0) {
            $category = $categoryRepository->findInCourseContext($categoryId, $course, $session);
            if (!$category instanceof CGlossaryCategory) {
                throw new BadRequestHttpException('Glossary category not found in the current course/session context.');
            }
        }

        $qb = $repo->getResourcesByCourse($course, $session);
        if ($category instanceof CGlossaryCategory) {
            $qb->andWhere('resource.category = :category')
                ->setParameter('category', $category)
            ;
        }
        $glossaryItems = $qb->getQuery()->getResult();

        $exportFilePath = $this->generateExportFile(
            $glossaryItems,
            $format,
            $course,
            $category,
        );

        $response = new BinaryFileResponse(new File($exportFilePath));
        $response->headers->set('Content-Type', self::MIME_TYPES[$format]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $response->getFile()->getFilename()
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @throws NotSupported|Exception
     */
    private function generateExportFile(
        array $glossaryItems,
        string $format,
        Course $course,
        ?CGlossaryCategory $category,
    ): string {
        if ('pdf' === $format) {
            return $this->generatePdfFile($glossaryItems, $course, $category);
        }

        $list = [];
        $list[] = ['term', 'definition', 'category'];

        $allowStrip = 'true' === $this->settingsManager->getSetting('glossary.allow_remove_tags_in_glossary_export');

        foreach ($glossaryItems as $item) {
            if (!$item instanceof CGlossary) {
                continue;
            }

            $definition = (string) ($item->getDescription() ?? '');

            if ($allowStrip) {
                $definition = htmlspecialchars_decode(strip_tags($definition), ENT_QUOTES);
            }

            $list[] = [$item->getTitle(), $definition, $item->getCategory()?->getTitle() ?? ''];
        }

        return match ($format) {
            'csv' => $this->generateCsvFile($list, $course),
            'xls' => $this->generateExcelFile($list, $course),
        };
    }

    private function generateCsvFile(array $glossaryItems, Course $course): string
    {
        return Export::arrayToCsv($glossaryItems, 'glossary_course_'.$course->getCode(), true);
    }

    private function generateExcelFile(array $glossaryItems, Course $course): string
    {
        return Export::arrayToXls($glossaryItems, 'glossary_course_'.$course->getCode(), true);
    }

    private function generatePdfFile(
        array $glossaryItems,
        Course $course,
        ?CGlossaryCategory $selectedCategory,
    ): string {
        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { font-size: 18px; margin-bottom: 6px; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
            th { background: #f4f4f4; }
        </style>';

        $html .= '<h1>'.$this->translator->trans('Glossary').'</h1>';
        if ($selectedCategory instanceof CGlossaryCategory) {
            $html .= '<h2>'.htmlspecialchars($selectedCategory->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h2>';
        }

        $grouped = [];
        foreach ($glossaryItems as $item) {
            if (!$item instanceof CGlossary) {
                continue;
            }

            $categoryTitle = $item->getCategory()?->getTitle() ?? $this->translator->trans('No category');
            $grouped[$categoryTitle][] = $item;
        }
        uksort($grouped, 'strnatcasecmp');

        foreach ($grouped as $categoryTitle => $items) {
            if (!$selectedCategory instanceof CGlossaryCategory) {
                $html .= '<h2>'.htmlspecialchars($categoryTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h2>';
            }

            $html .= '<table>';
            $html .= '<tr>';
            $html .= '<th>'.$this->translator->trans('Term').'</th>';
            $html .= '<th>'.$this->translator->trans('Term definition').'</th>';
            $html .= '</tr>';

            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td>'.htmlspecialchars($item->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</td>';
                $html .= '<td>'.htmlspecialchars((string) ($item->getDescription() ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        return (new PDF())
            ->content_to_pdf(
                $html,
                null,
                get_lang('Glossary').'_'.$course->getCode(),
                $course->getCode(),
                'F',
                false,
                null,
                false,
                true
            )
        ;
    }
}
