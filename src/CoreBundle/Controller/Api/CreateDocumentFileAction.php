<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CDocumentRepository $repo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        CourseRepository $courseRepository,
        CourseHelper $courseHelper,
        AiDisclosureHelper $aiDisclosureHelper,
    ): CDocument {
        $isUncompressZipEnabled = (string) $request->get('isUncompressZipEnabled', 'false');
        $fileExistsOption = (string) $request->get('fileExistsOption', 'rename');
        $aiAssistedRaw = strtolower(trim((string) $request->get('ai_assisted', '')));
        $isAiAssisted = \in_array($aiAssistedRaw, ['1', 'true', 'yes', 'on'], true);

        $document = new CDocument();

        if ('true' === $isUncompressZipEnabled) {
            $result = $this->handleCreateFileRequestUncompress(
                $document,
                $request,
                $em,
                $kernel,
                $courseRepository,
                $repo,
                $courseHelper
            );
        } else {
            $result = $this->handleCreateFileRequest(
                $document,
                $repo,
                $request,
                $em,
                $fileExistsOption,
                $translator,
                $courseRepository,
                $courseHelper
            );
        }

        $document->setTitle($result['title'] ?? $document->getResourceName());
        $document->setFiletype($result['filetype'] ?? 'file');
        $document->setComment($result['comment'] ?? '');

        // We need the iid to write ExtraFieldValue, so we persist+flush here.
        $em->persist($document);
        $em->flush();

        // Mark ExtraField: type=document, variable=ai_assisted, item_id=document iid
        if ($isAiAssisted && $aiDisclosureHelper->isDisclosureEnabled()) {
            try {
                $docId = (int) ($document->getIid() ?? 0);
                if ($docId > 0) {
                    $aiDisclosureHelper->markAiAssistedExtraField('document', $docId, true);
                }
            } catch (\Throwable) {
                // Never block the upload flow because of AI marking.
            }
        }

        return $document;
    }
}
