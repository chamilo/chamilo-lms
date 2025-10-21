<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use PDF;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class ExportCGlossaryAction
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * @throws Exception|NotSupported|ORMException|OptimisticLockException|TransactionRequiredException
     */
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator
    ): Response {
        $format = $request->get('format');
        $cid = $request->request->get('cid');
        $sid = $request->request->get('sid');

        if (!\in_array($format, ['csv', 'xls', 'pdf'], true)) {
            throw new BadRequestHttpException('Invalid export format');
        }

        $exportPath = $kernel->getCacheDir();
        $course = null;
        $session = null;
        if (0 !== $cid) {
            $course = $em->find(Course::class, $cid);
        }
        if (0 !== $sid) {
            $session = $em->find(Session::class, $sid);
        }

        $qb = $repo->getResourcesByCourse($course, $session);
        $glossaryItems = $qb->getQuery()->getResult();

        $exportFilePath = $this->generateExportFile(
            $glossaryItems,
            $format,
            $exportPath,
            $course,
            $session
        );

        $response = new BinaryFileResponse(
            new File($exportFilePath)
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $response->getFile()->getFilename()
        );

        return $response;
    }

    /**
     * @throws NotSupported|Exception
     */
    private function generateExportFile(
        array $glossaryItems,
        string $format,
        string $exportPath,
        ?Course $course,
        ?Session $session = null,
    ): string {
        return match ($format) {
            'csv' => $this->generateCsvFile($glossaryItems, $exportPath),
            'xls' => $this->generateExcelFile($glossaryItems, $exportPath),
            'pdf' => $this->generatePdfFile($glossaryItems, $course, $session),
            default => throw new NotSupported('Export format not supported'),
        };
    }

    private function generateCsvFile(array $glossaryItems, string $exportPath): string
    {
        $csvFilePath = $exportPath.'/glossary.csv';
        $csvContent = '';

        /** @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $csvContent .= $item->getTitle().','.$item->getDescription()."\n";
        }
        file_put_contents($csvFilePath, $csvContent);

        return $csvFilePath;
    }

    /**
     * @throws Exception
     */
    private function generateExcelFile(array $glossaryItems, string $exportPath): string
    {
        $excelFilePath = $exportPath.'/glossary.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /** @var CGlossary $item */
        foreach ($glossaryItems as $index => $item) {
            $row = $index + 1;
            $sheet->setCellValue('A'.$row, $item->getTitle());
            $sheet->setCellValue('B'.$row, $item->getDescription());
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($excelFilePath);

        return $excelFilePath;
    }

    private function generatePdfFile(
        array $glossaryItems,
        ?Course $course,
        ?Session $session = null,
    ): string
    {
        $html = '<h1>'.$this->translator->trans('Glossary').'</h1>';
        $html .= '<table>';
        $html .= '<tr><th>'.$this->translator->trans('Term').'</th><th>'.$this->translator->trans('Term definition').'</th></tr>';

        /** @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $html .= '<tr>';
            $html .= '<td>'.$item->getTitle().'</td>';
            $html .= '<td>'.$item->getDescription().'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

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
            );
    }
}
