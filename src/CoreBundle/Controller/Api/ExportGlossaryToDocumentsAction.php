<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PDF;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class ExportGlossaryToDocumentsAction
{
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        EntityManagerInterface $em,
        KernelInterface $kernel,
        TranslatorInterface $translator
    ): string {
        $data = json_decode((string) $request->getContent(), true) ?: [];

        $parentResourceNodeId = (int) ($data['parentResourceNodeId'] ?? 0);

        // The frontend may send resourceLinkList as a JSON string or as an array.
        $resourceLinkListRaw = $data['resourceLinkList'] ?? [];
        if (\is_string($resourceLinkListRaw)) {
            $resourceLinkListRaw = json_decode($resourceLinkListRaw, true) ?: [];
        }

        $resourceLinkList = $this->normalizeResourceLinks($resourceLinkListRaw);

        // Resolve context from resource links.
        $cid = (int) ($resourceLinkList[0]['cid'] ?? 0);
        $sid = (int) ($resourceLinkList[0]['sid'] ?? 0);

        $course = null;
        $session = null;

        if ($cid > 0) {
            $course = $em->getRepository(Course::class)->find($cid);
        }
        if ($sid > 0) {
            $session = $em->getRepository(Session::class)->find($sid);
        }

        // Important: export only glossary items for the current course/session context.
        if ($course) {
            $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);

            // The alias used by getResourcesByCourse() is "resource" (as seen in GetGlossaryCollectionController).
            $qb->orderBy('resource.title', 'ASC');

            /** @var CGlossary[] $glossaryItems */
            $glossaryItems = $qb->getQuery()->getResult();
        } else {
            // Fallback to keep behavior resilient if cid is missing.
            /** @var CGlossary[] $glossaryItems */
            $glossaryItems = $repo->findAll();
        }

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
     * @return array<int, array{cid:int,sid:int,gid:int,visibility:int}>
     */
    private function normalizeResourceLinks(mixed $links): array
    {
        if (!\is_array($links)) {
            return [];
        }

        $normalized = [];

        foreach ($links as $link) {
            if (!\is_array($link)) {
                continue;
            }

            $normalized[] = [
                'cid' => (int) ($link['cid'] ?? 0),
                'sid' => (int) ($link['sid'] ?? 0),
                'gid' => (int) ($link['gid'] ?? 0),
                'visibility' => (int) ($link['visibility'] ?? 0),
            ];
        }

        return $normalized;
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
