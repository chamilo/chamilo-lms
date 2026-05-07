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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final class CreateDocumentFileAction extends BaseResourceFileAction
{
    private const ALLOWED_CLOUD_LINK_HOSTS = [
        'asuswebstorage.com',
        'box.com',
        'dropbox.com',
        'dropboxusercontent.com',
        'docs.google.com',
        'drive.google.com',
        'fileserve.com',
        'icloud.com',
        'livefilestore.com',
        'mediafire.com',
        'mega.nz',
        'onedrive.live.com',
        'scribd.com',
        'slideshare.net',
        'sharepoint.com',
        'wetransfer.com',
    ];

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

        $filetype = (string) ($result['filetype'] ?? 'file');
        $comment = (string) ($result['comment'] ?? '');

        if ('link' === $filetype) {
            $comment = $this->normalizeCloudLinkUrl($comment, $translator);
        }

        $document->setTitle($result['title'] ?? $document->getResourceName());
        $document->setFiletype($filetype);
        $document->setComment($comment);

        // We need the iid to write ExtraFieldValue, so we persist+flush here.
        $em->persist($document);
        $em->flush();

        // Mark ExtraField: type=document, variable=ai_assisted, item_id=document iid.
        if ($isAiAssisted && $aiDisclosureHelper->isDisclosureEnabled()) {
            try {
                $docId = (int) ($document->getIid() ?? 0);
                if ($docId > 0) {
                    $aiDisclosureHelper->markAiAssistedExtraField('document', $docId, true);
                }
            } catch (Throwable) {
                // Never block the upload flow because of AI marking.
            }
        }

        return $document;
    }

    private function normalizeCloudLinkUrl(string $url, TranslatorInterface $translator): string
    {
        $url = trim($url);

        if ('' === $url) {
            throw new BadRequestHttpException($translator->trans('The URL is required.'));
        }

        $parts = parse_url($url);

        if (!\is_array($parts)) {
            throw new BadRequestHttpException($translator->trans('Invalid URL.'));
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new BadRequestHttpException($translator->trans('Only HTTP and HTTPS URLs are allowed.'));
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            throw new BadRequestHttpException($translator->trans('Invalid URL host.'));
        }

        if (!$this->isAllowedCloudLinkHost($host)) {
            throw new BadRequestHttpException($translator->trans('This cloud provider is not allowed.'));
        }

        return $url;
    }

    private function isAllowedCloudLinkHost(string $host): bool
    {
        foreach (self::ALLOWED_CLOUD_LINK_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }
}
