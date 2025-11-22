<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251022005000 extends AbstractMigrationChamilo
{
    private const DEBUG = true;

    /**
     * @var array<string,true>
     */
    private array $globalNeeded = [];

    public function getDescription(): string
    {
        return 'Rewrite legacy image paths in system templates to public /img paths.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $rows = $conn->fetchAllAssociative(
            "SELECT id, title, content
               FROM system_template
              WHERE content LIKE '%/main/img/%'
                 OR content LIKE '%{REL_PATH}main/img/%'
                 OR content LIKE '%{IMG_DIR}%'
                 OR content LIKE '%{COURSE_DIR}images/%'
                 OR content LIKE '%/img/certificates/%'"
        );

        if (empty($rows)) {
            $this->dbg('[MIG][templates] No legacy paths found. Nothing to do.');

            return;
        }

        $updated = 0;

        foreach ($rows as $r) {
            $id = (int) $r['id'];
            $title = (string) $r['title'];
            $content = (string) $r['content'];

            $newContent = $this->rewriteLegacyPathsToPublic($content, $title, $id);

            $needed = $this->extractPublicImagePaths($newContent);
            $this->logNeededImages($id, $title, $needed);

            if ($newContent !== $content) {
                $conn->update('system_template', ['content' => $newContent], ['id' => $id]);
                $updated++;
                $this->dbg(\sprintf('[MIG][templates] Updated template id=%d title="%s".', $id, $title));
            }
        }

        $this->dbg('--- [MIG][templates:images] GLOBAL REQUIRED IMAGES ---');
        $publicBase = $this->resolvePublicBaseDir();
        foreach (array_keys($this->globalNeeded) as $relPath) {
            $status = $this->fileStatus($publicBase, $relPath);
            $this->dbg(\sprintf('[MIG][templates:images] %s  %s', $status, $relPath));
        }
        $this->dbg(\sprintf('[MIG][templates] DONE. Updated %d template(s).', $updated));
    }

    /**
     * Rewrite legacy patterns to /img/...
     */
    private function rewriteLegacyPathsToPublic(string $html, string $title, int $id): string
    {
        $countAll = 0;

        // {IMG_DIR}filename.ext -> /img/filename.ext
        $html = preg_replace(
            '#(\burl\(\s*[\'"]?)\{IMG_DIR\}#i',
            '$1/img/',
            $html,
            -1,
            $c1
        );
        $countAll += (int) $c1;

        $html = preg_replace(
            '#(\bsrc=\s*[\'"])\{IMG_DIR\}#i',
            '$1/img/',
            $html,
            -1,
            $c1b
        );
        $countAll += (int) $c1b;

        // {REL_PATH}main/img/... -> /img/...
        $html = preg_replace(
            '#(\burl\(\s*[\'"]?)\{REL_PATH\}main/img/#i',
            '$1/img/',
            $html,
            -1,
            $c2
        );
        $countAll += (int) $c2;

        $html = preg_replace(
            '#(\bsrc=\s*[\'"])\{REL_PATH\}main/img/#i',
            '$1/img/',
            $html,
            -1,
            $c2b
        );
        $countAll += (int) $c2b;

        // Raw /main/img/... -> /img/...
        $html = preg_replace(
            '#(\burl\(\s*[\'"]?)/main/img/#i',
            '$1/img/',
            $html,
            -1,
            $c3
        );
        $countAll += (int) $c3;

        $html = preg_replace(
            '#(\bsrc=\s*[\'"])/main/img/#i',
            '$1/img/',
            $html,
            -1,
            $c3b
        );
        $countAll += (int) $c3b;

        // {COURSE_DIR}images/... -> /img/...
        $html = preg_replace(
            '#(\burl\(\s*[\'"]?)\{COURSE_DIR\}images/#i',
            '$1/img/',
            $html,
            -1,
            $c4
        );
        $countAll += (int) $c4;

        $html = preg_replace(
            '#(\bsrc=\s*[\'"])\{COURSE_DIR\}images/#i',
            '$1/img/',
            $html,
            -1,
            $c4b
        );
        $countAll += (int) $c4b;

        // /img/certificates/... -> /img/...
        $html = preg_replace(
            '#(\burl\(\s*[\'"]?)/img/certificates/#i',
            '$1/img/',
            $html,
            -1,
            $c5
        );
        $countAll += (int) $c5;

        $html = preg_replace(
            '#(\bsrc=\s*[\'"])/img/certificates/#i',
            '$1/img/',
            $html,
            -1,
            $c5b
        );
        $countAll += (int) $c5b;

        $html = preg_replace('#/img//+#', '/img/', $html, -1, $c6);
        $countAll += (int) $c6;

        if ($countAll > 0) {
            $this->dbg(\sprintf(
                '[MIG][templates] id=%d title="%s" rewrites=%d',
                $id,
                $title,
                $countAll
            ));
        }

        return $html;
    }

    /**
     * Extracts /img/... references from <img src> and CSS url(...).
     *
     * @return string[] relative paths (starting with /img)
     */
    private function extractPublicImagePaths(string $html): array
    {
        $found = [];

        // <img src="/img/...">
        if (preg_match_all('#<img\b[^>]*\bsrc\s*=\s*["\'](/img/[^"\']+)#i', $html, $m1)) {
            foreach ($m1[1] as $p) {
                $found[$p] = true;
            }
        }

        // url('/img/...') o url(/img/...)
        if (preg_match_all('#url\(\s*[\'"]?(/img/[^)\'"]+)#i', $html, $m2)) {
            foreach ($m2[1] as $p) {
                $found[$p] = true;
            }
        }

        return array_keys($found);
    }

    /**
     * Records (and accumulates) the list of images and checks for existence under public/.
     *
     * @param string[] $paths
     */
    private function logNeededImages(int $id, string $title, array $paths): void
    {
        if (empty($paths)) {
            $this->dbg(\sprintf('[MIG][templates:images] id=%d title="%s" no image refs.', $id, $title));

            return;
        }

        $publicBase = $this->resolvePublicBaseDir();
        $this->dbg(\sprintf('[MIG][templates:images] id=%d title="%s" refs=%d', $id, $title, \count($paths)));

        foreach ($paths as $rel) {
            $this->globalNeeded[$rel] = true;
            $status = $this->fileStatus($publicBase, $rel);
            $this->dbg(\sprintf('  %s  %s', $status, $rel));
        }
    }

    /**
     * Attempts to resolve absolute path to public/ when run via CLI.
     */
    private function resolvePublicBaseDir(): ?string
    {
        $cwdPublic = getcwd() ? rtrim((string) getcwd(), '/').'/public' : null;
        if ($cwdPublic && is_dir($cwdPublic)) {
            return $cwdPublic;
        }

        $guess = realpath(__DIR__.'/../../../../../../public');
        if ($guess && is_dir($guess)) {
            return $guess;
        }

        for ($i = 1; $i <= 7; $i++) {
            $up = realpath(__DIR__.str_repeat('/..', $i).'/public');
            if ($up && is_dir($up)) {
                return $up;
            }
        }

        $this->dbg('[MIG][templates:images] WARN: could not resolve public/ base dir.');

        return null;
    }

    /**
     * Mark whether the file exists or not.
     */
    private function fileStatus(?string $publicBase, string $relPath): string
    {
        if (!$publicBase) {
            return '[?   ]';
        }
        $abs = $publicBase.$relPath;
        if (is_file($abs)) {
            return '[OK  ]';
        }

        return '[MISS]';
    }

    private function dbg(string $msg): void
    {
        if (self::DEBUG) {
            error_log($msg);
        }
    }
}
