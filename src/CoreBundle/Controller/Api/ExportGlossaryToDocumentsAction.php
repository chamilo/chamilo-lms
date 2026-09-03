<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDF;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class ExportGlossaryToDocumentsAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        EntityManagerInterface $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        CidReqHelper $cidReqHelper
    ): string {
        $data = json_decode((string) $request->getContent(), true) ?: [];

        $parentResourceNodeId = (int) ($data['parentResourceNodeId'] ?? 0);

        // The frontend may send resourceLinkList as a JSON string or as an array.
        $resourceLinkListRaw = $data['resourceLinkList'] ?? [];
        if (\is_string($resourceLinkListRaw)) {
            $resourceLinkListRaw = json_decode($resourceLinkListRaw, true) ?: [];
        }

        $resourceLinkList = $this->buildResourceLinkListFromContext(
            $cidReqHelper,
            \is_array($resourceLinkListRaw) ? $resourceLinkListRaw : [],
        );

        // The course and session come from the context that authorized the request, never from the body.
        $course = $cidReqHelper->getDoctrineCourseEntity()
            ?? throw new BadRequestHttpException('A valid course id is required.');
        $session = $cidReqHelper->getDoctrineSessionEntity();

        if ($parentResourceNodeId > 0) {
            $this->assertParentNodeIsInCurrentCourse($cidReqHelper, $parentResourceNodeId, $em);
        }

        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);

        // The alias used by getResourcesByCourse() is "resource" (as seen in GetGlossaryCollectionController).
        $qb->orderBy('resource.title', 'ASC');

        /** @var CGlossary[] $glossaryItems */
        $glossaryItems = $qb->getQuery()->getResult();

        $exportPath = $kernel->getCacheDir();
        $pdfFilePath = $this->generatePdfFile($glossaryItems, $exportPath, $translator);

        if (!empty($pdfFilePath) && file_exists($pdfFilePath)) {
            $fileName = basename($pdfFilePath);

            // Important: mark as "test" because this file is generated on the server (not uploaded by HTTP).
            $uploadFile = new UploadedFile(
                $pdfFilePath,
                $fileName,
                'application/pdf',
                null,
                true
            );

            $document = new CDocument();
            $document->setTitle($fileName);
            $document->setUploadFile($uploadFile);
            $document->setFiletype('file');

            if ($parentResourceNodeId > 0) {
                $document->setParentResourceNode($parentResourceNodeId);
            }

            if (!empty($resourceLinkList)) {
                $document->setResourceLinkArray($resourceLinkList);
            }

            // Save the CDocument entity to the database
            $em->persist($document);
            $em->flush();

            @unlink($pdfFilePath);
        }

        return $pdfFilePath;
    }

    /**
     * @param CGlossary[] $glossaryItems
     */
    private function generatePdfFile(array $glossaryItems, string $exportPath, TranslatorInterface $translator): string
    {
        $date = date('Y-m-d');
        $suffix = bin2hex(random_bytes(4));
        $pdfFileName = 'glossary_'.$date.'_'.$suffix.'.pdf';
        $pdfFilePath = rtrim($exportPath, '/').'/'.$pdfFileName;

        $mpdf = new PDF();

        $html = '<h1>'.$translator->trans('Glossary').'</h1>';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
        $html .= '<tr><th>'.$translator->trans('Term').'</th><th>'.$translator->trans('Term definition').'</th></tr>';

        foreach ($glossaryItems as $item) {
            $term = htmlspecialchars((string) $item->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $def = htmlspecialchars((string) ($item->getDescription() ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html .= '<tr>';
            $html .= '<td>'.$term.'</td>';
            $html .= '<td>'.$def.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $mpdf->pdf->WriteHTML($html);

        $mpdf->pdf->Output($pdfFilePath, 'F');

        return $pdfFilePath;
    }
}
