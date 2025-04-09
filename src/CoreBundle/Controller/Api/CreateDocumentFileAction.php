<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateDocumentFileAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CDocumentRepository $repo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator
    ): CDocument {
        $isUncompressZipEnabled = $request->get('isUncompressZipEnabled', 'false');
        $fileExistsOption = $request->get('fileExistsOption', 'rename');

        $document = new CDocument();

        if ('true' === $isUncompressZipEnabled) {
            $result = $this->handleCreateFileRequestUncompress($document, $request, $em, $kernel);
        } else {
            $result = $this->handleCreateFileRequest($document, $repo, $request, $em, $fileExistsOption, $translator);
        }

        $document->setTitle($result['title']);
        $document->setFiletype($result['filetype']);
        $document->setComment($result['comment']);

        return $document;
    }
}
