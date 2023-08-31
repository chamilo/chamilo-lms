<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportCGlossaryAction
{
    public function __invoke(Request $request, CGlossaryRepository $repo, EntityManager $em, KernelInterface $kernel, TranslatorInterface $translator): Response
    {
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
            $course = $em->getRepository(Course::class)->find($cid);
        }
        if (0 !== $sid) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        $qb = $repo->getResourcesByCourse($course, $session);
        $glossaryItems = $qb->getQuery()->getResult();

        $exportFilePath = $this->generateExportFile($glossaryItems, $format, $exportPath, $translator);

        $file = new File($exportFilePath);
        $response = new Response($file->getContent());
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->headers->set('Content-Disposition', 'attachment; filename="glossary.'.$format.'"');

        unlink($exportFilePath);

        return $response;
    }

    private function generateExportFile(array $glossaryItems, string $format, string $exportPath, TranslatorInterface $translator): string
    {
        switch ($format) {
            case 'csv':
                return $this->generateCsvFile($glossaryItems, $exportPath);
            case 'xls':
                return $this->generateExcelFile($glossaryItems, $exportPath);
            case 'pdf':
                return $this->generatePdfFile($glossaryItems, $exportPath, $translator);
            default:
                throw new NotSupported('Export format not supported');
        }
    }

    private function generateCsvFile(array $glossaryItems, string $exportPath): string
    {
        $csvFilePath = $exportPath.'/glossary.csv';
        $csvContent = '';
        /** @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $csvContent .= $item->getName().','.$item->getDescription()."\n";
        }
        file_put_contents($csvFilePath, $csvContent);

        return $csvFilePath;
    }

    private function generateExcelFile(array $glossaryItems, string $exportPath): string
    {
        $excelFilePath = $exportPath.'/glossary.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        /** @var CGlossary $item */
        foreach ($glossaryItems as $index => $item) {
            $row = $index + 1;
            $sheet->setCellValue('A'.$row, $item->getName());
            $sheet->setCellValue('B'.$row, $item->getDescription());
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($excelFilePath);

        return $excelFilePath;
    }

    private function generatePdfFile(array $glossaryItems, string $exportPath, TranslatorInterface $translator): string
    {
        $pdfFilePath = $exportPath.'/glossary.pdf';

        $mpdf = new Mpdf();

        $html = '<h1>'.$translator->trans('Glossary').'</h1>';
        $html .= '<table>';
        $html .= '<tr><th>'.$translator->trans('Term').'</th><th>'.$translator->trans('Definition').'</th></tr>';
        /** @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $html .= '<tr>';
            $html .= '<td>'.$item->getName().'</td>';
            $html .= '<td>'.$item->getDescription().'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $mpdf->WriteHTML($html);

        $mpdf->Output($pdfFilePath, 'F');

        return $pdfFilePath;
    }
}
