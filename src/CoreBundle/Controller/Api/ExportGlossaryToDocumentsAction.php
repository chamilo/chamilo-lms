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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class ExportGlossaryToDocumentsAction
{
    public function __invoke(
        Request $request,
        CGlossaryRepository $repo,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
    ): Response {
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

        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }

        $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);

        // The alias used by getResourcesByCourse() is "resource" (as seen in GetGlossaryCollectionController).
        $qb->orderBy('resource.title', 'ASC');

        /** @var CGlossary[] $glossaryItems */
        $glossaryItems = $qb->getQuery()->getResult();

        $pdfFilePath = $this->generatePdfFile($glossaryItems, $course, $session, $translator);

        if (empty($pdfFilePath) || !file_exists($pdfFilePath)) {
            throw new BadRequestHttpException('The glossary PDF could not be generated.');
        }

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

        $em->persist($document);
        $em->flush();

        @unlink($pdfFilePath);

        return new JsonResponse([
            'created' => true,
            'title' => $fileName,
        ]);
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
    private function generatePdfFile(
        array $glossaryItems,
        Course $course,
        ?Session $session,
        TranslatorInterface $translator,
    ): string {
        $date = date('Y-m-d');
        $suffix = bin2hex(random_bytes(4));
        $fileBase = 'glossary_'.$date.'_'.$suffix;

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { font-size: 18px; margin-bottom: 6px; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
            th { background: #f4f4f4; }
            .muted { color: #666; font-size: 11px; margin-bottom: 10px; }
        </style>';

        $html .= '<h1>'.$translator->trans('Glossary').'</h1>';

        $meta = $course->getCode();
        if ($session instanceof Session) {
            $meta .= ' / '.$translator->trans('Session').' #'.$session->getId();
        }
        $html .= '<div class="muted">'.htmlspecialchars($meta, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</div>';

        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<th>'.$translator->trans('Term').'</th>';
        $html .= '<th>'.$translator->trans('Term definition').'</th>';
        $html .= '</tr>';

        foreach ($glossaryItems as $item) {
            $term = htmlspecialchars((string) $item->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $def = htmlspecialchars((string) ($item->getDescription() ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html .= '<tr>';
            $html .= '<td>'.$term.'</td>';
            $html .= '<td>'.$def.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return (new PDF())
            ->content_to_pdf(
                $html,
                null,
                $fileBase,
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
