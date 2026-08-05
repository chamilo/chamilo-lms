<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CWikiRepository;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PREG_SPLIT_NO_EMPTY;

final readonly class WikiPageExportService
{
    public function __construct(
        private CWikiRepository $wikiRepository,
        private WikiPageRenderer $renderer,
    ) {}

    /**
     * @param array{cid:int,sid:int,gid:int} $context
     *
     * @return array{title:string,filename:string,content:string,body:string,document:string}
     */
    public function build(
        CWiki $wiki,
        int $nodeId,
        array $context,
        bool $strictFiltering,
        string $baseUrl,
    ): array {
        $title = $this->renderer->displayTitle(
            (string) $wiki->getReflink(),
            (string) $wiki->getTitle(),
        );
        $sanitizedContent = $this->renderer->sanitizeContent(
            (string) $wiki->getContent(),
            $strictFiltering,
        );
        $linkedReflinks = $this->renderer->extractInternalReflinks($sanitizedContent);
        $existingReflinks = $this->wikiRepository->findExistingReflinks(
            $context['cid'],
            $linkedReflinks,
            $context['gid'],
            $context['sid'],
        );
        $content = $this->renderer->renderInternalLinks(
            $sanitizedContent,
            $existingReflinks,
            $nodeId,
            $context,
        );
        $content = $this->absolutizeRootUrls($content, $baseUrl);
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = '<main class="wiki-export">'
            .'<h1>'.$escapedTitle.'</h1>'
            .'<div class="wiki-export-content">'.$content.'</div>'
            .'</main>';
        $css = $this->getPrintCss();
        $document = '<!DOCTYPE html><html lang="en"><head>'
            .'<meta charset="UTF-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.$escapedTitle.'</title>'
            .'<style>'.$css.'</style>'
            .'</head><body>'.$body.'</body></html>';

        return [
            'title' => $title,
            'filename' => $this->buildFilename($title),
            'content' => $content,
            'body' => $body,
            'document' => $document,
        ];
    }

    public function getPrintCss(): string
    {
        return <<<'CSS'
@page { margin: 16mm; }
body {
  margin: 0;
  color: #222;
  font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
  font-size: 12pt;
  line-height: 1.45;
}
.wiki-export h1 {
  margin: 0 0 14px;
  padding-bottom: 8px;
  border-bottom: 1px solid #d9d9d9;
  font-size: 21pt;
  line-height: 1.2;
}
.wiki-export-content {
  overflow-wrap: anywhere;
}
.wiki-export-content img,
.wiki-export-content video,
.wiki-export-content svg {
  max-width: 100%;
  height: auto;
}
.wiki-export-content table {
  width: 100%;
  border-collapse: collapse;
}
.wiki-export-content th,
.wiki-export-content td {
  padding: 5px;
  border: 1px solid #d9d9d9;
  vertical-align: top;
}
.wiki-export-content pre,
.wiki-export-content code {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  font-family: "DejaVu Sans Mono", monospace;
  font-size: 10pt;
}
.wiki-export-content a {
  color: #145f8c;
  text-decoration: underline;
}
CSS;
    }

    public function buildFilename(string $title): string
    {
        $normalized = trim($title);
        $normalized = preg_replace('/[^\p{L}\p{N}._-]+/u', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-_.');

        if ('' === $normalized) {
            return 'wiki-page';
        }

        if (\function_exists('mb_substr')) {
            return mb_substr($normalized, 0, 120);
        }

        return substr($normalized, 0, 120);
    }

    private function absolutizeRootUrls(string $html, string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        if ('' === $baseUrl) {
            return $html;
        }

        $html = preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])(/[^\'"]*)\2~i',
            static fn (array $matches): string => $matches[1].'='.$matches[2]
                .$baseUrl.$matches[3].$matches[2],
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '~\bsrcset\s*=\s*([\'"])(.*?)\1~is',
            static function (array $matches) use ($baseUrl): string {
                $parts = array_map('trim', explode(',', (string) $matches[2]));

                foreach ($parts as &$part) {
                    if ('' === $part) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', $part, -1, PREG_SPLIT_NO_EMPTY);
                    if (!\is_array($tokens) || [] === $tokens) {
                        continue;
                    }

                    if (str_starts_with((string) $tokens[0], '/')) {
                        $tokens[0] = $baseUrl.$tokens[0];
                        $part = implode(' ', $tokens);
                    }
                }
                unset($part);

                return 'srcset='.$matches[1].implode(', ', $parts).$matches[1];
            },
            $html,
        ) ?? $html;
    }
}
