<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Cc1p3Convert;
use Chamilo\CourseBundle\Entity\CLink;
use Database;
use DocumentManager;
use DOMXPath;
use Throwable;

use const DIRECTORY_SEPARATOR;
use const ENT_COMPAT;
use const ENT_QUOTES;
use const FILTER_VALIDATE_URL;
use const PATHINFO_EXTENSION;
use const PHP_URL_SCHEME;

class Cc13Resource extends Cc13Entities
{
    public function generateData($resource_type)
    {
        $data = [];
        if (!empty(Cc1p3Convert::$instances['instances'][$resource_type])) {
            foreach (Cc1p3Convert::$instances['instances'][$resource_type] as $instance) {
                $data[] = $this->getResourceData($instance);
            }
        }

        return $data;
    }

    /**
     * Store web links using Doctrine entities (Chamilo 2 style).
     *
     * @param mixed $links
     */
    public function storeLinks($links): bool
    {
        if (empty($links)) {
            return true;
        }

        $em = Database::getManager();
        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity((int) api_get_session_id());

        foreach ($links as $link) {
            $title = trim(htmlspecialchars_decode((string) ($link[1] ?? ''), ENT_QUOTES)) ?: (string) ($link[4] ?? '');
            $url = trim((string) ($link[4] ?? ''));
            if ('' === $url) {
                Cc1p3Convert::logAction('storeLinks: empty URL skipped', ['title' => $title]);

                continue;
            }

            // Basic sanity check (best-effort).
            if (!self::validateUrlSyntax($url, 's+')) {
                $try = rawurldecode($url);
                if (self::validateUrlSyntax($try, 's+')) {
                    $url = $try;
                } else {
                    Cc1p3Convert::logAction('storeLinks: invalid URL skipped', ['url' => $url]);

                    continue; // Skip invalid URL instead of creating a broken entity.
                }
            }

            Cc1p3Convert::logAction('storeLinks: creating link', ['title' => $title, 'url' => $url]);

            $entity = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription('')
                ->setTarget('_blank')
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $em->persist($entity);
        }
        $em->flush();

        return true;
    }

    /**
     * Store Documents from CC package. No SYS_COURSE_PATH. Uses DocumentManager + ResourceFile.
     *
     * @param array  $documents   Items from getResourceData()
     * @param string $packageRoot Absolute path to the extracted package (directory of imsmanifest.xml)
     */
    public function storeDocuments(array $documents, string $packageRoot): bool
    {
        $packageRoot = rtrim($packageRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $courseInfo = api_get_course_info();
        $courseEntity = api_get_course_entity($courseInfo['real_id']);
        $sessionEnt = api_get_session_entity((int) api_get_session_id());
        $sessionId = (int) ($sessionEnt ? $sessionEnt->getId() : 0);
        $group = api_get_group_entity(0);

        $docRepo = Container::getDocumentRepository();

        // Ensure nested folder chain under Documents and return parent iid.
        $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $sessionEnt, $group, $sessionId): int {
            $rel = '/'.ltrim($relPath, '/');
            if ('/' === $rel || '' === $rel) {
                return 0;
            }

            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
            $accum = '';
            $parentId = 0;

            foreach ($parts as $seg) {
                $accum .= '/'.$seg;

                $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

                $existing = $docRepo->findCourseResourceByTitle(
                    $seg,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEnt,
                    $group
                );

                if ($existing && method_exists($existing, 'getIid')) {
                    $parentId = (int) $existing->getIid();

                    continue;
                }

                $entity = DocumentManager::addDocument(
                    ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                    $accum,
                    'folder',
                    0,
                    $seg,
                    null,
                    0,
                    null,
                    0,
                    $sessionId,
                    0,
                    false,
                    '',
                    $parentId,
                    ''
                );

                $parentId = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
            }

            return $parentId;
        };

        // Base destination root for the package contents.
        $baseRel = '/commoncartridge';
        $ensureFolder($baseRel);

        foreach ($documents as $doc) {
            $type = (string) ($doc[2] ?? '');

            // Compute destination subfolder:
            $subdir = '';
            if ('file' === $type) {
                $ref = trim((string) ($doc[4] ?? ''));
                if ('' === $ref) {
                    continue;
                }
                $subdir = trim(\dirname($ref), '.\/');
            } elseif ('html' === $type) {
                $subdir = trim((string) ($doc[6] ?? ''), '/');
            } else {
                // Unknown type; skip gracefully.
                Cc1p3Convert::logAction('storeDocuments: unknown type skipped', ['type' => $type]);

                continue;
            }

            $destPath = $baseRel.($subdir ? '/'.$subdir : '');
            $parentId = $ensureFolder($destPath);

            // Collision-safe final title.
            $title = (string) ($doc[1] ?? '');
            $guessName = 'file' === $type ? basename((string) ($doc[4] ?? 'file.bin')) : 'page.html';
            $finalTitle = '' !== $title ? $title : $guessName;

            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
            $nameExists = function (string $t) use ($docRepo, $parentRes, $courseEntity, $sessionEnt, $group): bool {
                $e = $docRepo->findCourseResourceByTitle(
                    $t,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEnt,
                    $group
                );

                return (bool) ($e && method_exists($e, 'getIid'));
            };

            if ($nameExists($finalTitle)) {
                $pi = pathinfo($finalTitle);
                $base = $pi['filename'] ?? $finalTitle;
                $ext = isset($pi['extension']) && '' !== $pi['extension'] ? '.'.$pi['extension'] : '';
                $i = 1;
                while ($nameExists($base.'_'.$i.$ext)) {
                    $i++;
                }
                $finalTitle = $base.'_'.$i.$ext;
            }

            // Persist
            if ('file' === $type) {
                $ref = trim((string) ($doc[4] ?? ''));
                $src = $packageRoot.str_replace('\\', '/', $ref);
                if (!is_file($src) || !is_readable($src)) {
                    Cc1p3Convert::logAction('CC import: missing/unreadable file', ['src' => $src]);

                    continue;
                }

                try {
                    DocumentManager::addDocument(
                        ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                        $destPath.'/'.$finalTitle,
                        'file',
                        (int) @filesize($src),
                        $finalTitle,
                        '',
                        0,
                        null,
                        0,
                        $sessionId,
                        0,
                        false,
                        '',      // no inline content for binaries
                        $parentId,
                        $src     // realPath → copied into ResourceFile storage
                    );
                } catch (Throwable $e) {
                    Cc1p3Convert::logAction('CC import: addDocument(file) failed', ['src' => $src, 'error' => $e->getMessage()]);
                }

                continue;
            }

            if ('html' === $type) {
                // Inline HTML content, keep relative links intact (folder layout is mirrored).
                $content = (string) ($doc[3] ?? '');

                try {
                    DocumentManager::addDocument(
                        ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                        $destPath.'/'.$finalTitle,
                        'file',
                        (int) \strlen($content),
                        $finalTitle,
                        '',
                        0,
                        null,
                        0,
                        $sessionId,
                        0,
                        false,
                        $content, // inline HTML
                        $parentId,
                        ''        // no realPath when content provided
                    );
                } catch (Throwable $e) {
                    Cc1p3Convert::logAction('CC import: addDocument(html) failed', ['dest' => $destPath.'/'.$finalTitle, 'error' => $e->getMessage()]);
                }

                continue;
            }
        }

        return true;
    }

    /**
     * Build normalized resource tuple for the importer pipeline.
     * Returns:
     *  [0]=instance, [1]=title, [2]=type('file'|'html'), [3]=html, [4]=href, [5]=options, [6]=baseDir(html only).
     *
     * @param mixed $instance
     */
    public function getResourceData($instance)
    {
        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);
        $link = '';
        $baseDir = '';

        if (
            Cc1p3Convert::CC_TYPE_WEBCONTENT == $instance['common_cartridge_type']
            || Cc1p3Convert::CC_TYPE_ASSOCIATED_CONTENT == $instance['common_cartridge_type']
        ) {
            $resource = $xpath->query(
                '/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$instance['resource_identifier'].'"]/@href'
            );
            if ($resource->length > 0) {
                $resource = !empty($resource->item(0)->nodeValue) ? $resource->item(0)->nodeValue : '';
            } else {
                $resource = '';
            }

            if (empty($resource)) {
                // Fallback: use src set in CcBase::createInstances() from <file href="...">
                $resource = $instance['src'];
            }
            if (!empty($resource)) {
                $link = $resource;
                $baseDir = trim(\dirname($resource), '.\/');
            }
        }

        if (Cc1p3Convert::CC_TYPE_WEBLINK == $instance['common_cartridge_type']) {
            $external_resource = $instance['src'];
            if (!empty($external_resource)) {
                $resourceDoc = $this->loadXmlResource(
                    Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$external_resource
                );

                if (!empty($resourceDoc)) {
                    // Namespace-agnostic: webLink/url with any NS
                    $x = new DOMXPath($resourceDoc);
                    $href = $x->query('/*[local-name()="webLink"]/*[local-name()="url"]/@href');
                    if ($href->length > 0) {
                        $raw = trim((string) $href->item(0)->nodeValue);
                        Cc1p3Convert::logAction('getResourceData: webLink extracted', ['raw' => $raw]);

                        if (!self::validateUrlSyntax($raw, 's+')) {
                            $changed = rawurldecode($raw);
                            if (self::validateUrlSyntax($changed, 's+')) {
                                $link = $changed;
                            } else {
                                Cc1p3Convert::logAction('getResourceData: invalid webLink URL', ['raw' => $raw]);
                                $link = 'http://invalidurldetected/';
                            }
                        } else {
                            $link = htmlspecialchars($raw, ENT_COMPAT, 'UTF-8', false);
                        }
                    } else {
                        Cc1p3Convert::logAction('getResourceData: webLink href not found via XPath(local-name())', ['file' => $external_resource]);
                    }
                }
            }
        }

        // Decide type: file vs html
        $type = 'file';
        $htmlContent = '';
        $options = '';

        if (!empty($link) && (Cc1p3Convert::CC_TYPE_WEBCONTENT == $instance['common_cartridge_type'])) {
            $ext = strtolower(pathinfo($link, PATHINFO_EXTENSION) ?: '');
            if (\in_array($ext, ['html', 'htm', 'xhtml'], true)) {
                $type = 'html';

                $root = realpath(Cc1p3Convert::$pathToManifestFolder);
                $abs = $root ? realpath($root.DIRECTORY_SEPARATOR.$link) : false;

                if ($abs && is_file($abs)) {
                    // Read HTML and strip outer wrappers; keep relative URLs as-is.
                    $raw = (string) @file_get_contents($abs);
                    $htmlContent = self::safexml($this->prepareContent($raw));
                    // For inline HTML we clear the href; the storage path is decided in storeDocuments.
                    $link = '';
                }
            }
        }

        return [
            $instance['instance'],
            self::safexml($instance['title'] ?: ($link ? basename($link) : '')),
            $type,
            $htmlContent,
            $link,
            $options,
            $baseDir,
        ];
    }

    /**
     * Simple URL validator.
     * $mode:
     *  - 's+' => require scheme (http/https) and validate full URL
     *  - 's*' => scheme optional (rarely used here).
     */
    private static function validateUrlSyntax(string $url, string $mode = 's+'): bool
    {
        $u = trim($url);
        if ('' === $u) {
            return false;
        }

        // Try original first
        if (false !== filter_var($u, FILTER_VALIDATE_URL)) {
            $scheme = strtolower(parse_url($u, PHP_URL_SCHEME) ?? '');
            if ('' === $scheme && 's+' === $mode) {
                return false;
            }
            if ('' !== $scheme && !\in_array($scheme, ['http', 'https'], true)) {
                return false;
            }

            return true;
        }

        // Try decoded form (handles %20 etc.)
        $dec = rawurldecode($u);
        if (false !== filter_var($dec, FILTER_VALIDATE_URL)) {
            $scheme = strtolower(parse_url($dec, PHP_URL_SCHEME) ?? '');
            if ('' === $scheme && 's+' === $mode) {
                return false;
            }
            if ('' !== $scheme && !\in_array($scheme, ['http', 'https'], true)) {
                return false;
            }

            return true;
        }

        return false;
    }
}
