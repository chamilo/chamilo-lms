<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Doctrine\DBAL\Types\Types;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

final readonly class LearningPathContentPdfService
{
    private const HTML_EXTENSIONS = ['htm', 'html', 'xht', 'xhtml'];
    private const IMAGE_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png', 'webp'];
    private const SUPPORTED_ITEM_TYPES = ['asset', 'dir', 'document', 'sco'];

    public function __construct(
        private CLpItemRepository $lpItemRepository,
        private CDocumentRepository $documentRepository,
        private AssetRepository $assetRepository,
        private ScormRuntimeManager $scormRuntimeManager,
        private Security $security,
    ) {}

    /**
     * @return list<array{id: int, title: string, type: string, level: int}>
     */
    public function getExportableItems(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $rows = [];

        foreach ($this->getOrderedItems($lp) as $item) {
            if (!$this->canResolveItem($lp, $item, $course, $session, $group)) {
                continue;
            }

            $rows[] = [
                'id' => (int) $item->getIid(),
                'title' => $this->plainText($item->getTitle()),
                'type' => $item->getItemType(),
                'level' => max(0, (int) $item->getLvl()),
            ];
        }

        return $rows;
    }

    /**
     * @param list<int> $selectedItemIds
     */
    public function buildHtml(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $selectedItemIds,
    ): string {
        $selected = [];
        foreach ($selectedItemIds as $itemId) {
            if ($itemId > 0) {
                $selected[$itemId] = true;
            }
        }

        $blocks = [];
        foreach ($this->getOrderedItems($lp) as $item) {
            $itemId = (int) $item->getIid();
            if ([] !== $selected && !isset($selected[$itemId])) {
                continue;
            }

            $block = $this->resolveItemBlock($lp, $item, $course, $session, $group);
            if (null !== $block) {
                $blocks[] = $block;
            }
        }

        if ([] === $blocks) {
            return '';
        }

        $html = '<style>
            @page { margin: 15mm; }
            body { color: #202124; font-family: sans-serif; font-size: 11pt; line-height: 1.45; }
            h1 { color: #1f2937; font-size: 22pt; margin: 0 0 8mm; text-align: center; }
            h2 { color: #1f2937; font-size: 16pt; margin: 0 0 5mm; }
            h3 { color: #374151; font-size: 13pt; margin: 0 0 4mm; }
            p { margin: 0 0 3mm; }
            img { height: auto; max-width: 100%; }
            table { border-collapse: collapse; max-width: 100%; width: 100%; }
            th, td { border: 1px solid #9ca3af; padding: 5px; vertical-align: top; }
            pre, code { font-family: monospace; white-space: pre-wrap; word-break: break-word; }
            .lp-section { margin-top: 6mm; }
            .lp-item { page-break-before: always; }
            .lp-item:first-of-type { page-break-before: auto; }
            .lp-item-content { overflow-wrap: anywhere; }
        </style>';
        $html .= '<h1>'.$this->escape($this->plainText($lp->getTitle())).'</h1>';

        foreach ($blocks as $block) {
            $class = 'heading' === $block['kind'] ? 'lp-section' : 'lp-item';
            $html .= '<section class="'.$class.'">';
            $html .= '<h2>'.$this->escape($block['title']).'</h2>';
            if ('' !== $block['content']) {
                $html .= '<div class="lp-item-content">'.$block['content'].'</div>';
            }
            $html .= '</section>';
        }

        return $html;
    }

    /** @return list<CLpItem> */
    private function getOrderedItems(CLp $lp): array
    {
        /** @var list<CLpItem> $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType != :rootType')
            ->andWhere('item.exportAllowed = :exportAllowed')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->setParameter('exportAllowed', true, Types::BOOLEAN)
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $items;
    }

    private function canResolveItem(
        CLp $lp,
        CLpItem $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        if (!\in_array(strtolower($item->getItemType()), self::SUPPORTED_ITEM_TYPES, true)) {
            return false;
        }

        if ('dir' === strtolower($item->getItemType())) {
            return true;
        }

        return null !== $this->resolveItemBlock($lp, $item, $course, $session, $group);
    }

    /** @return array{kind: string, title: string, content: string}|null */
    private function resolveItemBlock(
        CLp $lp,
        CLpItem $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?array {
        $type = strtolower(trim($item->getItemType()));
        if (!\in_array($type, self::SUPPORTED_ITEM_TYPES, true)) {
            return null;
        }

        $title = $this->plainText($item->getTitle());
        if ('dir' === $type) {
            return [
                'kind' => 'heading',
                'title' => $title,
                'content' => '',
            ];
        }

        if ('document' === $type) {
            return $this->resolveDocumentBlock($item, $course, $session, $group, $title);
        }

        return $this->resolveScormBlock($lp, $item, $title);
    }

    /** @return array{kind: string, title: string, content: string}|null */
    private function resolveDocumentBlock(
        CLpItem $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $title,
    ): ?array {
        $documentId = filter_var($item->getPath(), FILTER_VALIDATE_INT);
        if (false === $documentId || $documentId <= 0) {
            return null;
        }

        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof CDocument || !$this->resourceIsAvailable($document, $course, $session, $group)) {
            return null;
        }

        try {
            $content = $this->documentRepository->getResourceFileContent($document);
        } catch (Throwable) {
            return null;
        }

        if ('' === $content) {
            return null;
        }

        $resourceFile = $document->getResourceNode()?->getResourceFiles()->first();
        $mimeType = $resourceFile instanceof ResourceFile ? strtolower((string) $resourceFile->getMimeType()) : '';
        $extension = $resourceFile instanceof ResourceFile
            ? strtolower((string) pathinfo((string) $resourceFile->getOriginalName(), PATHINFO_EXTENSION))
            : '';

        if (str_starts_with($mimeType, 'image/') || \in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            $resolvedMimeType = '' !== $mimeType ? $mimeType : $this->guessMimeType($extension);

            return [
                'kind' => 'content',
                'title' => $title,
                'content' => '<p><img alt="'.$this->escape($title).'" src="data:'.$resolvedMimeType.';base64,'.base64_encode($content).'"></p>',
            ];
        }

        if (!str_contains($mimeType, 'html')
            && !str_starts_with($mimeType, 'text/')
            && !\in_array($extension, self::HTML_EXTENSIONS, true)
            && '<' !== substr(ltrim($content), 0, 1)
        ) {
            return null;
        }

        return [
            'kind' => 'content',
            'title' => $title,
            'content' => $this->sanitizeHtml($content),
        ];
    }

    /** @return array{kind: string, title: string, content: string}|null */
    private function resolveScormBlock(CLp $lp, CLpItem $item, string $title): ?array
    {
        if (CLp::SCORM_TYPE !== $lp->getLpType()) {
            return null;
        }

        $relativePath = trim((string) $item->getPath());
        if ('' === $relativePath || 1 === preg_match('#^https?://#i', $relativePath)) {
            return null;
        }

        try {
            $filePath = $this->scormRuntimeManager->resolveAssetFilePath($lp, $relativePath);
            $filesystem = $this->assetRepository->getFileSystem();
            if (!$filesystem->fileExists($filePath)) {
                return null;
            }

            $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
            $content = $filesystem->read($filePath);

            if (\in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                return [
                    'kind' => 'content',
                    'title' => $title,
                    'content' => '<p><img alt="'.$this->escape($title).'" src="data:'.$this->guessMimeType($extension).';base64,'.base64_encode($content).'"></p>',
                ];
            }

            if (!\in_array($extension, self::HTML_EXTENSIONS, true)) {
                return null;
            }

            return [
                'kind' => 'content',
                'title' => $title,
                'content' => $this->sanitizeHtml(
                    $this->inlineScormImages($lp, $relativePath, $content),
                ),
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function resourceIsAvailable(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): bool {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            return false;
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if ($resourceLink instanceof ResourceLink) {
            return true;
        }

        return null !== $session
            && null === $group
            && $resourceNode->getResourceLinkByContext($course) instanceof ResourceLink;
    }

    private function inlineScormImages(CLp $lp, string $htmlPath, string $html): string
    {
        $dom = $this->loadHtml($html);
        if (!$dom instanceof DOMDocument) {
            return $html;
        }

        $directory = trim(str_replace('\\', '/', dirname($htmlPath)), '.');
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            if (!$image instanceof DOMElement) {
                continue;
            }

            $source = trim($image->getAttribute('src'));
            if ('' === $source
                || str_starts_with($source, 'data:image/')
                || 1 === preg_match('#^(?:https?:)?//#i', $source)
                || str_starts_with($source, '/')
            ) {
                continue;
            }

            $pathOnly = (string) parse_url($source, PHP_URL_PATH);
            if ('' === $pathOnly) {
                continue;
            }

            $relative = $this->normalizeRelativePath(('' !== $directory ? $directory.'/' : '').$pathOnly);
            if ('' === $relative) {
                $image->removeAttribute('src');
                continue;
            }

            try {
                $assetPath = $this->scormRuntimeManager->resolveAssetFilePath($lp, $relative);
                $filesystem = $this->assetRepository->getFileSystem();
                if (!$filesystem->fileExists($assetPath)) {
                    $image->removeAttribute('src');
                    continue;
                }

                $extension = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));
                if (!\in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                    $image->removeAttribute('src');
                    continue;
                }

                $image->setAttribute(
                    'src',
                    'data:'.$this->guessMimeType($extension).';base64,'.base64_encode($filesystem->read($assetPath)),
                );
            } catch (Throwable) {
                $image->removeAttribute('src');
            }
        }

        return $this->innerHtml($dom);
    }

    private function sanitizeHtml(string $html): string
    {
        $dom = $this->loadHtml($html);
        if (!$dom instanceof DOMDocument) {
            return strip_tags($html, '<p><br><strong><b><em><i><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><table><thead><tbody><tr><th><td><blockquote><pre><code><img><a><span><div>');
        }

        $xpath = new DOMXPath($dom);
        $blockedNodes = $xpath->query('//script|//style|//iframe|//object|//embed|//form|//input|//button|//select|//textarea|//video|//audio|//canvas|//svg');
        if (false !== $blockedNodes) {
            $nodes = [];
            foreach ($blockedNodes as $blockedNode) {
                $nodes[] = $blockedNode;
            }
            foreach ($nodes as $blockedNode) {
                $blockedNode->parentNode?->removeChild($blockedNode);
            }
        }

        $elements = $xpath->query('//*');
        if (false !== $elements) {
            foreach ($elements as $element) {
                if (!$element instanceof DOMElement) {
                    continue;
                }

                $attributesToRemove = [];
                foreach ($element->attributes as $attribute) {
                    $name = strtolower($attribute->name);
                    $value = trim($attribute->value);
                    if (str_starts_with($name, 'on') || \in_array($name, ['srcdoc', 'formaction'], true)) {
                        $attributesToRemove[] = $attribute->name;
                        continue;
                    }
                    if ('style' === $name && 1 === preg_match('/(?:expression|javascript:|url\s*\()/i', $value)) {
                        $attributesToRemove[] = $attribute->name;
                        continue;
                    }
                    if (\in_array($name, ['href', 'src'], true) && !$this->isSafeUri($value, 'src' === $name)) {
                        $attributesToRemove[] = $attribute->name;
                    }
                }

                foreach ($attributesToRemove as $attributeName) {
                    $element->removeAttribute($attributeName);
                }
            }
        }

        return $this->innerHtml($dom);
    }

    private function isSafeUri(string $uri, bool $image): bool
    {
        if ('' === $uri || str_starts_with($uri, '#')) {
            return true;
        }
        if ($image && str_starts_with(strtolower($uri), 'data:image/')) {
            return true;
        }
        if (str_starts_with($uri, '/')) {
            return true;
        }

        return 1 === preg_match($image ? '#^https?://#i' : '#^(?:https?|mailto):#i', $uri);
    }

    private function loadHtml(string $html): ?DOMDocument
    {
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html) ?? $html;
        if (1 === preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="lp-pdf-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    private function innerHtml(DOMDocument $dom): string
    {
        $xpath = new DOMXPath($dom);
        $rootNodes = $xpath->query('//*[@id="lp-pdf-root"]');
        $root = false !== $rootNodes ? $rootNodes->item(0) : null;
        if (!$root instanceof DOMElement) {
            return '';
        }

        $html = '';
        foreach ($root->childNodes as $childNode) {
            $html .= $dom->saveHTML($childNode);
        }

        return $html;
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', rawurldecode($path));
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function guessMimeType(string $extension): string
    {
        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream';
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
