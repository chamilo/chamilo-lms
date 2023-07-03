<?php
declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportGlossaryToDocumentsAction
{

    public function __invoke(Request $request, CGlossaryRepository $repo, EntityManager $em, KernelInterface $kernel, TranslatorInterface $translator): String
    {

        $data = json_decode($request->getContent(), true);
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);

        $exportPath = $kernel->getCacheDir();
        $glossaryItems = $repo->findAll();

        $pdfFilePath = $this->generatePdfFile($glossaryItems, $exportPath, $translator);

        if ($pdfFilePath) {
            $fileName = basename($pdfFilePath);
            $uploadFile = new UploadedFile(
                $pdfFilePath,
                $fileName
            );

            $document = new CDocument();
            $document->setTitle($fileName);
            $document->setUploadFile($uploadFile);
            $document->setFiletype('file');

            if (!empty($parentResourceNodeId)) {
                $document->setParentResourceNode($parentResourceNodeId);
            }

            if (!empty($resourceLinkList)) {
                $document->setResourceLinkArray($resourceLinkList);
            }

            // Save the CDocument entity to the database
            $em->persist($document);
            $em->flush();

            unlink($pdfFilePath);
        }

        return $pdfFilePath;
    }

    private function generatePdfFile(array $glossaryItems, string $exportPath, TranslatorInterface $translator): string
    {

        $date = date('Y-m-d');
        $pdfFileName = 'glossary_' . $date . '.pdf';
        $pdfFilePath = $exportPath . '/' . $pdfFileName;

        $mpdf = new Mpdf();

        $html = '<h1>'.$translator->trans('Glossary').'</h1>';
        $html .= '<table>';
        $html .= '<tr><th>'.$translator->trans('Term').'</th><th>'.$translator->trans('Definition').'</th></tr>';
        /* @var CGlossary $item */
        foreach ($glossaryItems as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $item->getName(). '</td>';
            $html .= '<td>' . $item->getDescription() . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $mpdf->WriteHTML($html);

        $mpdf->Output($pdfFilePath, 'F');

        return $pdfFilePath;
    }
}
