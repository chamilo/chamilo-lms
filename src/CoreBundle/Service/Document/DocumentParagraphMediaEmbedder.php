<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Document;

use Chamilo\CoreBundle\Cache\DocumentListCacheInvalidator;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;
use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;
use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;
use const XML_PI_NODE;

final readonly class DocumentParagraphMediaEmbedder
{
    private const WRAPPER_ID = '__chamilo_mcp_document_fragment__';

    public function __construct(
        private CDocumentRepository $documentRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private EntityManager $entityManager,
        private DocumentListCacheInvalidator $cacheInvalidator,
        private ResourceHelper $resourceHelper,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{
     *     updated: bool,
     *     already_present: bool,
     *     paragraph_number: int,
     *     paragraphs_total: int,
     *     paragraph_preview: string,
     *     media_type: 'image'|'video',
     *     media_url: string
     * }
     */
    public function embed(
        CDocument $document,
        CDocument $mediaDocument,
        int $courseId,
        int $userId,
        ?int $paragraphNumber,
        ?string $paragraphText,
        ?string $altText,
        ?string $caption,
        string $placement,
    ): array {
        $originalHtml = $this->documentRepository->getResourceFileContent($document);
        if ('' === trim($originalHtml)) {
            throw new RuntimeException('The target HTML document is empty.');
        }

        $mediaResourceNode = $mediaDocument->getResourceNode();
        $mediaResourceFile = $mediaResourceNode?->getFirstResourceFile();
        if (!$mediaResourceFile instanceof ResourceFile) {
            throw new RuntimeException('The media document has no stored file.');
        }

        $mimeType = strtolower(trim((string) $mediaResourceFile->getMimeType()));
        $mediaType = $this->resolveMediaType($mimeType);
        $mediaUrl = $this->documentRepository->getResourceFileUrl(
            $mediaDocument,
            ['cid' => $courseId],
        );

        if ('' === trim($mediaUrl)) {
            throw new RuntimeException('The media URL could not be generated.');
        }

        [$dom, $scope, $isFullDocument] = $this->parseHtml($originalHtml);
        $xpath = new DOMXPath($dom);
        $paragraphs = $this->findParagraphs($xpath, $scope);

        if ([] === $paragraphs) {
            throw new InvalidArgumentException('The target document does not contain HTML paragraph elements.');
        }

        [$targetParagraph, $resolvedParagraphNumber] = $this->resolveParagraph(
            $paragraphs,
            $paragraphNumber,
            $paragraphText,
        );

        $paragraphPreview = $this->preview(
            $this->normalizeText($targetParagraph->textContent),
        );

        $mediaDocumentId = (int) ($mediaDocument->getIid() ?? 0);
        if ($mediaDocumentId <= 0) {
            throw new RuntimeException('The media document identifier is invalid.');
        }

        if ($this->hasMediaAlreadyEmbedded($xpath, $scope, $mediaDocumentId)) {
            return [
                'updated' => false,
                'already_present' => true,
                'paragraph_number' => $resolvedParagraphNumber,
                'paragraphs_total' => \count($paragraphs),
                'paragraph_preview' => $paragraphPreview,
                'media_type' => $mediaType,
                'media_url' => $mediaUrl,
            ];
        }

        $figure = $this->buildFigure(
            $dom,
            $mediaDocument,
            $mediaResourceFile,
            $mediaType,
            $mediaUrl,
            $altText,
            $caption,
        );

        $this->insertFigure($targetParagraph, $figure, $placement);

        $updatedHtml = $this->serializeHtml($dom, $scope, $isFullDocument);
        if ('' === trim($updatedHtml)) {
            throw new RuntimeException('The updated HTML document is empty.');
        }

        $this->writeDocument(
            $document,
            $originalHtml,
            $updatedHtml,
        );

        $documentId = (int) ($document->getIid() ?? 0);
        $this->aiDisclosureHelper->markAiAssistedExtraField(
            'document',
            $documentId,
            true,
        );
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.$courseId.':document:'.$documentId.':paragraph_media',
            userId: $userId,
            meta: [
                'feature' => 'document_paragraph_media',
                'mode' => 'embedded_existing_course_media',
                'paragraph_number' => $resolvedParagraphNumber,
                'media_document_id' => $mediaDocumentId,
                'media_type' => $mediaType,
                'placement' => $placement,
            ],
            courseId: $courseId,
        );

        return [
            'updated' => true,
            'already_present' => false,
            'paragraph_number' => $resolvedParagraphNumber,
            'paragraphs_total' => \count($paragraphs),
            'paragraph_preview' => $paragraphPreview,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ];
    }

    /**
     * @return array{0: DOMDocument, 1: DOMElement|null, 2: bool}
     */
    private function parseHtml(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $isFullDocument = (bool) preg_match('/<!doctype\s+html|<html\b/i', $html);

        try {
            if ($isFullDocument) {
                $loaded = $dom->loadHTML(
                    '<?xml encoding="UTF-8">'.$html,
                    LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
                );
                $scope = $dom->getElementsByTagName('body')->item(0);
                if (!$scope instanceof DOMElement) {
                    $scope = null;
                }
            } else {
                $loaded = $dom->loadHTML(
                    '<?xml encoding="UTF-8"><div id="'.self::WRAPPER_ID.'">'.$html.'</div>',
                    LIBXML_NONET
                    | LIBXML_NOERROR
                    | LIBXML_NOWARNING
                    | LIBXML_HTML_NOIMPLIED
                    | LIBXML_HTML_NODEFDTD,
                );

                $xpath = new DOMXPath($dom);
                $scopeNode = $xpath->query('//*[@id="'.self::WRAPPER_ID.'"]')?->item(0);
                $scope = $scopeNode instanceof DOMElement ? $scopeNode : null;
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            throw new InvalidArgumentException('The target document contains invalid HTML.');
        }

        $this->removeXmlProcessingInstructions($dom);

        if (!$isFullDocument && !$scope instanceof DOMElement) {
            throw new RuntimeException('The target HTML fragment could not be prepared for editing.');
        }

        return [$dom, $scope, $isFullDocument];
    }

    /**
     * @return DOMElement[]
     */
    private function findParagraphs(
        DOMXPath $xpath,
        ?DOMElement $scope,
    ): array {
        $nodeList = $scope instanceof DOMElement
            ? $xpath->query('.//p', $scope)
            : $xpath->query('//p');

        if (false === $nodeList) {
            return [];
        }

        $paragraphs = [];
        foreach ($nodeList as $node) {
            if ($node instanceof DOMElement) {
                $paragraphs[] = $node;
            }
        }

        return $paragraphs;
    }

    /**
     * @param DOMElement[] $paragraphs
     *
     * @return array{0: DOMElement, 1: int}
     */
    private function resolveParagraph(
        array $paragraphs,
        ?int $paragraphNumber,
        ?string $paragraphText,
    ): array {
        $paragraphText = null !== $paragraphText
            ? $this->normalizeText($paragraphText)
            : '';

        if (null !== $paragraphNumber) {
            if ($paragraphNumber <= 0 || $paragraphNumber > \count($paragraphs)) {
                throw new InvalidArgumentException(\sprintf('The paragraph number must be between 1 and %d.', \count($paragraphs)));
            }

            $paragraph = $paragraphs[$paragraphNumber - 1];

            if (
                '' !== $paragraphText
                && !str_contains(
                    mb_strtolower($this->normalizeText($paragraph->textContent)),
                    mb_strtolower($paragraphText),
                )
            ) {
                throw new InvalidArgumentException(\sprintf('Paragraph %d does not contain the expected text. Actual paragraph: "%s".', $paragraphNumber, $this->preview($this->normalizeText($paragraph->textContent))));
            }

            return [$paragraph, $paragraphNumber];
        }

        if ('' === $paragraphText) {
            throw new InvalidArgumentException('Provide paragraphNumber, paragraphText, or both.');
        }

        $matches = [];
        $needle = mb_strtolower($paragraphText);

        foreach ($paragraphs as $index => $paragraph) {
            $paragraphContent = mb_strtolower(
                $this->normalizeText($paragraph->textContent),
            );

            if (str_contains($paragraphContent, $needle)) {
                $matches[] = [
                    'paragraph' => $paragraph,
                    'number' => $index + 1,
                ];
            }
        }

        if ([] === $matches) {
            throw new InvalidArgumentException('No paragraph contains the supplied paragraphText.');
        }

        if (\count($matches) > 1) {
            throw new InvalidArgumentException('More than one paragraph contains paragraphText. Provide paragraphNumber to disambiguate.');
        }

        return [
            $matches[0]['paragraph'],
            $matches[0]['number'],
        ];
    }

    private function buildFigure(
        DOMDocument $dom,
        CDocument $mediaDocument,
        ResourceFile $mediaResourceFile,
        string $mediaType,
        string $mediaUrl,
        ?string $altText,
        ?string $caption,
    ): DOMElement {
        $mediaDocumentId = (int) ($mediaDocument->getIid() ?? 0);
        $figure = $dom->createElement('figure');
        $figure->setAttribute('class', 'document-paragraph-media');
        $figure->setAttribute(
            'data-chamilo-mcp-media-document-id',
            (string) $mediaDocumentId,
        );

        $accessibleText = $this->normalizeText(
            null !== $altText && '' !== trim($altText)
                ? $altText
                : $mediaDocument->getTitle(),
        );

        if (mb_strlen($accessibleText) > 500) {
            throw new InvalidArgumentException('The media alternative text cannot be longer than 500 characters.');
        }

        if ('image' === $mediaType) {
            $media = $dom->createElement('img');
            $media->setAttribute('src', $mediaUrl);
            $media->setAttribute('alt', $accessibleText);
            $media->setAttribute('loading', 'lazy');
            $media->setAttribute('decoding', 'async');
        } else {
            $media = $dom->createElement('video');
            $media->setAttribute('controls', 'controls');
            $media->setAttribute('preload', 'metadata');
            $media->setAttribute('aria-label', $accessibleText);

            $source = $dom->createElement('source');
            $source->setAttribute('src', $mediaUrl);
            $source->setAttribute(
                'type',
                (string) $mediaResourceFile->getMimeType(),
            );
            $media->appendChild($source);
            $media->appendChild(
                $dom->createTextNode(
                    'Your browser does not support embedded video.'
                )
            );
        }

        $media->setAttribute(
            'style',
            'display:block;max-width:100%;height:auto;margin:0 auto;',
        );
        $figure->appendChild($media);

        $caption = null !== $caption
            ? $this->normalizeText($caption)
            : '';

        if (mb_strlen($caption) > 1_000) {
            throw new InvalidArgumentException('The media caption cannot be longer than 1000 characters.');
        }

        if ('' !== $caption) {
            $figcaption = $dom->createElement('figcaption');
            $figcaption->appendChild($dom->createTextNode($caption));
            $figure->appendChild($figcaption);
        }

        return $figure;
    }

    private function insertFigure(
        DOMElement $paragraph,
        DOMElement $figure,
        string $placement,
    ): void {
        $parent = $paragraph->parentNode;
        if (!$parent instanceof DOMNode) {
            throw new RuntimeException('The target paragraph cannot receive an illustration.');
        }

        if ('before' === $placement) {
            $parent->insertBefore($figure, $paragraph);

            return;
        }

        $nextSibling = $paragraph->nextSibling;
        if ($nextSibling instanceof DOMNode) {
            $parent->insertBefore($figure, $nextSibling);

            return;
        }

        $parent->appendChild($figure);
    }

    private function hasMediaAlreadyEmbedded(
        DOMXPath $xpath,
        ?DOMElement $scope,
        int $mediaDocumentId,
    ): bool {
        $query = './/*[@data-chamilo-mcp-media-document-id="'
            .$mediaDocumentId
            .'"]';

        $matches = $scope instanceof DOMElement
            ? $xpath->query($query, $scope)
            : $xpath->query(substr($query, 1));

        return false !== $matches && $matches->length > 0;
    }

    private function serializeHtml(
        DOMDocument $dom,
        ?DOMElement $scope,
        bool $isFullDocument,
    ): string {
        if ($isFullDocument) {
            $html = $dom->saveHTML();

            return \is_string($html) ? trim($html) : '';
        }

        if (!$scope instanceof DOMElement) {
            return '';
        }

        $html = '';
        foreach ($scope->childNodes as $childNode) {
            $part = $dom->saveHTML($childNode);
            if (\is_string($part)) {
                $html .= $part;
            }
        }

        return trim($html);
    }

    private function writeDocument(
        CDocument $document,
        string $originalHtml,
        string $updatedHtml,
    ): void {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            throw new RuntimeException('The target document resource node is missing.');
        }

        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            throw new RuntimeException('The target document resource file is missing.');
        }

        $filename = $this->resourceNodeRepository->getFilename($resourceFile);
        if (!\is_string($filename) || '' === trim($filename)) {
            throw new RuntimeException('The target document storage path is missing.');
        }

        $filesystem = $this->resourceNodeRepository->getFileSystem();

        try {
            $this->entityManager->wrapInTransaction(
                function () use (
                    $document,
                    $resourceNode,
                    $resourceFile,
                    $filesystem,
                    $filename,
                    $updatedHtml,
                ): void {
                    $filesystem->write($filename, $updatedHtml);

                    $resourceNode->setContent($updatedHtml);
                    $resourceNode->setUpdatedAt(new DateTime());
                    $resourceFile
                        ->setSize(\strlen($updatedHtml))
                        ->setMimeType('text/html')
                    ;

                    $this->entityManager->persist($document);
                    $this->entityManager->persist($resourceNode);
                    $this->entityManager->persist($resourceFile);
                }
            );
        } catch (Throwable $throwable) {
            try {
                $filesystem->write($filename, $originalHtml);
            } catch (Throwable) {
                // Keep the original exception. The log will contain the storage failure.
            }

            throw new RuntimeException('The illustrated document could not be stored.', 0, $throwable);
        }

        $this->cacheInvalidator->invalidate();

        try {
            $this->resourceHelper->createAndSaveResourceEvent(
                $resourceNode,
                'edition',
            );
        } catch (Throwable) {
            // Tracking must not turn a completed document update into a tool failure.
        }
    }

    private function resolveMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        throw new InvalidArgumentException('The media document must contain an image or video file.');
    }

    private function removeXmlProcessingInstructions(
        DOMDocument $dom,
    ): void {
        $nodesToRemove = [];
        foreach ($dom->childNodes as $childNode) {
            if (XML_PI_NODE === $childNode->nodeType) {
                $nodesToRemove[] = $childNode;
            }
        }

        foreach ($nodesToRemove as $childNode) {
            $dom->removeChild($childNode);
        }
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode(
            strip_tags($text),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function preview(string $text): string
    {
        if (mb_strlen($text) <= 180) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 177)).'...';
    }
}
