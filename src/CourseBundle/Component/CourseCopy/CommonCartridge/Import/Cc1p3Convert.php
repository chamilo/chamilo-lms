<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\CcBase;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter\Cc13Forum;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter\Cc13Quiz;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter\Cc13Resource;
use DocumentManager;
use DOMElement;
use DOMXPath;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use const DIRECTORY_SEPARATOR;

class Cc1p3Convert extends CcBase
{
    // Keep local CC_TYPE_* for readability; values must match the manifest exactly (v1p3).
    public const CC_TYPE_FORUM = 'imsdt_xmlv1p3';
    public const CC_TYPE_QUIZ = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const CC_TYPE_QUESTION_BANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const CC_TYPE_WEBLINK = 'imswl_xmlv1p3';
    public const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const CC_TYPE_WEBCONTENT = 'webcontent';
    public const CC_TYPE_BASICLTI = 'imsbasiclti_xmlv1p3';

    /**
     * XPath namespaces for imsmanifest.xml (v1p3 or plain imscp v1p1).
     */
    public static $namespaces = [
        'imscc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
        'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
        'lom' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
        'voc' => 'http://ltsc.ieee.org/xsd/LOM/vocab',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        'cc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsccauth_v1p1',
    ];

    // These remain for backward compatibility; converters may read them.
    public static $restypes = ['associatedcontent/imscc_xmlv1p3/learning-application-resource', 'webcontent'];
    public static $forumns = ['dt' => 'http://www.imsglobal.org/xsd/imsccv1p3/imsdt_v1p3'];
    public static $quizns = ['xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2'];
    public static $resourcens = ['wl' => 'http://www.imsglobal.org/xsd/imsccv1p3/imswl_v1p3'];
    public static array $basicltins = [
        'xmlns' => 'http://www.imsglobal.org/xsd/imslticc_v1p0',
        'blti' => 'http://www.imsglobal.org/xsd/imsbasiclti_v1p0',
        'lticm' => 'http://www.imsglobal.org/xsd/imslticm_v1p0',
        'lticp' => 'http://www.imsglobal.org/xsd/imslticp_v1p0',
    ];

    public function __construct(string $path_to_manifest)
    {
        parent::__construct($path_to_manifest);
        // Point our "imscc" prefix to the actual root namespace (v1p3 or plain imscp v1p1).
        $this->normalizeImscpNamespace();
    }

    /**
     * Resolve the absolute path to imsmanifest.xml in an extracted cartridge folder.
     * It searches a few common locations and returns the first existing path.
     */
    public static function getManifest(string $extractedDir): ?string
    {
        if (is_file($extractedDir) && preg_match('~imsmanifest\.xml$~i', $extractedDir)) {
            return $extractedDir;
        }

        $candidates = [
            rtrim($extractedDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'imsmanifest.xml',
        ];

        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        // Shallow recursive search (depth 2) just in case
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $maxDepth = 2;
        foreach ($it as $fs) {
            if ($it->getDepth() > $maxDepth) {
                continue;
            }
            if ($fs->isFile() && 0 === strcasecmp($fs->getFilename(), 'imsmanifest.xml')) {
                return $fs->getPathname();
            }
        }

        return null;
    }

    /**
     * Scan the manifest and create Chamilo resources.
     */
    public function generateImportData(): void
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);

        // If parent logic didn't populate instances, build them here (v1.3-aware).
        if (empty(self::$instances['instances']) || !\is_array(self::$instances['instances'])) {
            $this->buildInstancesForV13($xpath);
        }

        // Converters
        $resourcesConv = new Cc13Resource();
        $forumsConv = new Cc13Forum();
        $quizConv = new Cc13Quiz();

        // Build data payloads from embedded XML resources
        $documentValues = $resourcesConv->generateData('document');
        $linkValues = $resourcesConv->generateData('link');
        $forumValues = $forumsConv->generateData();
        $quizValues = $quizConv->generateData();

        // Ensure /document/commoncartridge exists (Chamilo 2 resource tree)
        if (!empty($forumValues) || !empty($quizValues) || !empty($documentValues) || !empty($linkValues)) {
            $courseInfo = api_get_course_info();
            $courseEntity = api_get_course_entity($courseInfo['real_id']);
            $sessionEnt = api_get_session_entity((int) api_get_session_id());
            $groupEnt = api_get_group_entity(0);

            $docRepo = Container::getDocumentRepository();

            $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $sessionEnt, $groupEnt): int {
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
                        $groupEnt
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
                        (int) ($sessionEnt ? $sessionEnt->getId() : 0),
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

            // Create the base folder once
            $ensureFolder('/commoncartridge');
        }

        self::logAction(
            'cc13: payload sizes',
            [
                'docs' => \count($documentValues ?? []),
                'links' => \count($linkValues ?? []),
                'forums' => \count($forumValues ?? []),
                'quizzes' => \count($quizValues ?? []),
            ]
        );

        // Persist resources
        if (!empty($forumValues)) {
            $forumsConv->storeForums($forumValues);
        }
        if (!empty($quizValues)) {
            $quizConv->storeQuizzes($quizValues);
        }
        if (!empty($documentValues)) {
            $resourcesConv->storeDocuments($documentValues, static::$pathToManifestFolder);
        }
        if (!empty($linkValues)) {
            $resourcesConv->storeLinks($linkValues);
        }
    }

    /**
     * CC 1.3-specific instance builder that tolerates:
     *  - root NS = imscp_v1p1 (plain IMS CP)
     *  - resource@var variants v1p1 and v1p3 (imswl/imsdt)
     *  - missing @href (uses first <file href="...">)
     *  - heuristic by path for links/discussions
     */
    private function buildInstancesForV13(DOMXPath $xp): void
    {
        // Initialize bag once.
        if (empty(self::$instances['instances']) || !\is_array(self::$instances['instances'])) {
            self::$instances['instances'] = [];
        }

        // 1) Map resource id => basic info (type, href, first file)
        $resMap = $this->collectResourcesMap($xp);

        // 2) Map resource id <= referenced from organizations with a title
        $itemTitles = $this->collectItemTitles($xp);

        $nextInstance = 0;
        $push = function (string $bucket, array $payload) use (&$nextInstance): void {
            $nextInstance++;
            $payload['instance'] = $nextInstance;
            self::$instances['instances'][$bucket] ??= [];
            self::$instances['instances'][$bucket][] = $payload;
        };

        // 3) Create instances for every item that points to a resource
        foreach ($itemTitles as $resId => $titles) {
            if (!isset($resMap[$resId])) {
                continue;
            }
            $ri = $resMap[$resId];

            foreach ($titles as $title) {
                $bucket = $this->bucketForType($ri['type']);

                // Heuristic fallback by src path when type wasn't matched explicitly
                if (null === $bucket && '' !== $ri['src']) {
                    $path = strtolower($ri['src']);
                    if (preg_match('~(^|/)weblinks/[^/]+\.xml$~i', $path)) {
                        $bucket = 'link';
                    } elseif (preg_match('~(^|/)discussions/[^/]+\.xml$~i', $path)) {
                        $bucket = 'forum';
                    }
                }

                if (null === $bucket) {
                    continue;
                }

                $payload = [
                    'title' => $title,
                    'resource_identifier' => $resId,
                    'src' => $ri['src'],
                    'common_cartridge_type' => $ri['type'],
                ];
                $push($bucket, $payload);
            }
        }

        // 4) Detached resources (webcontent/associatedcontent with no item)
        foreach ($resMap as $resId => $ri) {
            $bucket = $this->bucketForType($ri['type']);

            if ('document' === $bucket && empty($itemTitles[$resId])) {
                $title = '' !== $ri['src'] ? basename($ri['src']) : ($ri['href'] ?: $resId);
                $payload = [
                    'title' => $title,
                    'resource_identifier' => $resId,
                    'src' => $ri['src'],
                    'common_cartridge_type' => $ri['type'],
                ];
                $push('document', $payload);
            }
        }

        // Debug log for visibility
        self::logAction('buildInstances:v13', [
            'doc' => \count(self::$instances['instances']['document'] ?? []),
            'link' => \count(self::$instances['instances']['link'] ?? []),
            'forum' => \count(self::$instances['instances']['forum'] ?? []),
            'quiz' => \count(self::$instances['instances']['quiz'] ?? []),
            'bank' => \count(self::$instances['instances']['question_bank'] ?? []),
        ]);
    }

    /**
     * Build a map of all <resource> elements.
     * Returns: id => ['type'=>..., 'href'=>..., 'src'=>firstFileOrHref].
     */
    private function collectResourcesMap(DOMXPath $xp): array
    {
        $map = [];

        // 1) Prefixed query (v1p3/normalized root NS)
        $nodes = $xp->query('/imscc:manifest/imscc:resources/imscc:resource');

        // 2) Fallback without namespaces for plain imscp_v1p1 roots
        if (!$nodes || 0 === $nodes->length) {
            $nodes = $xp->query('/*[local-name()="manifest"]/*[local-name()="resources"]/*[local-name()="resource"]');
            self::logAction('collectResourcesMap: fallback local-name() engaged');
        }

        if (!$nodes) {
            return $map;
        }

        foreach ($nodes as $res) {
            /** @var DOMElement $res */
            $id = (string) $res->getAttribute('identifier');
            $type = (string) $res->getAttribute('type');
            $href = (string) $res->getAttribute('href');

            // First <file href="..."> as safe fallback for src
            $fileHref = '';
            $file = $xp->query('imscc:file/@href', $res);
            if (!$file || 0 === $file->length) {
                // Fallback without ns
                $file = $xp->query('./*[local-name()="file"]/@href', $res);
            }
            if ($file && $file->length > 0) {
                $fileHref = (string) $file->item(0)->nodeValue;
            }

            $src = '' !== $href ? $href : $fileHref;

            $map[$id] = [
                'type' => $type,
                'href' => $href,
                'src' => $src,
            ];
        }

        self::logAction('collectResourcesMap: counted resources', ['count' => \count($map)]);

        return $map;
    }

    /**
     * Collect item -> resource titles:
     * Returns: resourceId => [title1, title2, ...]
     */
    private function collectItemTitles(DOMXPath $xp): array
    {
        $byRes = [];

        // 1) With prefixes
        $items = $xp->query('/imscc:manifest/imscc:organizations/imscc:organization//imscc:item[@identifierref]');

        // 2) Fallback without ns
        if (!$items || 0 === $items->length) {
            $items = $xp->query(
                '/*[local-name()="manifest"]/*[local-name()="organizations"]/*[local-name()="organization"]'.
                '//*[local-name()="item"][@identifierref]'
            );
            self::logAction('collectItemTitles: fallback local-name() engaged');
        }

        if (!$items) {
            return $byRes;
        }

        foreach ($items as $it) {
            /** @var DOMElement $it */
            $rid = (string) $it->getAttribute('identifierref');
            if ('' === $rid) {
                continue;
            }

            // Title node in both modes
            $t = $xp->query('imscc:title', $it);
            if (!$t || 0 === $t->length) {
                $t = $xp->query('./*[local-name()="title"]', $it);
            }

            $title = ($t && $t->length > 0) ? trim((string) $t->item(0)->nodeValue) : '';
            if ('' === $title) {
                $title = $rid;
            }

            $byRes[$rid] ??= [];
            $byRes[$rid][] = $title;
        }

        self::logAction('collectItemTitles: items grouped by resource', ['resources' => \count($byRes)]);

        return $byRes;
    }

    /**
     * Map resource/@var to our instance bucket.
     * Tolerant to v1p1 and v1p3 variants for imsdt/imswl; also accepts QTI patterns.
     */
    private function bucketForType(string $resType): ?string
    {
        $t = strtolower(trim($resType));

        // Documents
        if (self::CC_TYPE_WEBCONTENT === $t || self::CC_TYPE_ASSOCIATED_CONTENT === $t) {
            return 'document';
        }
        if ('webcontent' === $t) { // be explicit in case constants change
            return 'document';
        }

        // WebLink (accept v1p1 and v1p3)
        // Examples: imswl_xmlv1p3, imswl_xmlv1p1
        if (self::CC_TYPE_WEBLINK === $t || str_contains($t, 'imswl')) {
            return 'link';
        }

        // Discussion Topic (accept v1p1 and v1p3)
        // Examples: imsdt_xmlv1p3, imsdt_xmlv1p1
        if (self::CC_TYPE_FORUM === $t || str_contains($t, 'imsdt')) {
            return 'forum';
        }

        // Quizzes / question banks (be defensive with QTI strings)
        if (str_contains($t, '/assessment')) {
            return 'quiz';
        }
        if (str_contains($t, 'question-bank')) {
            return 'question_bank';
        }

        return null;
    }

    /**
     * Some CC manifests use plain IMS CP default NS (imscp_v1p1).
     * Our XPath prefix "imscc" must point to the root NS to match elements.
     */
    private function normalizeImscpNamespace(): void
    {
        if (!self::$manifest || !method_exists(self::$manifest, 'documentElement')) {
            return;
        }

        $root = self::$manifest->documentElement;
        if (!$root) {
            return;
        }

        $rootNs = (string) $root->namespaceURI;

        // Accept either of these as valid "manifest" NS for CC 1.3:
        //  - http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1  (some toolchains)
        //  - http://www.imsglobal.org/xsd/imscp_v1p1            (plain IMS CP)
        $allowed = [
            'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
            'http://www.imsglobal.org/xsd/imscp_v1p1',
        ];

        if (\in_array($rootNs, $allowed, true)) {
            // Point our "imscc" prefix to whatever the root is actually using.
            self::$namespaces['imscc'] = $rootNs;
        }
    }
}
