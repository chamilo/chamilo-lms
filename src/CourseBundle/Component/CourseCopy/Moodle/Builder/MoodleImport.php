<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CourseBundle\Component\CourseCopy\Course;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PharData;
use RuntimeException;
use stdClass;
use Throwable;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILEINFO_MIME_TYPE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * Moodle importer for Chamilo.
 */
class MoodleImport
{
    private ?string $ctxArchivePath = null;
    private ?EntityManagerInterface $ctxEm = null;
    private int $ctxCourseRealId = 0;
    private int $ctxSessionId = 0;
    private ?int $ctxSameFileNameOption = null;

    public function __construct(private bool $debug = false) {}

    public function attachContext(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?int $sameFileNameOption = null
    ): self {
        $this->ctxArchivePath = $archivePath;
        $this->ctxEm = $em;
        $this->ctxCourseRealId = $courseRealId;
        $this->ctxSessionId = $sessionId;
        $this->ctxSameFileNameOption = $sameFileNameOption;

        return $this;
    }

    /**
     * Builds a Course ready for CourseRestorer::restore().
     */
    public function buildLegacyCourseFromMoodleArchive(string $archivePath): object
    {
        $rid = \function_exists('random_bytes') ? substr(bin2hex(random_bytes(3)), 0, 6) : substr(sha1((string) mt_rand()), 0, 6);
        if ($this->debug) { error_log("MBZ[$rid] START buildLegacyCourseFromMoodleArchive archivePath={$archivePath}"); }

        // 1) Extract archive to a temp working directory
        [$workDir] = $this->extractToTemp($archivePath);
        if ($this->debug) { error_log("MBZ[$rid] extracted workDir={$workDir}"); }

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            if ($this->debug) { error_log("MBZ[$rid] ERROR moodle_backup.xml missing at {$mbx}"); }
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }

        // Optional: files.xml for documents/resources
        $fx = $workDir.'/files.xml';
        $hasFilesXml = is_file($fx);
        $fileIndex = $hasFilesXml ? $this->buildFileIndex($fx, $workDir) : ['byId' => [], 'byHash' => []];
        if ($this->debug) {
            $byId  = isset($fileIndex['byId']) ? count((array) $fileIndex['byId']) : 0;
            $byHash= isset($fileIndex['byHash']) ? count((array) $fileIndex['byHash']) : 0;
            error_log("MBZ[$rid] indexes moodle_backup.xml=1 files.xml=".($hasFilesXml?1:0)." fileIndex.byId={$byId} fileIndex.byHash={$byHash}");
        }

        // 2) Load main XMLs
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        // Detect meta sidecars early to drive import policy
        $hasQuizMeta = $this->hasQuizMeta($workDir);
        $hasLpMeta   = $this->hasLearnpathMeta($workDir);
        if ($this->debug) { error_log("MBZ[$rid] meta_flags hasQuizMeta=".($hasQuizMeta?1:0)." hasLpMeta=".($hasLpMeta?1:0)); }

        $skippedQuizXml = 0; // stats

        // Optional course.xml (course meta, summary)
        $courseXmlPath = $workDir.'/course/course.xml';
        $courseMeta = $this->readCourseMeta($courseXmlPath); // NEW: safe, tolerant
        if ($this->debug) {
            $cm = array_intersect_key((array)$courseMeta, array_flip(['fullname','shortname','idnumber','format']));
            error_log("MBZ[$rid] course_meta ".json_encode($cm, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 3) Read sections & pre-build LP map (one LP per section)
        $sections = $this->readSections($mb);
        if ($this->debug) { error_log("MBZ[$rid] sections.count=".count((array)$sections)); }
        $lpMap = $this->sectionsToLearnpaths($sections);

        // 4) Init resource buckets (legacy snapshot shape)
        $resources = [
            'document'            => [],
            'Forum_Category'      => [],
            'forum'               => [],
            'Link_Category'       => [],
            'link'                => [],
            'learnpath'           => [],
            'learnpath_category'  => [],
            'scorm_documents'     => [],
            'scorm'               => [],
            'announcement'        => [],
            'course_descriptions' => [],
            'tool_intro'          => [],
            'events'              => [],
            'quizzes'             => [],
            'quiz_question'       => [],
            'surveys'             => [],
            'works'               => [],
            'glossary'            => [],
            'wiki'                => [],
            'gradebook'           => [],
            'assets'              => [],
            'attendance'          => [],
        ];

        // 5) Ensure a default Forum Category (fallback)
        $defaultForumCatId = 1;
        $resources['Forum_Category'][$defaultForumCatId] = $this->mkLegacyItem('Forum_Category', $defaultForumCatId, [
            'id'          => $defaultForumCatId,
            'cat_title'   => 'General',
            'cat_comment' => '',
            'title'       => 'General',
            'description' => '',
        ]);
        if ($this->debug) { error_log("MBZ[$rid] forum.default_category id={$defaultForumCatId}"); }

        // 6) Ensure document working dirs
        $this->ensureDir($workDir.'/document');
        $this->ensureDir($workDir.'/document/moodle_pages');

        // Root folder example (kept for consistency; optional)
        if (empty($resources['document'])) {
            $docFolderId = $this->nextId($resources['document']);
            $resources['document'][$docFolderId] = $this->mkLegacyItem('document', $docFolderId, [
                'file_type' => 'folder',
                'path'      => '/document/moodle_pages',
                'title'     => 'moodle_pages',
            ]);
            if ($this->debug) { error_log("MBZ[$rid] document.root_folder id={$docFolderId} path=/document/moodle_pages"); }
        }

        // 7) Iterate activities and fill buckets
        $activityNodes = $mb->query('//activity');
        $activityCount = $activityNodes?->length ?? 0;
        if ($this->debug) { error_log("MBZ[$rid] activities.count total={$activityCount}"); }
        $i = 0; // contador interno (punto en property names no permitido, usar otra var)
        $i = 0;

        foreach ($activityNodes as $node) {
            /** @var DOMElement $node */
            $i++;
            $modName   = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            $dir       = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $sectionId = (int)    ($node->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);

            if ($this->debug) { error_log("MBZ[$rid] activity #{$i} mod={$modName} dir={$dir} section={$sectionId}"); }

            // Locate module xml path
            $moduleXml = ('' !== $modName && '' !== $dir) ? $workDir.'/'.$dir.'/'.$modName.'.xml' : null;
            if (!$moduleXml || !is_file($moduleXml)) {
                // Some modules use different file names (resource, folder...) – handled separately
                if ($this->debug) { error_log("MBZ[$rid] activity #{$i} skip={$modName} reason=module_xml_not_found"); }
            }

            // --- Early mapping: Moodle forum(type=news) -> Chamilo announcements (skip normal forum path)
            if ($moduleXml && is_file($moduleXml) && strtolower((string)$modName) === 'forum') {
                if ($this->hasChamiloAnnouncementMeta($workDir)) {
                    if ($this->debug) { error_log("MBZ[$rid] forum early-map: announcements meta present -> keep non-news, skip news"); }
                } else {
                    $forumInfo = $this->readForumHeader($moduleXml);
                    if ($this->isNewsForum($forumInfo)) {
                        if ($this->debug) { error_log("MBZ[$rid] forum NEWS detected -> mapping to announcements"); }
                        $anns = $this->readAnnouncementsFromForum($moduleXml, $workDir);

                        if (empty($anns)) {
                            if ($this->debug) { error_log("MBZ[$rid] forum NEWS no-discussions fallback=module intro"); }
                            $f = $this->readForumModule($moduleXml);
                            $fallbackTitle = (string)($f['name'] ?? 'announcement');
                            $fallbackHtml  = (string)($f['description'] ?? '');
                            $fallbackTime  = (int)($f['timemodified'] ?? $f['timecreated'] ?? time());
                            if ($fallbackHtml !== '') {
                                $anns[] = [
                                    'title'       => $fallbackTitle,
                                    'html'        => $this->wrapHtmlIfNeeded($fallbackHtml, $fallbackTitle),
                                    'date'        => date('Y-m-d H:i:s', $fallbackTime),
                                    'attachments' => [],
                                ];
                            }
                        }

                        foreach ($anns as $a) {
                            $iid = $this->nextId($resources['announcement']);
                            $payload = [
                                'title'               => (string) $a['title'],
                                'content'             => (string) $this->wrapHtmlIfNeeded($a['html'], (string)$a['title']),
                                'date'                => (string) $a['date'],
                                'display_order'       => 0,
                                'email_sent'          => 0,
                                'attachment_path'     => (string) ($a['first_path'] ?? ''),
                                'attachment_filename' => (string) ($a['first_name'] ?? ''),
                                'attachment_size'     => (int)    ($a['first_size'] ?? 0),
                                'attachment_comment'  => '',
                                'attachments'         => (array)  ($a['attachments'] ?? []),
                            ];
                            $resources['announcement'][$iid] = $this->mkLegacyItem('announcement', $iid, $payload, ['attachments']);
                        }
                        if ($this->debug) { error_log("MBZ[$rid] forum NEWS mapped announcements.count=".count($anns)." -> skip forum case"); }
                        continue; // Skip normal forum case
                    }
                }
            }

            switch ($modName) {
                case 'label': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $data = $this->readHtmlModule($moduleXml, 'label');
                    $title = (string) ($data['name'] ?? 'Label');
                    $html  = (string) $this->wrapHtmlIfNeeded(
                        $this->rewritePluginfileBasic((string) ($data['content'] ?? ''), 'label'),
                        $title
                    );
                    $descId = $this->nextId($resources['course_descriptions']);
                    $resources['course_descriptions'][$descId] = $this->mkLegacyItem('course_descriptions', $descId, [
                        'title'            => $title,
                        'content'          => $html,
                        'description_type' => 0,
                        'source_id'        => $descId,
                    ]);
                    if ($this->debug) { error_log("MBZ[$rid] label -> course_descriptions id={$descId} title=".json_encode($title)); }
                    break;
                }

                case 'page': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $isHomepage = $this->looksLikeCourseHomepage($dir, $moduleXml);

                    if ($isHomepage) {
                        $raw = $this->readPageContent($moduleXml);
                        $html = (string) $this->wrapHtmlIfNeeded(
                            $this->rewritePluginfileBasic($raw, 'page'),
                            get_lang('Introduction')
                        );
                        if (!isset($resources['tool_intro']['course_homepage'])) {
                            $resources['tool_intro']['course_homepage'] = $this->mkLegacyItem('tool_intro', 0, [
                                'id'         => 'course_homepage',
                                'intro_text' => $html,
                            ]);
                            if ($this->debug) { error_log("MBZ[$rid] page HOMEPAGE -> tool_intro[course_homepage] set"); }
                        } else {
                            if ($this->debug) { error_log("MBZ[$rid] page HOMEPAGE -> tool_intro[course_homepage] exists, skip overwrite"); }
                        }
                        break;
                    }

                    $data = $this->readHtmlModule($moduleXml, $modName);
                    $docId = $this->nextId($resources['document']);
                    $slug  = $data['slug'] ?: ('page_'.$docId);
                    $rel   = 'document/moodle_pages/'.$slug.'.html';
                    $abs   = $workDir.'/'.$rel;

                    $this->ensureDir(\dirname($abs));
                    $html = $this->wrapHtmlIfNeeded($data['content'] ?? '', $data['name'] ?? ucfirst($modName));
                    file_put_contents($abs, $html);

                    $resources['document'][$docId] = $this->mkLegacyItem('document', $docId, [
                        'file_type' => 'file',
                        'path'      => '/'.$rel,
                        'title'     => (string) ($data['name'] ?? ucfirst($modName)),
                        'size'      => @filesize($abs) ?: 0,
                        'comment'   => '',
                    ]);
                    if ($this->debug) { error_log("MBZ[$rid] page -> document id={$docId} path=/{$rel} title=".json_encode($resources['document'][$docId]->title)); }

                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'document',
                            'ref'       => $docId,
                            'title'     => $data['name'] ?? ucfirst($modName),
                        ];
                        if ($this->debug) { error_log("MBZ[$rid] page -> LP section={$sectionId} add document ref={$docId}"); }
                    }
                    break;
                }

                case 'forum': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }

                    // If there is Chamilo meta for announcements, prefer it and let non-news forums pass through
                    if ($this->hasChamiloAnnouncementMeta($workDir)) {
                        if ($this->debug) { error_log('MOODLE_IMPORT: announcements meta present → keep forum import for non-news'); }
                    }

                    // 1) Read forum type header
                    $forumInfo = $this->readForumHeader($moduleXml); // ['type' => 'news'|'general'|..., 'name' => ..., ...]
                    if ($this->debug) {
                        error_log('MOODLE_IMPORT: forum header peek -> ' . json_encode([
                                'name' => $forumInfo['name'] ?? null,
                                'type' => $forumInfo['type'] ?? null,
                            ]));
                    }

                    // 2) If it's a "news" forum and meta wasn't used → import as announcements (with fallback)
                    if (!$this->hasChamiloAnnouncementMeta($workDir) && $this->isNewsForum($forumInfo)) {
                        $anns = $this->readAnnouncementsFromForum($moduleXml, $workDir);
                        if ($this->debug) {
                            error_log('MOODLE_IMPORT: news-forum detected, announcements extracted=' . count($anns));
                        }

                        if (empty($anns)) {
                            if ($this->debug) { error_log('MOODLE_IMPORT: announcements empty -> intro fallback (in switch)'); }
                            $f = $this->readForumModule($moduleXml);
                            $fallbackTitle = (string)($f['name'] ?? 'announcement');
                            $fallbackHtml  = (string)($f['description'] ?? '');
                            $fallbackTime  = (int)($f['timemodified'] ?? $f['timecreated'] ?? time());
                            if ($fallbackHtml !== '') {
                                $anns[] = [
                                    'title'       => $fallbackTitle,
                                    'html'        => $this->wrapHtmlIfNeeded($fallbackHtml, $fallbackTitle),
                                    'date'        => date('Y-m-d H:i:s', $fallbackTime),
                                    'attachments' => [],
                                ];
                            }
                        }

                        foreach ($anns as $a) {
                            $iid = $this->nextId($resources['announcement']);
                            $payload = [
                                'title'               => (string) $a['title'],
                                'content'             => (string) $this->wrapHtmlIfNeeded($a['html'], (string)$a['title']),
                                'date'                => (string) $a['date'],
                                'display_order'       => 0,
                                'email_sent'          => 0,
                                'attachment_path'     => (string) ($a['first_path'] ?? ''),
                                'attachment_filename' => (string) ($a['first_name'] ?? ''),
                                'attachment_size'     => (int)    ($a['first_size'] ?? 0),
                                'attachment_comment'  => '',
                                'attachments'         => (array)  ($a['attachments'] ?? []),
                            ];
                            $resources['announcement'][$iid] = $this->mkLegacyItem('announcement', $iid, $payload, ['attachments']);
                        }

                        // Do NOT also import as forum
                        break;
                    }

                    // 3) Normal forum path (general, Q&A, etc.)
                    $f = $this->readForumModule($moduleXml);

                    $catId    = (int) ($f['category_id'] ?? 0);
                    $catTitle = (string) ($f['category_title'] ?? '');
                    if ($catId > 0 && !isset($resources['Forum_Category'][$catId])) {
                        $resources['Forum_Category'][$catId] = $this->mkLegacyItem('Forum_Category', $catId, [
                            'id'          => $catId,
                            'cat_title'   => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                            'cat_comment' => '',
                            'title'       => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                            'description' => '',
                        ]);
                        if ($this->debug) { error_log("MBZ[$rid] forum -> created Forum_Category id={$catId} title=".json_encode($catTitle)); }
                    }
                    $dstCatId = $catId > 0 ? $catId : $defaultForumCatId;

                    $fid = $this->nextId($resources['forum']);
                    $resources['forum'][$fid] = $this->mkLegacyItem('forum', $fid, [
                        'id'             => $fid,
                        'forum_title'    => (string) ($f['name'] ?? 'Forum'),
                        'forum_comment'  => (string) ($f['description'] ?? ''),
                        'forum_category' => $dstCatId,
                        'default_view'   => 'flat',
                    ]);
                    if ($this->debug) { error_log("MBZ[$rid] forum -> forum id={$fid} category={$dstCatId} title=".json_encode($resources['forum'][$fid]->forum_title)); }

                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'forum',
                            'ref'       => $fid,
                            'title'     => $f['name'] ?? 'Forum',
                        ];
                        if ($this->debug) { error_log("MBZ[$rid] forum -> LP section={$sectionId} add forum ref={$fid}"); }
                    }
                    break;
                }

                case 'url': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $u = $this->readUrlModule($moduleXml);
                    $urlVal = trim((string) ($u['url'] ?? ''));
                    if ('' === $urlVal) { if ($this->debug) { error_log("MBZ[$rid] url -> empty url, skip"); } break; }

                    $catId    = (int) ($u['category_id'] ?? 0);
                    $catTitle = (string) ($u['category_title'] ?? '');
                    if ($catId > 0 && !isset($resources['Link_Category'][$catId])) {
                        $resources['Link_Category'][$catId] = $this->mkLegacyItem('Link_Category', $catId, [
                            'id'          => $catId,
                            'title'       => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                            'description' => '',
                        ]);
                        if ($this->debug) { error_log("MBZ[$rid] url -> created Link_Category id={$catId} title=".json_encode($catTitle)); }
                    }

                    $lid       = $this->nextId($resources['link']);
                    $linkTitle = ($u['name'] ?? '') !== '' ? (string) $u['name'] : $urlVal;

                    $resources['link'][$lid] = $this->mkLegacyItem('link', $lid, [
                        'id'          => $lid,
                        'title'       => $linkTitle,
                        'description' => '',
                        'url'         => $urlVal,
                        'target'      => '',
                        'category_id' => $catId,
                        'on_homepage' => false,
                    ]);
                    if ($this->debug) { error_log("MBZ[$rid] url -> link id={$lid} url=".json_encode($urlVal)); }

                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'link',
                            'ref'       => $lid,
                            'title'     => $linkTitle,
                        ];
                        if ($this->debug) { error_log("MBZ[$rid] url -> LP section={$sectionId} add link ref={$lid}"); }
                    }
                    break;
                }

                case 'scorm': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $sc = $this->readScormModule($moduleXml);
                    $sid = $this->nextId($resources['scorm_documents']);

                    $resources['scorm_documents'][$sid] = $this->mkLegacyItem('scorm_documents', $sid, [
                        'id'    => $sid,
                        'title' => (string) ($sc['name'] ?? 'SCORM package'),
                    ]);
                    $resources['scorm'][$sid] = $resources['scorm_documents'][$sid];
                    if ($this->debug) { error_log("MBZ[$rid] scorm -> scorm_documents id={$sid}"); }

                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'scorm',
                            'ref'       => $sid,
                            'title'     => $sc['name'] ?? 'SCORM package',
                        ];
                        if ($this->debug) { error_log("MBZ[$rid] scorm -> LP section={$sectionId} add scorm ref={$sid}"); }
                    }
                    break;
                }

                case 'quiz': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    if ($hasQuizMeta) {
                        $peekTitle = $this->peekQuizTitle($moduleXml);
                        if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                            $lpMap[$sectionId]['items'][] = [
                                'item_type' => 'quiz',
                                'ref'       => null,
                                'title'     => $peekTitle ?? 'Quiz',
                            ];
                            if ($this->debug) { error_log("MBZ[$rid] quiz(meta) -> LP section={$sectionId} add quiz (ref=null) title=".json_encode($peekTitle ?? 'Quiz')); }
                        }
                        $skippedQuizXml++;
                        if ($this->debug) { error_log("MBZ[$rid] quiz(meta) skipping heavy XML (skipped={$skippedQuizXml})"); }
                        break;
                    }

                    [$quiz, $questions] = $this->readQuizModule($workDir, $dir, $moduleXml);
                    if (!empty($quiz)) {
                        $qid = $this->nextId($resources['quizzes']);
                        $resources['quizzes'][$qid] = $this->mkLegacyItem('quizzes', $qid, $quiz);
                        if ($this->debug) { error_log("MBZ[$rid] quiz -> quizzes id={$qid} title=".json_encode($quiz['name'] ?? 'Quiz')); }
                        if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                            $lpMap[$sectionId]['items'][] = [
                                'item_type' => 'quiz',
                                'ref'       => $qid,
                                'title'     => $quiz['name'] ?? 'Quiz',
                            ];
                            if ($this->debug) { error_log("MBZ[$rid] quiz -> LP section={$sectionId} add quiz ref={$qid}"); }
                        }
                        foreach ($questions as $q) {
                            $qqid = $this->nextId($resources['quiz_question']);
                            $resources['quiz_question'][$qqid] = $this->mkLegacyItem('quiz_question', $qqid, $q);
                        }
                        if ($this->debug) { error_log("MBZ[$rid] quiz -> quiz_question added=".count($questions)); }
                    }
                    break;
                }

                case 'survey':
                case 'feedback': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $s = $this->readSurveyModule($moduleXml, $modName);
                    if (!empty($s)) {
                        $sid = $this->nextId($resources['surveys']);
                        $resources['surveys'][$sid] = $this->mkLegacyItem('surveys', $sid, $s);
                        if ($this->debug) { error_log("MBZ[$rid] {$modName} -> surveys id={$sid}"); }
                        if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                            $lpMap[$sectionId]['items'][] = [
                                'item_type' => 'survey',
                                'ref'       => $sid,
                                'title'     => $s['name'] ?? ucfirst($modName),
                            ];
                            if ($this->debug) { error_log("MBZ[$rid] {$modName} -> LP section={$sectionId} add survey ref={$sid}"); }
                        }
                    }
                    break;
                }

                case 'assign': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $w = $this->readAssignModule($moduleXml);
                    if (!empty($w)) {
                        $wid = $this->nextId($resources['works']);
                        $resources['works'][$wid] = $this->mkLegacyItem('works', $wid, $w);
                        if ($this->debug) { error_log("MBZ[$rid] assign -> works id={$wid}"); }
                        if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                            $lpMap[$sectionId]['items'][] = [
                                'item_type' => 'works',
                                'ref'       => $wid,
                                'title'     => $w['name'] ?? 'Assignment',
                            ];
                            if ($this->debug) { error_log("MBZ[$rid] assign -> LP section={$sectionId} add works ref={$wid}"); }
                        }
                    }
                    break;
                }

                case 'glossary': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    $g = $this->readGlossaryModule($moduleXml);
                    $added = 0;
                    foreach ((array) ($g['entries'] ?? []) as $term) {
                        $title = (string) ($term['concept'] ?? '');
                        if ($title === '') { continue; }
                        $descHtml = $this->wrapHtmlIfNeeded((string) ($term['definition'] ?? ''), $title);
                        $gid = $this->nextId($resources['glossary']);
                        $resources['glossary'][$gid] = $this->mkLegacyItem('glossary', $gid, [
                            'id'          => $gid,
                            'title'       => $title,
                            'description' => $descHtml,
                            'approved'    => (int) ($term['approved'] ?? 1),
                            'aliases'     => (array) ($term['aliases'] ?? []),
                            'userid'      => (int) ($term['userid'] ?? 0),
                            'timecreated' => (int) ($term['timecreated'] ?? 0),
                            'timemodified'=> (int) ($term['timemodified'] ?? 0),
                        ]);
                        $added++;
                    }
                    if ($this->debug) { error_log("MBZ[$rid] glossary -> entries added={$added}"); }
                    break;
                }

                case 'wiki': {
                    if (!$moduleXml || !is_file($moduleXml)) { break; }
                    [$meta, $pages] = $this->readWikiModuleFull($moduleXml);
                    $added = 0;
                    if (!empty($pages)) {
                        foreach ($pages as $p) {
                            $payload = [
                                'pageId'  => (int) $p['id'],
                                'reflink' => (string) ($p['reflink'] ?? $this->slugify((string)$p['title'])),
                                'title'   => (string) $p['title'],
                                'content' => (string) $this->wrapHtmlIfNeeded($this->rewritePluginfileBasic((string)($p['content'] ?? ''), 'wiki'), (string)$p['title']),
                                'userId'  => (int) ($p['userid'] ?? 0),
                                'groupId' => 0,
                                'dtime'   => date('Y-m-d H:i:s', (int) ($p['timemodified'] ?? time())),
                                'progress'=> '',
                                'version' => (int) ($p['version'] ?? 1),
                                'source_id'       => (int) $p['id'],
                                'source_moduleid' => (int) ($meta['moduleid'] ?? 0),
                                'source_sectionid'=> (int) ($meta['sectionid'] ?? 0),
                            ];
                            $wkid = $this->nextId($resources['wiki']);
                            $resources['wiki'][$wkid] = $this->mkLegacyItem('wiki', $wkid, $payload);
                            $added++;
                        }
                    }
                    if ($this->debug) { error_log("MBZ[$rid] wiki -> pages added={$added}"); }
                    break;
                }

                default:
                    if ($this->debug) { error_log("MBZ[$rid] unhandled module {$modName}"); }
                    break;
            }

            if ($this->debug && ($i % 10 === 0)) {
                $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
                error_log("MBZ[$rid] progress.counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }

        // 8) Documents from resource + folder + inlined pluginfile
        $this->readDocuments($workDir, $mb, $fileIndex, $resources, $lpMap);
        if ($this->debug) {
            $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
            error_log("MBZ[$rid] after.readDocuments counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 8.1) Import quizzes from meta when present (skipping XML ensured above)
        if ($hasQuizMeta) {
            $this->tryImportQuizMeta($workDir, $resources);
            if ($this->debug) {
                $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
                error_log("MBZ[$rid] after.quizMeta counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }

        // 8.2) Prefer LP meta; otherwise fallback to sections
        $lpFromMeta = false;
        if ($hasLpMeta) {
            $lpFromMeta = $this->tryImportLearnpathMeta($workDir, $resources);
            if ($this->debug) {
                error_log("MBZ[$rid] lpFromMeta=".($lpFromMeta?1:0));
                $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
                error_log("MBZ[$rid] after.lpMeta counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }

        // 8.3) Thematic meta (authoritative)
        $this->tryImportThematicMeta($workDir, $resources);
        if ($this->debug) {
            $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
            error_log("MBZ[$rid] after.thematicMeta counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 8.4) Attendance meta (authoritative)
        $this->tryImportAttendanceMeta($workDir, $resources);
        if ($this->debug) {
            $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
            error_log("MBZ[$rid] after.attendanceMeta counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 8.5) Gradebook meta (authoritative)
        $this->tryImportGradebookMeta($workDir, $resources);
        if ($this->debug) {
            $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
            error_log("MBZ[$rid] after.gradebookMeta counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 9) Build learnpaths from sections (fallback only if no meta)
        if (!$lpFromMeta && !empty($lpMap)) {
            $this->backfillLpRefsFromResources($lpMap, $resources, [
                \defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz',
                'quizzes',
                'document',
                'forum',
                'link',
                'scorm',
            ]);

            foreach ($lpMap as $sid => $lp) {
                if (empty($resources['learnpath_category'])) {
                    $catId = $this->nextId($resources['learnpath_category']);
                    $resources['learnpath_category'][$catId] = $this->mkLegacyItem('learnpath_category', $catId, [
                        'id'    => $catId,
                        'name'  => 'Sections',
                        'title' => 'Sections',
                    ]);
                    if ($this->debug) { error_log("MBZ[$rid] lp.category created id={$catId}"); }
                }

                $linked = $this->collectLinkedFromLpItems($lp['items']);
                $lid    = $this->nextId($resources['learnpath']);

                $resources['learnpath'][$lid] = $this->mkLegacyItem(
                    'learnpath',
                    $lid,
                    [
                        'id'   => $lid,
                        'name' => (string) $lp['title'],
                        'lp_type' => 'section',
                        'category_id' => array_key_first($resources['learnpath_category']),
                    ],
                    ['items','linked_resources']
                );
                $resources['learnpath'][$lid]->items = array_map(
                    static fn (array $i) => [
                        'item_type' => (string) $i['item_type'],
                        'title'     => (string) $i['title'],
                        'path'      => '',
                        'ref'       => $i['ref'] ?? null,
                    ],
                    $lp['items']
                );
                $resources['learnpath'][$lid]->linked_resources = $linked;

                if ($this->debug) { error_log("MBZ[$rid] lp.created id={$lid} name=".json_encode($resources['learnpath'][$lid]->name)); }
            }
        }

        // 10) Course descriptions / tool intro from course meta (safe fallbacks)
        if (!empty($courseMeta['summary'])) {
            $cdId = $this->nextId($resources['course_descriptions']);
            $resources['course_descriptions'][$cdId] = $this->mkLegacyItem('course_descriptions', $cdId, [
                'title'       => 'Course summary',
                'description' => (string) $courseMeta['summary'],
                'type'        => 'summary',
            ]);
            $tiId = $this->nextId($resources['tool_intro']);
            $resources['tool_intro'][$tiId] = $this->mkLegacyItem('tool_intro', $tiId, [
                'tool'    => 'Course home',
                'title'   => 'Introduction',
                'content' => (string) $courseMeta['summary'],
            ]);
            if ($this->debug) { error_log("MBZ[$rid] course_meta -> added summary to course_descriptions id={$cdId} and tool_intro id={$tiId}"); }
        }

        // 11) Events (course-level calendar) — optional
        $events = $this->readCourseEvents($workDir);
        foreach ($events as $e) {
            $eid = $this->nextId($resources['events']);
            $resources['events'][$eid] = $this->mkLegacyItem('events', $eid, $e);
        }
        if ($this->debug) { error_log("MBZ[$rid] events.added count=".count($events)); }

        $resources = $this->canonicalizeResourceBags($resources);
        if ($this->debug) {
            $counts = array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources);
            error_log("MBZ[$rid] final.counts ".json_encode($counts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // 12) Compose Course snapshot
        $course = new Course();
        $course->resources   = $resources;
        $course->backup_path = $workDir;

        // 13) Meta: "metaexport" + derived moodle meta
        $meta = [
            'import_source' => 'moodle',
            'generated_at'  => date('c'),
            'moodle'        => [
                'fullname'  => (string) ($courseMeta['fullname'] ?? ''),
                'shortname' => (string) ($courseMeta['shortname'] ?? ''),
                'idnumber'  => (string) ($courseMeta['idnumber'] ?? ''),
                'startdate' => (int)    ($courseMeta['startdate'] ?? 0),
                'enddate'   => (int)    ($courseMeta['enddate'] ?? 0),
                'format'    => (string) ($courseMeta['format'] ?? ''),
            ],
        ];

        // Merge metaexport JSON if present (export_meta.json | meta_export.json)
        $meta = $this->mergeMetaExportIfPresent($workDir, $meta); // NEW

        $course->meta = $meta;
        $course->resources['__meta'] = $meta;

        // 14) Optional course basic info
        $ci = \function_exists('api_get_course_info') ? (api_get_course_info() ?: []) : [];
        if (property_exists($course, 'code')) {
            $course->code = (string) ($ci['code'] ?? '');
        }
        if (property_exists($course, 'type')) {
            $course->type = 'partial';
        }
        if (property_exists($course, 'encoding')) {
            $course->encoding = \function_exists('api_get_system_encoding')
                ? api_get_system_encoding()
                : 'UTF-8';
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT: resources='.json_encode(
                    array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ));
            error_log('MOODLE_IMPORT: backup_path='.$course->backup_path);
            if (property_exists($course, 'code') && property_exists($course, 'encoding')) {
                error_log('MOODLE_IMPORT: course_code='.$course->code.' encoding='.$course->encoding);
            }
            error_log("MBZ[$rid] DONE buildLegacyCourseFromMoodleArchive");
        }

        return $course;
    }


    private function extractToTemp(string $archivePath): array
    {
        $base = rtrim(sys_get_temp_dir(), '/').'/moodle_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
        if (!@mkdir($base, 0775, true)) {
            throw new RuntimeException('Cannot create temp dir');
        }

        $ext = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));
        if (\in_array($ext, ['zip', 'mbz'], true)) {
            $zip = new ZipArchive();
            if (true !== $zip->open($archivePath)) {
                throw new RuntimeException('Cannot open zip');
            }
            if (!$zip->extractTo($base)) {
                $zip->close();

                throw new RuntimeException('Cannot extract zip');
            }
            $zip->close();
        } elseif (\in_array($ext, ['gz', 'tgz'], true)) {
            $phar = new PharData($archivePath);
            $phar->extractTo($base, null, true);
        } else {
            throw new RuntimeException('Unsupported archive type');
        }

        if (!is_file($base.'/moodle_backup.xml')) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }

        return [$base];
    }

    private function loadXml(string $path): DOMDocument
    {
        $xml = @file_get_contents($path);
        if (false === $xml || '' === $xml) {
            throw new RuntimeException('Cannot read XML: '.$path);
        }
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        if (!@$doc->loadXML($xml)) {
            throw new RuntimeException('Invalid XML: '.$path);
        }

        return $doc;
    }

    /**
     * Build an index from files.xml.
     * Returns ['byId' => [id => row], 'byHash' => [hash => row]].
     * Each row contains: id, hash, filename, filepath, component, filearea, mimetype, filesize, contextid, blob(abs path).
     */
    private function buildFileIndex(string $filesXmlPath, string $workDir): array
    {
        $doc = $this->loadXml($filesXmlPath);
        $xp = new DOMXPath($doc);

        $byId = [];
        $byHash = [];

        foreach ($xp->query('//file') as $f) {
            /** @var DOMElement $f */
            $id = (int) ($f->getAttribute('id') ?? 0);
            $hash = (string) ($f->getElementsByTagName('contenthash')->item(0)?->nodeValue ?? '');
            if ('' === $hash) {
                continue;
            }

            $name = (string) ($f->getElementsByTagName('filename')->item(0)?->nodeValue ?? '');
            $fp = (string) ($f->getElementsByTagName('filepath')->item(0)?->nodeValue ?? '/');
            $comp = (string) ($f->getElementsByTagName('component')->item(0)?->nodeValue ?? '');
            $fa = (string) ($f->getElementsByTagName('filearea')->item(0)?->nodeValue ?? '');
            $mime = (string) ($f->getElementsByTagName('mimetype')->item(0)?->nodeValue ?? '');
            $size = (int) ($f->getElementsByTagName('filesize')->item(0)?->nodeValue ?? 0);
            $ctx = (int) ($f->getElementsByTagName('contextid')->item(0)?->nodeValue ?? 0);

            $blob = $this->contentHashPath($workDir, $hash);

            $row = [
                'id' => $id,
                'hash' => $hash,
                'filename' => $name,
                'filepath' => $fp,
                'component' => $comp,
                'filearea' => $fa,
                'mimetype' => $mime,
                'filesize' => $size,
                'contextid' => $ctx,
                'blob' => $blob,
            ];

            if ($id > 0) {
                $byId[$id] = $row;
            }
            $byHash[$hash] = $row;
        }

        return ['byId' => $byId, 'byHash' => $byHash];
    }

    private function readSections(DOMXPath $xp): array
    {
        $out = [];
        foreach ($xp->query('//section') as $s) {
            /** @var DOMElement $s */
            $id = (int) ($s->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);
            if ($id <= 0) {
                $id = (int) ($s->getElementsByTagName('number')->item(0)?->nodeValue
                    ?? $s->getElementsByTagName('id')->item(0)?->nodeValue
                    ?? 0);
            }
            $name = (string) ($s->getElementsByTagName('name')->item(0)?->nodeValue ?? '');
            $summary = (string) ($s->getElementsByTagName('summary')->item(0)?->nodeValue ?? '');
            if ($id > 0) {
                $out[$id] = ['id' => $id, 'name' => $name, 'summary' => $summary];
            }
        }

        return $out;
    }

    private function sectionsToLearnpaths(array $sections): array
    {
        $map = [];
        foreach ($sections as $sid => $s) {
            $title = $s['name'] ?: ('Section '.$sid);
            $map[(int) $sid] = [
                'title' => $title,
                'items' => [],
            ];
        }

        return $map;
    }

    private function readHtmlModule(string $xmlPath, string $type): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        $name = (string) ($xp->query('//name')->item(0)?->nodeValue ?? ucfirst($type));

        $content = (string) ($xp->query('//intro')->item(0)?->nodeValue
            ?? $xp->query('//content')->item(0)?->nodeValue
            ?? '');

        // NEW: normalize @@PLUGINFILE@@ placeholders so local files resolve
        $content = $this->normalizePluginfileContent($content);

        return [
            'name' => $name,
            'content' => $content,
            'slug' => $this->slugify($name),
        ];
    }

    private function readForumModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        $name = trim((string) ($xp->query('//forum/name')->item(0)?->nodeValue ?? ''));
        $description = (string) ($xp->query('//forum/intro')->item(0)?->nodeValue ?? '');
        $type = trim((string) ($xp->query('//forum/type')->item(0)?->nodeValue ?? 'general'));

        $catId = 0;
        $catTitle = '';
        if (preg_match('/CHAMILO2:forum_category_id:(\d+)/', $description, $m)) {
            $catId = (int) $m[1];
        }
        if (preg_match('/CHAMILO2:forum_category_title:([^\-]+?)\s*-->/u', $description, $m)) {
            $catTitle = trim($m[1]);
        }

        return [
            'name' => ('' !== $name ? $name : 'Forum'),
            'description' => $description,
            'type' => ('' !== $type ? $type : 'general'),
            'category_id' => $catId,
            'category_title' => $catTitle,
        ];
    }

    private function readUrlModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);
        $name = trim($xp->query('//url/name')->item(0)?->nodeValue ?? '');
        $url = trim($xp->query('//url/externalurl')->item(0)?->nodeValue ?? '');
        $intro = (string) ($xp->query('//url/intro')->item(0)?->nodeValue ?? '');

        $catId = 0;
        $catTitle = '';
        if (preg_match('/CHAMILO2:link_category_id:(\d+)/', $intro, $m)) {
            $catId = (int) $m[1];
        }
        if (preg_match('/CHAMILO2:link_category_title:([^\-]+?)\s*-->/u', $intro, $m)) {
            $catTitle = trim($m[1]);
        }

        return ['name' => $name, 'url' => $url, 'category_id' => $catId, 'category_title' => $catTitle];
    }

    private function readScormModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        return [
            'name' => (string) ($xp->query('//name')->item(0)?->nodeValue ?? 'SCORM'),
        ];
    }

    private function collectLinkedFromLpItems(array $items): array
    {
        $map = [
            'document' => 'document',
            'forum' => 'forum',
            'url' => 'link',
            'link' => 'link',
            'weblink' => 'link',
            'work' => 'works',
            'student_publication' => 'works',
            'quiz' => 'quiz',
            'exercise' => 'quiz',
            'survey' => 'survey',
            'scorm' => 'scorm',
        ];

        $out = [];
        foreach ($items as $i) {
            $t = (string) ($i['item_type'] ?? '');
            $r = $i['ref'] ?? null;
            if ('' === $t || null === $r) {
                continue;
            }
            $bag = $map[$t] ?? $t;
            $out[$bag] ??= [];
            $out[$bag][] = (int) $r;
        }

        return $out;
    }

    private function nextId(array $bucket): int
    {
        $max = 0;
        foreach ($bucket as $k => $_) {
            $i = is_numeric($k) ? (int) $k : 0;
            if ($i > $max) {
                $max = $i;
            }
        }

        return $max + 1;
    }

    private function slugify(string $s): string
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $t = strtolower(preg_replace('/[^a-z0-9]+/', '-', $t ?: $s));

        return trim($t, '-') ?: 'item';
    }

    private function wrapHtmlIfNeeded(string $content, string $title = 'Page'): string
    {
        $trim = ltrim($content);
        $looksHtml = str_contains(strtolower(substr($trim, 0, 200)), '<html')
            || str_contains(strtolower(substr($trim, 0, 200)), '<!doctype');

        if ($looksHtml) {
            return $content;
        }

        return "<!doctype html>\n<html><head><meta charset=\"utf-8\"><title>".
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
            "</title></head><body>\n".$content."\n</body></html>";
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create directory: '.$dir);
        }
    }

    /**
     * Resolve physical path for a given contenthash.
     * Our exporter writes blobs in: files/<first two letters of hash>/<hash>.
     */
    private function contentHashPath(string $workDir, string $hash): string
    {
        $h = trim($hash);
        if ('' === $h || \strlen($h) < 2) {
            return $workDir.'/files/'.$h;
        }

        // export convention: files/<two first letters>/<full-hash>
        return $workDir.'/files/'.substr($h, 0, 2).'/'.$h;
    }

    /**
     * Fast-path: persist only Links (and Link Categories) from a Moodle backup
     * directly with Doctrine entities. This bypasses the generic Restorer so we
     * avoid ResourceType#tool and UserAuthSource#url cascade issues.
     *
     * @return array{categories:int,links:int}
     */
    public function restoreLinks(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        // Resolve parent entities
        /** @var CourseEntity|null $course */
        $course = $em->getRepository(CourseEntity::class)->find($courseRealId);
        if (!$course) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        /** @var SessionEntity|null $session */
        $session = $sessionId > 0
            ? $em->getRepository(SessionEntity::class)->find($sessionId)
            : null;

        // Fast-path: use filtered snapshot if provided (import/resources selection)
        if ($courseArg && isset($courseArg->resources) && \is_array($courseArg->resources)) {
            $linksBucket = (array) ($courseArg->resources['link'] ?? []);
            $catsBucket = (array) ($courseArg->resources['Link_Category'] ?? []);

            if (empty($linksBucket)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: snapshot has no selected links');
                }

                return ['categories' => 0, 'links' => 0];
            }

            // Build set of category ids actually referenced by selected links
            $usedCatIds = [];
            foreach ($linksBucket as $L) {
                $oldCatId = (int) ($L->category_id ?? 0);
                if ($oldCatId > 0) {
                    $usedCatIds[$oldCatId] = true;
                }
            }

            // Persist only needed categories
            $catMapByOldId = [];
            $newCats = 0;

            foreach ($catsBucket as $oldId => $C) {
                if (!isset($usedCatIds[$oldId])) {
                    continue;
                }

                $cat = (new CLinkCategory())
                    ->setTitle((string) ($C->title ?? ('Category '.$oldId)))
                    ->setDescription((string) ($C->description ?? ''))
                ;

                // Parent & course/session links BEFORE persist (prePersist needs a parent)
                if (method_exists($cat, 'setParent')) {
                    $cat->setParent($course);
                } elseif (method_exists($cat, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                    $cat->setParentResourceNode($course->getResourceNode());
                }
                if (method_exists($cat, 'addCourseLink')) {
                    $cat->addCourseLink($course, $session);
                }

                $em->persist($cat);
                $catMapByOldId[(int) $oldId] = $cat;
                $newCats++;
            }
            if ($newCats > 0) {
                $em->flush();
            }

            // Persist selected links
            $newLinks = 0;
            foreach ($linksBucket as $L) {
                $url = trim((string) ($L->url ?? ''));
                if ('' === $url) {
                    continue;
                }

                $title = (string) ($L->title ?? '');
                if ('' === $title) {
                    $title = $url;
                }

                $link = (new CLink())
                    ->setUrl($url)
                    ->setTitle($title)
                    ->setDescription((string) ($L->description ?? ''))
                    ->setTarget((string) ($L->target ?? ''))
                ;

                if (method_exists($link, 'setParent')) {
                    $link->setParent($course);
                } elseif (method_exists($link, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                    $link->setParentResourceNode($course->getResourceNode());
                }
                if (method_exists($link, 'addCourseLink')) {
                    $link->addCourseLink($course, $session);
                }

                $oldCatId = (int) ($L->category_id ?? 0);
                if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                    $link->setCategory($catMapByOldId[$oldCatId]);
                }

                $em->persist($link);
                $newLinks++;
            }

            $em->flush();

            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreLinks]: persisted (snapshot)='.
                    json_encode(['cats' => $newCats, 'links' => $newLinks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return ['categories' => $newCats, 'links' => $newLinks];
        }

        // Extract & open main XML
        [$workDir] = $this->extractToTemp($archivePath);

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        // Collect URL activities -> { name, url, category hints }
        $links = [];
        $categories = []; // oldCatId => ['title' => ...]
        foreach ($mb->query('//activity') as $node) {
            /** @var DOMElement $node */
            $modName = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            if ('url' !== $modName) {
                continue;
            }

            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $moduleXml = ('' !== $dir) ? $workDir.'/'.$dir.'/url.xml' : null;
            if (!$moduleXml || !is_file($moduleXml)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: skip url (url.xml not found)');
                }

                continue;
            }

            $u = $this->readUrlModule($moduleXml);

            $urlVal = trim((string) ($u['url'] ?? ''));
            if ('' === $urlVal) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: skip url (empty externalurl)');
                }

                continue;
            }

            $oldCatId = (int) ($u['category_id'] ?? 0);
            $oldCatTitle = (string) ($u['category_title'] ?? '');
            if ($oldCatId > 0 && !isset($categories[$oldCatId])) {
                $categories[$oldCatId] = [
                    'title' => ('' !== $oldCatTitle ? $oldCatTitle : ('Category '.$oldCatId)),
                    'description' => '',
                ];
            }

            $links[] = [
                'name' => (string) ($u['name'] ?? ''),
                'url' => $urlVal,
                'description' => '',
                'target' => '',
                'old_cat_id' => $oldCatId,
            ];
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreLinks]: to_persist='.
                json_encode(['cats' => \count($categories), 'links' => \count($links)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if (empty($links) && empty($categories)) {
            return ['categories' => 0, 'links' => 0];
        }

        // Helper: robustly resolve an IID as int after flush
        $resolveIid = static function ($entity): int {
            // try entity->getIid()
            if (method_exists($entity, 'getIid')) {
                $iid = $entity->getIid();
                if (\is_int($iid)) {
                    return $iid;
                }
                if (is_numeric($iid)) {
                    return (int) $iid;
                }
            }
            // fallback: resource node iid
            if (method_exists($entity, 'getResourceNode')) {
                $node = $entity->getResourceNode();
                if ($node && method_exists($node, 'getIid')) {
                    $nid = $node->getIid();
                    if (\is_int($nid)) {
                        return $nid;
                    }
                    if (is_numeric($nid)) {
                        return (int) $nid;
                    }
                }
            }
            // last resort: primary ID
            if (method_exists($entity, 'getId')) {
                $id = $entity->getId();
                if (\is_int($id)) {
                    return $id;
                }
                if (is_numeric($id)) {
                    return (int) $id;
                }
            }

            return 0;
        };

        // Persist categories first -> flush -> refresh -> map iid
        $catMapByOldId = [];   // oldCatId => CLinkCategory entity
        $iidMapByOldId = [];   // oldCatId => int iid
        $newCats = 0;

        foreach ($categories as $oldId => $payload) {
            $cat = (new CLinkCategory())
                ->setTitle((string) $payload['title'])
                ->setDescription((string) $payload['description'])
            ;

            // Parent & course/session links BEFORE persist (prePersist needs a parent)
            if (method_exists($cat, 'setParent')) {
                $cat->setParent($course);
            } elseif (method_exists($cat, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                $cat->setParentResourceNode($course->getResourceNode());
            }
            if (method_exists($cat, 'addCourseLink')) {
                $cat->addCourseLink($course, $session);
            }

            $em->persist($cat);
            $catMapByOldId[(int) $oldId] = $cat;
            $newCats++;
        }

        // Flush categories to get identifiers assigned
        if ($newCats > 0) {
            $em->flush();
            // Refresh & resolve iid
            foreach ($catMapByOldId as $oldId => $cat) {
                $em->refresh($cat);
                $iidMapByOldId[$oldId] = $resolveIid($cat);
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: category persisted {old='.$oldId.', iid='.$iidMapByOldId[$oldId].', title='.$cat->getTitle().'}');
                }
            }
        }

        // Persist links (single flush at the end)
        $newLinks = 0;
        foreach ($links as $L) {
            $url = trim((string) $L['url']);
            if ('' === $url) {
                continue;
            }

            $title = (string) ($L['name'] ?? '');
            if ('' === $title) {
                $title = $url;
            }

            $link = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription((string) ($L['description'] ?? ''))
                ->setTarget((string) ($L['target'] ?? ''))
            ;

            // Parent & course/session links
            if (method_exists($link, 'setParent')) {
                $link->setParent($course);
            } elseif (method_exists($link, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                $link->setParentResourceNode($course->getResourceNode());
            }
            if (method_exists($link, 'addCourseLink')) {
                $link->addCourseLink($course, $session);
            }

            // Attach category if it existed in Moodle
            $oldCatId = (int) ($L['old_cat_id'] ?? 0);
            if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                $link->setCategory($catMapByOldId[$oldCatId]);
            }

            $em->persist($link);
            $newLinks++;
        }

        $em->flush();

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreLinks]: persisted='.
                json_encode(['cats' => $newCats, 'links' => $newLinks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return ['categories' => $newCats, 'links' => $newLinks];
    }

    /**
     * Fast-path: persist only Forum Categories and Forums from a Moodle backup,
     * wiring proper parents and course/session links with Doctrine entities.
     *
     * @return array{categories:int,forums:int}
     */
    public function restoreForums(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        /** @var CourseEntity|null $course */
        $course = $em->getRepository(CourseEntity::class)->find($courseRealId);
        if (!$course) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        /** @var SessionEntity|null $session */
        $session = $sessionId > 0
            ? $em->getRepository(SessionEntity::class)->find($sessionId)
            : null;

        // Fast-path: use filtered snapshot if provided (import/resources selection)
        if ($courseArg && isset($courseArg->resources) && \is_array($courseArg->resources)) {
            $forumsBucket = (array) ($courseArg->resources['forum'] ?? []);
            $catsBucket = (array) ($courseArg->resources['Forum_Category'] ?? []);

            if (empty($forumsBucket)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreForums]: snapshot has no selected forums');
                }

                return ['categories' => 0, 'forums' => 0];
            }

            // Categories actually referenced by selected forums
            $usedCatIds = [];
            foreach ($forumsBucket as $F) {
                $oldCatId = (int) ($F->forum_category ?? 0);
                if ($oldCatId > 0) {
                    $usedCatIds[$oldCatId] = true;
                }
            }

            // Persist only needed categories
            $catMapByOldId = [];
            $newCats = 0;
            foreach ($catsBucket as $oldId => $C) {
                if (!isset($usedCatIds[$oldId])) {
                    continue;
                }

                $cat = (new CForumCategory())
                    ->setTitle((string) ($C->cat_title ?? $C->title ?? ('Category '.$oldId)))
                    ->setCatComment((string) ($C->cat_comment ?? $C->description ?? ''))
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $em->persist($cat);
                $catMapByOldId[(int) $oldId] = $cat;
                $newCats++;
            }
            if ($newCats > 0) {
                $em->flush();
            }

            // Fallback default category if none referenced
            $defaultCat = null;
            $ensureDefault = function () use (&$defaultCat, $course, $session, $em): CForumCategory {
                if ($defaultCat instanceof CForumCategory) {
                    return $defaultCat;
                }
                $defaultCat = (new CForumCategory())
                    ->setTitle('General')
                    ->setCatComment('')
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $em->persist($defaultCat);
                $em->flush();

                return $defaultCat;
            };

            // Persist selected forums
            $newForums = 0;
            foreach ($forumsBucket as $F) {
                $title = (string) ($F->forum_title ?? $F->title ?? 'Forum');
                $comment = (string) ($F->forum_comment ?? $F->description ?? '');

                $dstCategory = null;
                $oldCatId = (int) ($F->forum_category ?? 0);
                if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                    $dstCategory = $catMapByOldId[$oldCatId];
                } elseif (1 === \count($catMapByOldId)) {
                    $dstCategory = reset($catMapByOldId);
                } else {
                    $dstCategory = $ensureDefault();
                }

                $forum = (new CForum())
                    ->setTitle($title)
                    ->setForumComment($comment)
                    ->setForumCategory($dstCategory)
                    ->setAllowAttachments(1)
                    ->setAllowNewThreads(1)
                    ->setDefaultView('flat')
                    ->setParent($dstCategory)
                    ->addCourseLink($course, $session)
                ;

                $em->persist($forum);
                $newForums++;
            }

            $em->flush();

            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreForums]: persisted (snapshot) cats='.$newCats.' forums='.$newForums);
            }

            return ['categories' => $newCats + ($defaultCat ? 1 : 0), 'forums' => $newForums];
        }

        [$workDir] = $this->extractToTemp($archivePath);

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        $forums = [];
        $categories = [];
        foreach ($mb->query('//activity') as $node) {
            /** @var DOMElement $node */
            $modName = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            if ('forum' !== $modName) {
                continue;
            }

            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $moduleXml = ('' !== $dir) ? $workDir.'/'.$dir.'/forum.xml' : null;
            if (!$moduleXml || !is_file($moduleXml)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreForums]: skip (forum.xml not found)');
                }

                continue;
            }

            $f = $this->readForumModule($moduleXml);

            $oldCatId = (int) ($f['category_id'] ?? 0);
            $oldCatTitle = (string) ($f['category_title'] ?? '');
            if ($oldCatId > 0 && !isset($categories[$oldCatId])) {
                $categories[$oldCatId] = [
                    'title' => ('' !== $oldCatTitle ? $oldCatTitle : ('Category '.$oldCatId)),
                    'description' => '',
                ];
            }

            $forums[] = [
                'name' => (string) ($f['name'] ?? 'Forum'),
                'description' => (string) ($f['description'] ?? ''),
                'type' => (string) ($f['type'] ?? 'general'),
                'old_cat_id' => $oldCatId,
            ];
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreForums]: found forums='.\count($forums).' cats='.\count($categories));
        }

        if (empty($forums) && empty($categories)) {
            return ['categories' => 0, 'forums' => 0];
        }

        $catMapByOldId = []; // oldCatId => CForumCategory
        $newCats = 0;

        foreach ($categories as $oldId => $payload) {
            $cat = (new CForumCategory())
                ->setTitle((string) $payload['title'])
                ->setCatComment((string) $payload['description'])
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $em->persist($cat);
            $catMapByOldId[(int) $oldId] = $cat;
            $newCats++;
        }
        if ($newCats > 0) {
            $em->flush();
        }

        $defaultCat = null;
        $ensureDefault = function () use (&$defaultCat, $course, $session, $em): CForumCategory {
            if ($defaultCat instanceof CForumCategory) {
                return $defaultCat;
            }
            $defaultCat = (new CForumCategory())
                ->setTitle('General')
                ->setCatComment('')
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $em->persist($defaultCat);
            $em->flush();

            return $defaultCat;
        };

        $newForums = 0;

        foreach ($forums as $F) {
            $title = (string) ($F['name'] ?? 'Forum');
            $comment = (string) ($F['description'] ?? '');

            $dstCategory = null;
            $oldCatId = (int) ($F['old_cat_id'] ?? 0);
            if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                $dstCategory = $catMapByOldId[$oldCatId];
            } elseif (1 === \count($catMapByOldId)) {
                $dstCategory = reset($catMapByOldId);
            } else {
                $dstCategory = $ensureDefault();
            }

            $forum = (new CForum())
                ->setTitle($title)
                ->setForumComment($comment)
                ->setForumCategory($dstCategory)
                ->setAllowAttachments(1)
                ->setAllowNewThreads(1)
                ->setDefaultView('flat')
                ->setParent($dstCategory)
                ->addCourseLink($course, $session)
            ;

            $em->persist($forum);
            $newForums++;
        }

        $em->flush();

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreForums]: persisted cats='.$newCats.' forums='.$newForums);
        }

        return ['categories' => $newCats, 'forums' => $newForums];
    }

    /**
     * Fast-path: restore only Documents from a Moodle backup, wiring ResourceFiles directly.
     * CHANGE: We already normalize paths and explicitly strip a leading "Documents/" segment,
     * so the Moodle top-level "Documents" folder is treated as the document root in Chamilo.
     */
    public function restoreDocuments(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        int $sameFileNameOption = 2,
        ?object $courseArg = null
    ): array {
        // Use filtered snapshot if provided; otherwise build from archive
        $legacy = $courseArg ?: $this->buildLegacyCourseFromMoodleArchive($archivePath);

        if (!\defined('FILE_SKIP')) {
            \define('FILE_SKIP', 1);
        }
        if (!\defined('FILE_RENAME')) {
            \define('FILE_RENAME', 2);
        }
        if (!\defined('FILE_OVERWRITE')) {
            \define('FILE_OVERWRITE', 3);
        }
        $filePolicy = \in_array($sameFileNameOption, [1, 2, 3], true) ? $sameFileNameOption : FILE_RENAME;

        /** @var CDocumentRepository $docRepo */
        $docRepo = Container::getDocumentRepository();
        $courseEntity = api_get_course_entity($courseRealId);
        $sessionEntity = api_get_session_entity((int) $sessionId);
        $groupEntity = api_get_group_entity(0);

        if (!$courseEntity) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        $srcRoot = rtrim((string) ($legacy->backup_path ?? ''), '/').'/';
        if (!is_dir($srcRoot)) {
            throw new RuntimeException('Moodle working directory not found: '.$srcRoot);
        }

        $docs = [];
        if (!empty($legacy->resources['document']) && \is_array($legacy->resources['document'])) {
            $docs = $legacy->resources['document'];
        } elseif (!empty($legacy->resources['Document']) && \is_array($legacy->resources['Document'])) {
            $docs = $legacy->resources['Document'];
        }
        if (empty($docs)) {
            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreDocuments]: no document bucket found');
            }

            return ['documents' => 0, 'folders' => 0];
        }

        $courseInfo = api_get_course_info();
        $courseDir = (string) ($courseInfo['directory'] ?? $courseInfo['code'] ?? '');

        $DBG = function (string $msg, array $ctx = []): void {
            error_log('[MOODLE_IMPORT:RESTORE_DOCS] '.$msg.(empty($ctx) ? '' : ' '.json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        };

        // Path normalizer: strip moodle-specific top-level segments like t/, moodle_pages/, Documents/
        // NOTE: This is what makes "Documents" behave as root in Chamilo.
        $normalizeMoodleRel = static function (string $rawPath): string {
            $p = ltrim($rawPath, '/');

            // Drop "document/" prefix if present
            if (str_starts_with($p, 'document/')) {
                $p = substr($p, 9);
            }

            // Strip known moodle export prefixes (order matters: most specific first)
            $strip = ['t/', 'moodle_pages/', 'Documents/'];
            foreach ($strip as $pre) {
                if (str_starts_with($p, $pre)) {
                    $p = substr($p, \strlen($pre));
                }
            }

            $p = ltrim($p, '/');

            return '' === $p ? '/' : '/'.$p;
        };

        $isFolderItem = static function (object $item): bool {
            $e = (isset($item->obj) && \is_object($item->obj)) ? $item->obj : $item;
            $ft = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
            if ('folder' === $ft) {
                return true;
            }
            $p = (string) ($e->path ?? '');

            return '' !== $p && '/' === substr($p, -1);
        };
        $effectiveEntity = static function (object $item): object {
            return (isset($item->obj) && \is_object($item->obj)) ? $item->obj : $item;
        };

        // Ensure folder chain and return destination parent iid
        $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $sessionId, $DBG) {
            $rel = '/'.ltrim($relPath, '/');
            if ('/' === $rel || '' === $rel) {
                return 0;
            }
            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));

            // If first segment is "document", skip it; we are already under the course document root.
            $start = (isset($parts[0]) && 'document' === strtolower($parts[0])) ? 1 : 0;

            $accum = '';
            $parentId = 0;
            for ($i = $start; $i < \count($parts); $i++) {
                $seg = $parts[$i];
                $accum = $accum.'/'.$seg;
                $title = $seg;
                $parent = $parentId ? $docRepo->find($parentId) : $courseEntity;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parent->getResourceNode(),
                    $courseEntity,
                    api_get_session_entity((int) $sessionId),
                    api_get_group_entity(0)
                );

                if ($existing) {
                    $parentId = method_exists($existing, 'getIid') ? (int) $existing->getIid() : 0;

                    continue;
                }

                $entity = DocumentManager::addDocument(
                    ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                    $accum,
                    'folder',
                    0,
                    $title,
                    null,
                    0,
                    null,
                    0,
                    (int) $sessionId,
                    0,
                    false,
                    '',
                    $parentId,
                    ''
                );
                $parentId = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
                $DBG('ensureFolder:create', ['accum' => $accum, 'iid' => $parentId]);
            }

            return $parentId;
        };

        $isHtmlFile = static function (string $filePath, string $nameGuess): bool {
            $ext1 = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($nameGuess, PATHINFO_EXTENSION));
            if (\in_array($ext1, ['html', 'htm'], true) || \in_array($ext2, ['html', 'htm'], true)) {
                return true;
            }
            $peek = (string) @file_get_contents($filePath, false, null, 0, 2048);
            if ('' === $peek) {
                return false;
            }
            $s = strtolower($peek);
            if (str_contains($s, '<html') || str_contains($s, '<!doctype html')) {
                return true;
            }
            if (\function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) {
                    $mt = @finfo_buffer($fi, $peek) ?: '';
                    finfo_close($fi);
                    if (str_starts_with($mt, 'text/html')) {
                        return true;
                    }
                }
            }

            return false;
        };

        // Create folders (preserve tree) with normalized paths; track destination iids
        $folders = []; // map: normalized folder rel -> iid
        $nFolders = 0;

        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if (!$isFolderItem($wrap)) {
                continue;
            }

            $rawPath = (string) ($e->path ?? '');
            if ('' === $rawPath) {
                continue;
            }

            // Normalize to avoid 't/', 'moodle_pages/', 'Documents/' phantom roots
            $rel = $normalizeMoodleRel($rawPath);
            if ('/' === $rel) {
                continue;
            }

            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
            $accum = '';
            $parentId = 0;

            foreach ($parts as $i => $seg) {
                $accum .= '/'.$seg;
                if (isset($folders[$accum])) {
                    $parentId = $folders[$accum];

                    continue;
                }

                $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $title = ($i === \count($parts) - 1) ? ((string) ($e->title ?? $seg)) : $seg;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity
                );

                if ($existing) {
                    $iid = method_exists($existing, 'getIid') ? (int) $existing->getIid() : 0;
                    $DBG('folder:reuse', ['title' => $title, 'iid' => $iid]);
                } else {
                    $entity = DocumentManager::addDocument(
                        ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                        $accum,
                        'folder',
                        0,
                        $title,
                        null,
                        0,
                        null,
                        0,
                        (int) $sessionId,
                        0,
                        false,
                        '',
                        $parentId,
                        ''
                    );
                    $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
                    $DBG('folder:create', ['title' => $title, 'iid' => $iid]);
                    $nFolders++;
                }

                $folders[$accum] = $iid;
                $parentId = $iid;
            }

            if (isset($legacy->resources['document'][$k])) {
                $legacy->resources['document'][$k]->destination_id = $parentId;
            }
        }

        // PRE-SCAN: build URL maps for HTML rewriting if helpers exist
        $urlMapByRel = [];
        $urlMapByBase = [];
        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if ($isFolderItem($wrap)) {
                continue;
            }

            $title = (string) ($e->title ?? basename((string) $e->path));
            $src = $srcRoot.(string) $e->path;

            if (!is_file($src) || !is_readable($src)) {
                continue;
            }
            if (!$isHtmlFile($src, $title)) {
                continue;
            }

            $html = (string) @file_get_contents($src);
            if ('' === $html) {
                continue;
            }

            try {
                $maps = ChamiloHelper::buildUrlMapForHtmlFromPackage(
                    $html,
                    $courseDir,
                    $srcRoot,
                    $folders,
                    $ensureFolder,
                    $docRepo,
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity,
                    (int) $sessionId,
                    (int) $filePolicy,
                    $DBG
                );

                foreach ($maps['byRel'] ?? [] as $kRel => $vUrl) {
                    if (!isset($urlMapByRel[$kRel])) {
                        $urlMapByRel[$kRel] = $vUrl;
                    }
                }
                foreach ($maps['byBase'] ?? [] as $kBase => $vUrl) {
                    if (!isset($urlMapByBase[$kBase])) {
                        $urlMapByBase[$kBase] = $vUrl;
                    }
                }
            } catch (Throwable $te) {
                $DBG('html:map:failed', ['err' => $te->getMessage()]);
            }
        }
        $DBG('global.map.stats', ['byRel' => \count($urlMapByRel), 'byBase' => \count($urlMapByBase)]);

        // Import files (HTML rewritten before addDocument; binaries via realPath)
        $nFiles = 0;
        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if ($isFolderItem($wrap)) {
                continue;
            }

            $rawTitle = (string) ($e->title ?? basename((string) $e->path));
            $srcPath = $srcRoot.(string) $e->path;

            if (!is_file($srcPath) || !is_readable($srcPath)) {
                $DBG('file:skip:src-missing', ['src' => $srcPath, 'title' => $rawTitle]);

                continue;
            }

            // Parent folder: from normalized path (this strips "Documents/")
            $rel = $normalizeMoodleRel((string) $e->path);
            $parentRel = rtrim(\dirname($rel), '/');
            $parentId = $folders[$parentRel] ?? 0;
            if (!$parentId) {
                $parentId = $ensureFolder($parentRel);
                $folders[$parentRel] = $parentId;
            }
            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

            // Handle name collisions based on $filePolicy
            $findExistingIid = function (string $title) use ($docRepo, $parentRes, $courseEntity, $sessionEntity, $groupEntity): ?int {
                $ex = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity
                );

                return $ex && method_exists($ex, 'getIid') ? (int) $ex->getIid() : null;
            };

            $baseTitle = $rawTitle;
            $finalTitle = $baseTitle;

            $existsIid = $findExistingIid($finalTitle);
            if ($existsIid) {
                $DBG('file:collision', ['title' => $finalTitle, 'policy' => $filePolicy]);
                if (FILE_SKIP === $filePolicy) {
                    if (isset($legacy->resources['document'][$k])) {
                        $legacy->resources['document'][$k]->destination_id = $existsIid;
                    }

                    continue;
                }
                if (FILE_RENAME === $filePolicy) {
                    $pi = pathinfo($baseTitle);
                    $name = $pi['filename'] ?? $baseTitle;
                    $ext2 = isset($pi['extension']) && '' !== $pi['extension'] ? '.'.$pi['extension'] : '';
                    $i = 1;
                    while ($findExistingIid($finalTitle)) {
                        $finalTitle = $name.'_'.$i.$ext2;
                        $i++;
                    }
                }
                // FILE_OVERWRITE => let DocumentManager handle it
            }

            // Prepare payload for addDocument
            $isHtml = $isHtmlFile($srcPath, $rawTitle);
            $content = '';
            $realPath = '';

            if ($isHtml) {
                $raw = @file_get_contents($srcPath) ?: '';
                if (\defined('UTF8_CONVERT') && UTF8_CONVERT) {
                    $raw = utf8_encode($raw);
                }
                $DBG('html:rewrite:before', ['title' => $finalTitle, 'maps' => [\count($urlMapByRel), \count($urlMapByBase)]]);

                try {
                    $rew = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
                        $raw,
                        $courseDir,
                        $urlMapByRel,
                        $urlMapByBase
                    );
                    $content = (string) ($rew['html'] ?? $raw);
                    $DBG('html:rewrite:after', ['replaced' => (int) ($rew['replaced'] ?? 0), 'misses' => (int) ($rew['misses'] ?? 0)]);
                } catch (Throwable $te) {
                    $content = $raw; // fallback to original HTML
                    $DBG('html:rewrite:error', ['err' => $te->getMessage()]);
                }
            } else {
                $realPath = $srcPath; // binary: pass physical path to be streamed into ResourceFile
            }

            try {
                $entity = DocumentManager::addDocument(
                    ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                    $rel,
                    'file',
                    (int) ($e->size ?? 0),
                    $finalTitle,
                    (string) ($e->comment ?? ''),
                    0,
                    null,
                    0,
                    (int) $sessionId,
                    0,
                    false,
                    $content,
                    $parentId,
                    $realPath
                );
                $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;

                if (isset($legacy->resources['document'][$k])) {
                    $legacy->resources['document'][$k]->destination_id = $iid;
                }

                $nFiles++;
                $DBG('file:created', ['title' => $finalTitle, 'iid' => $iid, 'html' => $isHtml ? 1 : 0]);
            } catch (Throwable $eX) {
                $DBG('file:create:failed', ['title' => $finalTitle, 'error' => $eX->getMessage()]);
            }
        }

        $DBG('summary', ['files' => $nFiles, 'folders' => $nFolders]);

        return ['documents' => $nFiles, 'folders' => $nFolders];
    }

    /**
     * Read documents from activities/resource + files.xml and populate $resources['document'].
     * NEW behavior:
     * - Treat Moodle's top-level "Documents" folder as the ROOT of /document (do NOT create a "Documents" node).
     * - Preserve any real subfolders beneath "Documents/".
     * - Copies blobs from files/<hash> to the target /document/... path
     * - Adds LP items when section map exists.
     */
    private function readDocuments(
        string $workDir,
        DOMXPath $mb,
        array $fileIndex,
        array &$resources,
        array &$lpMap
    ): void {
        $resources['document'] ??= [];

        // Ensure physical /document dir exists in the working dir (snapshot points there).
        $this->ensureDir($workDir.'/document');

        // Helper: strip an optional leading "/Documents" segment *once*
        $stripDocumentsRoot = static function (string $p): string {
            $p = '/'.ltrim($p, '/');
            if (preg_match('~^/Documents(/|$)~i', $p)) {
                $p = substr($p, \strlen('/Documents'));
                if (false === $p) {
                    $p = '/';
                }
            }

            return '' === $p ? '/' : $p;
        };

        // Small helper: ensure folder chain (legacy snapshot + filesystem) under /document,
        // skipping an initial "Documents" segment if present.
        $ensureFolderChain = function (string $base, string $fp) use (&$resources, $workDir, $stripDocumentsRoot): string {
            // Normalize base and fp
            $base = rtrim($base, '/');               // expected "/document"
            $fp = $this->normalizeSlash($fp ?: '/'); // "/sub/dir/" or "/"
            $fp = $stripDocumentsRoot($fp);

            if ('/' === $fp || '' === $fp) {
                // Just the base /document
                $this->ensureDir($workDir.$base);

                return $base;
            }

            // Split and ensure each segment (both on disk and in legacy snapshot)
            $parts = array_values(array_filter(explode('/', trim($fp, '/'))));
            $accRel = $base;
            foreach ($parts as $seg) {
                $accRel .= '/'.$seg;
                // Create on disk
                $this->ensureDir($workDir.$accRel);
                // Create in legacy snapshot as a folder node (idempotent)
                $this->ensureFolderLegacy($resources['document'], $accRel, $seg);
            }

            return $accRel; // final parent folder rel path (under /document)
        };

        // A) Restore "resource" activities (single-file resources)
        foreach ($mb->query('//activity[modulename="resource"]') as $node) {
            /** @var DOMElement $node */
            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            if ('' === $dir) {
                continue;
            }

            $resourceXml = $workDir.'/'.$dir.'/resource.xml';
            $inforefXml = $workDir.'/'.$dir.'/inforef.xml';
            if (!is_file($resourceXml) || !is_file($inforefXml)) {
                continue;
            }

            // 1) Read resource name/intro
            [$resName, $resIntro] = $this->readResourceMeta($resourceXml);

            // 2) Resolve referenced file ids
            $fileIds = $this->parseInforefFileIds($inforefXml);
            if (empty($fileIds)) {
                continue;
            }

            foreach ($fileIds as $fid) {
                $f = $fileIndex['byId'][$fid] ?? null;
                if (!$f) {
                    continue;
                }

                // Keep original structure from files.xml under /document (NOT /document/Documents)
                $fp = $this->normalizeSlash($f['filepath'] ?? '/'); // e.g. "/sub/dir/"
                $fp = $stripDocumentsRoot($fp);
                $base = '/document'; // root in Chamilo
                $parentRel = $ensureFolderChain($base, $fp);

                $fileName = ltrim((string) ($f['filename'] ?? ''), '/');
                if ('' === $fileName) {
                    $fileName = 'file_'.$fid;
                }
                $targetRel = rtrim($parentRel, '/').'/'.$fileName;
                $targetAbs = $workDir.$targetRel;

                // Copy binary into working dir
                $this->ensureDir(\dirname($targetAbs));
                $this->safeCopy($f['blob'], $targetAbs);

                // Register in legacy snapshot
                $docId = $this->nextId($resources['document']);
                $resources['document'][$docId] = $this->mkLegacyItem(
                    'document',
                    $docId,
                    [
                        'file_type' => 'file',
                        'path' => $targetRel,
                        'title' => ('' !== $resName ? $resName : (string) $fileName),
                        'comment' => $resIntro,
                        'size' => (string) ($f['filesize'] ?? 0),
                    ]
                );

                // Add to LP of the section, if present (keeps current behavior)
                $sectionId = (int) ($node->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);
                if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                    $resourcesDocTitle = $resources['document'][$docId]->title ?? (string) $fileName;
                    $lpMap[$sectionId]['items'][] = [
                        'item_type' => 'document',
                        'ref' => $docId,
                        'title' => $resourcesDocTitle,
                    ];
                }
            }
        }

        // B) Restore files that belong to mod_folder activities.
        foreach ($fileIndex['byId'] as $f) {
            if (($f['component'] ?? '') !== 'mod_folder') {
                continue;
            }

            // Keep inner structure from files.xml under /document; strip leading "Documents/"
            $fp = $this->normalizeSlash($f['filepath'] ?? '/'); // e.g. "/unit1/slide/"
            $fp = $stripDocumentsRoot($fp);
            $base = '/document';

            // Ensure folder chain exists on disk and in legacy map; get parent rel
            $parentRel = $ensureFolderChain($base, $fp);

            // Final rel path for the file
            $fileName = ltrim((string) ($f['filename'] ?? ''), '/');
            if ('' === $fileName) {
                // Defensive: generate name if missing (rare, but keeps import resilient)
                $fileName = 'file_'.$this->nextId($resources['document']);
            }
            $rel = rtrim($parentRel, '/').'/'.$fileName;

            // Copy to working dir
            $abs = $workDir.$rel;
            $this->ensureDir(\dirname($abs));
            $this->safeCopy($f['blob'], $abs);

            // Register the file in legacy snapshot (folder nodes were created by ensureFolderChain)
            $docId = $this->nextId($resources['document']);
            $resources['document'][$docId] = $this->mkLegacyItem(
                'document',
                $docId,
                [
                    'file_type' => 'file',
                    'path' => $rel,
                    'title' => (string) ($fileName ?: 'file '.$docId),
                    'size' => (string) ($f['filesize'] ?? 0),
                    'comment' => '',
                ]
            );
        }
    }

    /**
     * Extract resource name and intro from activities/resource/resource.xml.
     */
    private function readResourceMeta(string $resourceXml): array
    {
        $doc = $this->loadXml($resourceXml);
        $xp = new DOMXPath($doc);
        $name = (string) ($xp->query('//resource/name')->item(0)?->nodeValue ?? '');
        $intro = (string) ($xp->query('//resource/intro')->item(0)?->nodeValue ?? '');

        return [$name, $intro];
    }

    /**
     * Parse file ids referenced by inforef.xml (<inforef><fileref><file><id>..</id>).
     */
    private function parseInforefFileIds(string $inforefXml): array
    {
        $doc = $this->loadXml($inforefXml);
        $xp = new DOMXPath($doc);
        $ids = [];
        foreach ($xp->query('//inforef/fileref/file/id') as $n) {
            $v = (int) ($n->nodeValue ?? 0);
            if ($v > 0) {
                $ids[] = $v;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Create (if missing) a legacy folder entry at $folderPath in $bucket and return its id.
     */
    private function ensureFolderLegacy(array &$bucket, string $folderPath, string $title): int
    {
        foreach ($bucket as $k => $it) {
            if (($it->file_type ?? '') === 'folder' && (($it->path ?? '') === $folderPath)) {
                return (int) $k;
            }
        }
        $id = $this->nextId($bucket);
        $bucket[$id] = $this->mkLegacyItem('document', $id, [
            'file_type' => 'folder',
            'path' => $folderPath,
            'title' => $title,
            'size' => '0',
        ]);

        return $id;
    }

    /**
     * Copy a file if present (tolerant if blob is missing).
     */
    private function safeCopy(string $src, string $dst): void
    {
        if (!is_file($src)) {
            if ($this->debug) {
                error_log('MOODLE_IMPORT: blob not found: '.$src);
            }

            return;
        }
        if (!is_file($dst)) {
            @copy($src, $dst);
        }
    }

    /**
     * Normalize a path to have single slashes and end with a slash.
     */
    private function normalizeSlash(string $p): string
    {
        if ('' === $p || '.' === $p) {
            return '/';
        }
        $p = preg_replace('#/+#', '/', $p);

        return rtrim($p, '/').'/';
    }

    private function mkLegacyItem(string $type, int $sourceId, array|object $obj, array $arrayKeysToPromote = []): stdClass
    {
        $o = new stdClass();
        $o->type = $type;
        $o->source_id = $sourceId;
        $o->destination_id = null;
        $o->has_obj = true;
        $o->obj = (object) $obj;

        if (!isset($o->obj->iid)) {
            $o->obj->iid = $sourceId;
        }
        if (!isset($o->id)) {
            $o->id = $sourceId;
        }
        if (!isset($o->obj->id)) {
            $o->obj->id = $sourceId;
        }

        // Promote scalars to top-level (like the builder)
        foreach ((array) $obj as $k => $v) {
            if (\is_scalar($v) || null === $v) {
                if (!property_exists($o, $k)) {
                    $o->{$k} = $v;
                }
            }
        }
        // Promote array keys (e.g., items, linked_resources in learnpath)
        foreach ($arrayKeysToPromote as $k) {
            if (isset($obj[$k]) && \is_array($obj[$k])) {
                $o->{$k} = $obj[$k];
            }
        }

        // Special adjustments for documents
        if ('document' === $type) {
            $o->path = (string) ($o->path ?? $o->full_path ?? $o->obj->path ?? $o->obj->full_path ?? '');
            $o->full_path = (string) ($o->full_path ?? $o->path ?? $o->obj->full_path ?? $o->obj->path ?? '');
            $o->file_type = (string) ($o->file_type ?? $o->filetype ?? $o->obj->file_type ?? $o->obj->filetype ?? '');
            $o->filetype = (string) ($o->filetype ?? $o->file_type ?? $o->obj->filetype ?? $o->obj->file_type ?? '');
            $o->title = (string) ($o->title ?? $o->obj->title ?? '');
            if (!isset($o->name) || '' === $o->name || null === $o->name) {
                $o->name = '' !== $o->title ? $o->title : ('document '.$sourceId);
            }
        }

        // Default name if missing
        if (!isset($o->name) || '' === $o->name || null === $o->name) {
            if (isset($obj['name']) && '' !== $obj['name']) {
                $o->name = (string) $obj['name'];
            } elseif (isset($obj['title']) && '' !== $obj['title']) {
                $o->name = (string) $obj['title'];
            } else {
                $o->name = $type.' '.$sourceId;
            }
        }

        return $o;
    }

    /**
     * Replace Moodle @@PLUGINFILE@@ placeholders with package-local /document/ URLs
     * so that later HTML URL mapping can resolve and rewire them correctly.
     * Examples:
     *   @@PLUGINFILE@@/Documents/foo.png  -> /document/foo.png
     *   @@PLUGINFILE@@/documents/bar.pdf  -> /document/bar.pdf
     */
    private function normalizePluginfileContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        // Case-insensitive replace; keep a single leading slash
        // Handles both Documents/ and documents/
        $html = preg_replace('~@@PLUGINFILE@@/(?:Documents|documents)/~i', '/document/', $html);

        return $html;
    }

    private function readCourseMeta(string $courseXmlPath): array
    {
        if (!is_file($courseXmlPath)) {
            return [];
        }
        $doc = $this->loadXml($courseXmlPath);
        $xp  = new DOMXPath($doc);

        $get = static function (string $q) use ($xp) {
            $n = $xp->query($q)->item(0);

            return $n ? (string) $n->nodeValue : '';
        };

        // Moodle course.xml typical nodes
        $fullname  = $get('//course/fullname');
        $shortname = $get('//course/shortname');
        $idnumber  = $get('//course/idnumber');
        $summary   = $get('//course/summary');
        $format    = $get('//course/format');

        $startdate = (int) ($get('//course/startdate') ?: 0);
        $enddate   = (int) ($get('//course/enddate')   ?: 0);

        return [
            'fullname'  => $fullname,
            'shortname' => $shortname,
            'idnumber'  => $idnumber,
            'summary'   => $summary,
            'format'    => $format,
            'startdate' => $startdate,
            'enddate'   => $enddate,
        ];
    }

    private function mergeMetaExportIfPresent(string $workDir, array $meta): array
    {
        $candidates = ['meta_export.json', 'export_meta.json', 'meta.json'];
        foreach ($candidates as $fn) {
            $p = rtrim($workDir,'/').'/'.$fn;
            if (is_file($p)) {
                $raw = @file_get_contents($p);
                if (false !== $raw && '' !== $raw) {
                    $j = json_decode($raw, true);
                    if (\is_array($j)) {
                        // shallow merge under 'metaexport'
                        $meta['metaexport'] = $j;
                    }
                }
                break;
            }
        }

        return $meta;
    }

    private function readQuizModule(string $workDir, string $dir, string $quizXmlPath): array
    {
        $doc = $this->loadXml($quizXmlPath);
        $xp  = new DOMXPath($doc);

        $name  = (string) ($xp->query('//quiz/name')->item(0)?->nodeValue ?? 'Quiz');
        $intro = (string) ($xp->query('//quiz/intro')->item(0)?->nodeValue ?? '');
        $timeopen  = (int) ($xp->query('//quiz/timeopen')->item(0)?->nodeValue ?? 0);
        $timeclose = (int) ($xp->query('//quiz/timeclose')->item(0)?->nodeValue ?? 0);
        $timelimit = (int) ($xp->query('//quiz/timelimit')->item(0)?->nodeValue ?? 0);

        $quiz = [
            'name'        => $name,
            'description' => $intro,
            'timeopen'    => $timeopen,
            'timeclose'   => $timeclose,
            'timelimit'   => $timelimit,
            'attempts'    => (int) ($xp->query('//quiz/attempts')->item(0)?->nodeValue ?? 0),
            'shuffle'     => (int) ($xp->query('//quiz/shufflequestions')->item(0)?->nodeValue ?? 0),
        ];

        // Question bank usually sits at $dir/questions.xml (varies by Moodle version)
        $qxml = $workDir.'/'.$dir.'/questions.xml';
        $questions = [];
        if (is_file($qxml)) {
            $qDoc = $this->loadXml($qxml);
            $qx   = new DOMXPath($qDoc);

            foreach ($qx->query('//question') as $qn) {
                /** @var DOMElement $qn */
                $qtype = strtolower((string) $qn->getAttribute('type'));
                $qname = (string) ($qn->getElementsByTagName('name')->item(0)?->getElementsByTagName('text')->item(0)?->nodeValue ?? '');
                $qtext = (string) ($qn->getElementsByTagName('questiontext')->item(0)?->getElementsByTagName('text')->item(0)?->nodeValue ?? '');

                $q = [
                    'type'       => $qtype ?: 'description',
                    'name'       => $qname ?: 'Question',
                    'questiontext' => $qtext,
                    'answers'    => [],
                    'defaultgrade' => (float) ($qn->getElementsByTagName('defaultgrade')->item(0)?->nodeValue ?? 1.0),
                    'single'     => null,
                    'correct'    => [],
                ];

                if ('multichoice' === $qtype) {
                    $single = (int) ($qn->getElementsByTagName('single')->item(0)?->nodeValue ?? 1);
                    $q['single'] = $single;

                    foreach ($qn->getElementsByTagName('answer') as $an) {
                        /** @var DOMElement $an */
                        $t = (string) ($an->getElementsByTagName('text')->item(0)?->nodeValue ?? '');
                        $f = (float) ($an->getAttribute('fraction') ?: 0);
                        $q['answers'][] = ['text' => $t, 'fraction' => $f];
                        if ($f > 0) {
                            $q['correct'][] = $t;
                        }
                    }
                } elseif ('truefalse' === $qtype) {
                    foreach ($qn->getElementsByTagName('answer') as $an) {
                        $t = (string) ($an->getElementsByTagName('text')->item(0)?->nodeValue ?? '');
                        $f = (float) ($an->getAttribute('fraction') ?: 0);
                        $q['answers'][] = ['text' => $t, 'fraction' => $f];
                        if ($f > 0) {
                            $q['correct'][] = $t;
                        }
                    }
                } // else: keep minimal info

                $questions[] = $q;
            }
        }

        return [$quiz, $questions];
    }

    private function readAssignModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp  = new DOMXPath($doc);

        $name     = (string) ($xp->query('//assign/name')->item(0)?->nodeValue ?? 'Assignment');
        $intro    = (string) ($xp->query('//assign/intro')->item(0)?->nodeValue ?? '');
        $duedate  = (int) ($xp->query('//assign/duedate')->item(0)?->nodeValue ?? 0);
        $allowsub = (int) ($xp->query('//assign/teamsubmission')->item(0)?->nodeValue ?? 0);

        return [
            'name'        => $name,
            'description' => $intro,
            'deadline'    => $duedate,
            'group'       => $allowsub,
        ];
    }

    private function readSurveyModule(string $xmlPath, string $type): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp  = new DOMXPath($doc);

        $name  = (string) ($xp->query("//{$type}/name")->item(0)?->nodeValue ?? ucfirst($type));
        $intro = (string) ($xp->query("//{$type}/intro")->item(0)?->nodeValue ?? '');

        return [
            'name'        => $name,
            'subtitle'    => '',
            'intro'       => $intro,
            'thanks'      => '',
            'survey_type' => $type,
        ];
    }

    private function readGlossaryModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp  = new DOMXPath($doc);

        $name  = (string) ($xp->query('//glossary/name')->item(0)?->nodeValue ?? 'Glossary');
        $intro = (string) ($xp->query('//glossary/intro')->item(0)?->nodeValue ?? '');

        $entries = [];
        foreach ($xp->query('//glossary/entries/entry') as $eNode) {
            /** @var DOMElement $eNode */
            $entryId    = (int) $eNode->getAttribute('id');
            $concept    = trim((string) ($xp->evaluate('string(concept)', $eNode) ?? ''));
            $definition = (string) ($xp->evaluate('string(definition)', $eNode) ?? '');
            $approved   = (int) $xp->evaluate('number(approved)', $eNode);
            $userId     = (int) $xp->evaluate('number(userid)', $eNode);
            $created    = (int) $xp->evaluate('number(timecreated)', $eNode);
            $modified   = (int) $xp->evaluate('number(timemodified)', $eNode);

            // Collect aliases
            $aliases = [];
            foreach ($xp->query('aliases/alias/alias_text', $eNode) as $aNode) {
                $aliases[] = (string) $aNode->nodeValue;
            }

            $entries[] = [
                'id'          => $entryId,
                'concept'     => $concept,
                'definition'  => $definition, // keep HTML; resolver for @@PLUGINFILE@@ can run later
                'approved'    => $approved ?: 1,
                'userid'      => $userId,
                'timecreated' => $created,
                'timemodified'=> $modified,
                'aliases'     => $aliases,
            ];
        }

        return [
            'name'        => $name,
            'description' => $intro,
            'entries'     => $entries,
        ];
    }

    /**
     * Read course-level events from /course/calendar.xml (as written by CourseExport).
     * Returns legacy-shaped payloads for the 'events' bag.
     *
     * @return array<int,array<string,mixed>>
     */
    private function readCourseEvents(string $workDir): array
    {
        $path = rtrim($workDir, '/').'/course/calendar.xml';
        if (!is_file($path)) {
            // No calendar file -> no events
            return [];
        }

        // Load XML safely
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $prev = libxml_use_internal_errors(true);
        $ok = @$doc->load($path);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$ok) {
            // Corrupted calendar.xml -> ignore
            return [];
        }

        $xp = new \DOMXPath($doc);
        $evNodes = $xp->query('/calendar/event');
        if (!$evNodes || $evNodes->length === 0) {
            return [];
        }

        $out = [];
        /** @var \DOMElement $ev */
        foreach ($evNodes as $ev) {
            $get = static function (\DOMElement $ctx, string $tag): ?string {
                $n = $ctx->getElementsByTagName($tag)->item(0);
                return $n ? (string) $n->nodeValue : null; // preserves CDATA inner content
            };

            // Fields per CourseExport::createCalendarXml()
            $name       = trim((string) ($get($ev, 'name') ?? ''));
            $desc       = (string) ($get($ev, 'description') ?? '');
            $timestartV = (string) ($get($ev, 'timestart') ?? '');
            $durationV  = (string) ($get($ev, 'duration') ?? '');
            $alldayV    = (string) ($get($ev, 'allday') ?? '');
            $visibleV   = (string) ($get($ev, 'visible') ?? '1');
            $eventtype  = (string) ($get($ev, 'eventtype') ?? 'course');
            $uuid       = (string) ($get($ev, 'uuid') ?? '');

            // Tolerant parsing: accept numeric or date-string (older/other writers)
            $toTs = static function (string $v, int $fallback = 0): int {
                if ($v === '') { return $fallback; }
                if (is_numeric($v)) { return (int) $v; }
                $t = @\strtotime($v);
                return $t !== false ? (int) $t : $fallback;
            };

            $timestart = $toTs($timestartV, time());
            $duration  = max(0, (int) $durationV);
            $allday    = (int) $alldayV ? 1 : 0;
            $visible   = (int) $visibleV ? 1 : 0;

            // Legacy-friendly payload (used by mkLegacyItem('events', ...))
            $payload = [
                'name'        => $name !== '' ? $name : 'Event',
                'description' => $desc,          // HTML allowed (comes from CDATA)
                'timestart'   => $timestart,     // Unix timestamp
                'duration'    => $duration,      // seconds
                'allday'      => $allday,        // 0/1
                'visible'     => $visible,       // 0/1
                'eventtype'   => $eventtype,     // 'course' by default
                'uuid'        => $uuid,          // $@NULL@$ or value
            ];

            $out[] = $payload;
        }

        return $out;
    }

    /**
     * Restore selected buckets using the generic CourseRestorer to persist them.
     * This lets us reuse the legacy snapshot you already build.
     */
    private function restoreWithRestorer(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId,
        array $allowedBuckets,          // ej: ['quizzes','quiz_question']
        ?object $courseArg = null
    ): array {
        $legacy = $courseArg ?: $this->buildLegacyCourseFromMoodleArchive($archivePath);
        $legacy->resources = isset($legacy->resources) && \is_array($legacy->resources)
            ? $this->canonicalizeResourceBags($legacy->resources)
            : [];

        $expanded = $this->expandBucketAliases($allowedBuckets);
        $legacy->resources = array_intersect_key(
            (array) $legacy->resources,
            array_flip($expanded)
        );

        $total = 0;
        foreach ($legacy->resources as $v) {
            $total += \is_array($v) ? \count($v) : 0;
        }
        if ($total === 0) {
            return ['imported' => 0, 'notes' => ['No resources to restore for '.implode(',', $allowedBuckets)]];
        }

        $restorerClass = '\\Chamilo\\CourseBundle\\Component\\CourseCopy\\CourseRestorer';
        if (!\class_exists($restorerClass)) {
            return ['imported' => 0, 'notes' => ['CourseRestorer not available']];
        }
        $restorer = new $restorerClass($em, $courseRealId, $sessionId);

        if (property_exists($restorer, 'course')) {
            $restorer->course = $legacy;
        } elseif (\method_exists($restorer, 'setCourse')) {
            $restorer->setCourse($legacy);
        }

        $destCode = '';
        $courseEntity = \function_exists('api_get_course_entity') ? api_get_course_entity($courseRealId) : null;
        if ($courseEntity && \method_exists($courseEntity, 'getCode')) {
            $destCode = (string) $courseEntity->getCode();
        } else {
            $ci = \function_exists('api_get_course_info_by_id') ? api_get_course_info_by_id($courseRealId) : null;
            if (\is_array($ci) && !empty($ci['code'])) {
                $destCode = (string) $ci['code'];
            }
        }

        if (\method_exists($restorer, 'restore')) {
            $restorer->restore($destCode, $sessionId, false, false);
        } else {
            return ['imported' => 0, 'notes' => ['No supported restore() method in CourseRestorer']];
        }

        return ['imported' => $total, 'notes' => ['Restored: '.implode(',', $expanded)]];
    }

    private function expandBucketAliases(array $buckets): array
    {
        $map = [
            'quizzes'        => ['quizzes', 'quiz', \defined('RESOURCE_QUIZ') ? (string) RESOURCE_QUIZ : 'quiz'],
            'quiz'           => ['quiz', 'quizzes', \defined('RESOURCE_QUIZ') ? (string) RESOURCE_QUIZ : 'quiz'],
            'quiz_question'  => ['quiz_question', 'Exercise_Question', \defined('RESOURCE_QUIZQUESTION') ? (string) RESOURCE_QUIZQUESTION : 'quiz_question'],
            'scorm'             => ['scorm', 'scorm_documents'],
            'scorm_documents'   => ['scorm_documents', 'scorm'],
            'document'       => ['document', 'Document'],
            'forum'          => ['forum'],
            'Forum_Category' => ['Forum_Category'],
            'link'           => ['link'],
            'Link_Category'  => ['Link_Category'],
            'learnpath'          => ['learnpath'],
            'learnpath_category' => ['learnpath_category'],
            'thematic'    => ['thematic', \defined('RESOURCE_THEMATIC') ? (string) RESOURCE_THEMATIC : 'thematic'],
            'attendance'  => ['attendance', \defined('RESOURCE_ATTENDANCE') ? (string) RESOURCE_ATTENDANCE : 'attendance'],
            'gradebook'   => ['gradebook', 'Gradebook', \defined('RESOURCE_GRADEBOOK') ? (string) RESOURCE_GRADEBOOK : 'gradebook'],
            'announcement' => array_values(array_unique(array_filter([
                'announcement',
                'news',
                \defined('RESOURCE_ANNOUNCEMENT') ? (string) RESOURCE_ANNOUNCEMENT : null,
            ]))),
            'news' => ['news', 'announcement'],
        ];

        $out = [];
        foreach ($buckets as $b) {
            $b = (string) $b;
            $out = array_merge($out, $map[$b] ?? [$b]);
        }

        return array_values(array_unique($out));
    }

    /**
     * Convenience: restore a set of specific buckets using the generic CourseRestorer.
     * Keeps all logic inside MoodleImport (no new classes).
     *
     * @param string[] $buckets
     * @return array{imported:int,notes:array}
     */
    public function restoreSelectedBuckets(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        array $buckets = [],
        ?object $courseArg = null
    ): array {
        if (empty($buckets)) {
            return ['imported' => 0, 'notes' => ['No buckets requested']];
        }

        // Delegate to the generic restorer while filtering the snapshot to the requested buckets.
        return $this->restoreWithRestorer(
            $archivePath,
            $em,
            $courseRealId,
            $sessionId,
            $buckets,
            $courseArg
        );
    }

    /** Quizzes (+ question bank minimal snapshot) */
    public function restoreQuizzes(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        return $this->restoreSelectedBuckets(
            $archivePath, $em, $courseRealId, $sessionId,
            ['quizzes', 'quiz_question'],
            $courseArg
        );
    }

    /** SCORM packages */
    public function restoreScorm(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        // Keep both keys for maximum compatibility with CourseRestorer implementations
        return $this->restoreSelectedBuckets(
            $archivePath, $em, $courseRealId, $sessionId,
            ['scorm_documents', 'scorm'],
            $courseArg
        );
    }

    /**
     * Preferred Learnpath importer using Chamilo sidecar JSON under chamilo/learnpath.
     * Returns true if LPs (and categories) were imported from meta.
     */
    private function tryImportLearnpathMeta(string $workDir, array &$resources): bool
    {
        $base = rtrim($workDir, '/').'/chamilo/learnpath';
        $indexFile = $base.'/index.json';
        if (!is_file($indexFile)) {
            return false; // No meta present -> fallback to sections
        }

        $index = $this->readJsonFile($indexFile);
        $cats  = $this->readJsonFile($base.'/categories.json');

        // 1) Ensure learnpath_category from meta (idempotent)
        $existingCatIds = array_map('intval', array_keys((array) ($resources['learnpath_category'] ?? [])));
        foreach ((array) ($cats['categories'] ?? []) as $c) {
            $cid   = (int) ($c['id'] ?? 0);
            $title = (string) ($c['title'] ?? '');
            if ($cid <= 0) { continue; }
            if (!\in_array($cid, $existingCatIds, true)) {
                // Preserve category id from meta to simplify mapping
                $resources['learnpath_category'][$cid] = $this->mkLegacyItem('learnpath_category', $cid, [
                    'id'    => $cid,
                    'name'  => $title,
                    'title' => $title,
                ]);
                $existingCatIds[] = $cid;
            }
        }

        // 2) Build search indexes to resolve item "ref" into our freshly-built resource IDs
        $idx = $this->buildResourceIndexes($resources);

        // 3) Import learnpaths
        $imported = 0;
        foreach ((array) ($index['learnpaths'] ?? []) as $row) {
            $dir = (string) ($row['dir'] ?? '');
            if ($dir === '') { continue; }

            $lpJson   = $this->readJsonFile($base.'/'.$dir.'/learnpath.json');
            $itemsJson= $this->readJsonFile($base.'/'.$dir.'/items.json');

            $lpRaw    = (array) ($lpJson['learnpath'] ?? []);
            // Defensive normalization
            $lpTitle  = (string) ($lpRaw['title'] ?? $lpRaw['name'] ?? ($row['title'] ?? 'Lesson'));
            $lpType   = (int)   ($lpRaw['lp_type'] ?? $row['lp_type'] ?? 1);
            $catId    = (int)   ($lpRaw['category_id'] ?? $row['category_id'] ?? 0);

            // Allocate a fresh ID (avoid collisions) but keep source id in meta
            $lid = $this->nextId($resources['learnpath']);
            $payload = [
                'id'          => $lid,
                'lp_type'     => $lpType, // 1=LP, 2=SCORM, 3=AICC
                'title'       => $lpTitle,
                'path'        => (string) ($lpRaw['path'] ?? ''),
                'ref'         => (string) ($lpRaw['ref'] ?? ''),
                'description' => (string) ($lpRaw['description'] ?? ''),
                'content_local'      => (string) ($lpRaw['content_local'] ?? ''),
                'default_encoding'   => (string) ($lpRaw['default_encoding'] ?? ''),
                'default_view_mod'   => (string) ($lpRaw['default_view_mod'] ?? ''),
                'prevent_reinit'     => (bool)   ($lpRaw['prevent_reinit'] ?? false),
                'force_commit'       => (bool)   ($lpRaw['force_commit'] ?? false),
                'content_maker'      => (string) ($lpRaw['content_maker'] ?? ''),
                'display_order'      => (int)    ($lpRaw['display_order'] ?? 0),
                'js_lib'             => (string) ($lpRaw['js_lib'] ?? ''),
                'content_license'    => (string) ($lpRaw['content_license'] ?? ''),
                'debug'              => (bool)   ($lpRaw['debug'] ?? false),
                'visibility'         => (string) ($lpRaw['visibility'] ?? '1'),
                'author'             => (string) ($lpRaw['author'] ?? ''),
                'use_max_score'      => (int)    ($lpRaw['use_max_score'] ?? 0),
                'autolaunch'         => (int)    ($lpRaw['autolaunch'] ?? 0),
                'created_on'         => (string) ($lpRaw['created_on'] ?? ''),
                'modified_on'        => (string) ($lpRaw['modified_on'] ?? ''),
                'published_on'       => (string) ($lpRaw['published_on'] ?? ''),
                'expired_on'         => (string) ($lpRaw['expired_on'] ?? ''),
                'session_id'         => 0,
                'category_id'        => $catId > 0 ? $catId : (array_key_first($resources['learnpath_category']) ?? 0),
                '_src'               => [
                    'lp_id' => (int) ($lpRaw['id'] ?? ($row['id'] ?? 0)),
                ],
            ];

            // Create wrapper with extended props (items, linked_resources)
            $resources['learnpath'][$lid] = $this->mkLegacyItem('learnpath', $lid, $payload, ['items','linked_resources']);

            // Items: stable-order by display_order if present
            $rawItems = (array) ($itemsJson['items'] ?? []);
            usort($rawItems, static fn(array $a, array $b) =>
                (int)($a['display_order'] ?? 0) <=> (int)($b['display_order'] ?? 0));

            $items = [];
            foreach ($rawItems as $it) {
                $mappedRef = $this->mapLpItemRef($it, $idx, $resources);
                $items[] = [
                    'id'             => (int)   ($it['id'] ?? 0),
                    'item_type'      => (string)($it['item_type'] ?? ''),
                    'ref'            => $mappedRef,
                    'title'          => (string)($it['title'] ?? ''),
                    'name'           => (string)($it['name'] ?? $lpTitle),
                    'description'    => (string)($it['description'] ?? ''),
                    'path'           => (string)($it['path'] ?? ''),
                    'min_score'      => (float) ($it['min_score'] ?? 0),
                    'max_score'      => isset($it['max_score']) ? (float) $it['max_score'] : null,
                    'mastery_score'  => isset($it['mastery_score']) ? (float) $it['mastery_score'] : null,
                    'parent_item_id' => (int)   ($it['parent_item_id'] ?? 0),
                    'previous_item_id'=> isset($it['previous_item_id']) ? (int) $it['previous_item_id'] : null,
                    'next_item_id'   => isset($it['next_item_id']) ? (int) $it['next_item_id'] : null,
                    'display_order'  => (int)   ($it['display_order'] ?? 0),
                    'prerequisite'   => (string)($it['prerequisite'] ?? ''),
                    'parameters'     => (string)($it['parameters'] ?? ''),
                    'launch_data'    => (string)($it['launch_data'] ?? ''),
                    'audio'          => (string)($it['audio'] ?? ''),
                    '_src'           => [
                        'ref'  => $it['ref'] ?? null,
                        'path' => $it['path'] ?? null,
                    ],
                ];
            }

            $resources['learnpath'][$lid]->items = $items;
            $resources['learnpath'][$lid]->linked_resources = $this->collectLinkedFromLpItems($items);

            $imported++;
        }

        if ($this->debug) {
            @error_log("MOODLE_IMPORT: LPs from meta imported={$imported}");
        }
        return $imported > 0;
    }

    /** Read JSON file safely; return [] on error. */
    private function readJsonFile(string $file): array
    {
        $raw = @file_get_contents($file);
        if ($raw === false) { return []; }
        $data = json_decode($raw, true);
        return \is_array($data) ? $data : [];
    }

    /**
     * Build look-up indexes over the freshly collected resources to resolve LP item refs.
     * We index by multiple keys to increase match odds (path, title, url, etc.)
     */
    private function buildResourceIndexes(array $resources): array
    {
        $idx = [
            'documentByPath' => [],
            'documentByTitle' => [],
            'linkByUrl' => [],
            'forumByTitle' => [],
            'quizByTitle' => [],
            'workByTitle' => [],
            'scormByTitle' => [],
        ];

        foreach ((array) ($resources['document'] ?? []) as $id => $doc) {
            $arr = \is_object($doc) ? get_object_vars($doc) : (array) $doc;
            $p   = (string) ($arr['path'] ?? '');
            $t   = (string) ($arr['title'] ?? '');
            if ($p !== '') { $idx['documentByPath'][$p] = (int) $id; }
            if ($t !== '') { $idx['documentByTitle'][mb_strtolower($t)][] = (int) $id; }
        }
        foreach ((array) ($resources['link'] ?? []) as $id => $lnk) {
            $arr = \is_object($lnk) ? get_object_vars($lnk) : (array) $lnk;
            $u   = (string) ($arr['url'] ?? '');
            if ($u !== '') { $idx['linkByUrl'][$u] = (int) $id; }
        }
        foreach ((array) ($resources['forum'] ?? []) as $id => $f) {
            $arr = \is_object($f) ? get_object_vars($f) : (array) $f;
            $t   = (string) ($arr['forum_title'] ?? $arr['title'] ?? '');
            if ($t !== '') { $idx['forumByTitle'][mb_strtolower($t)][] = (int) $id; }
        }
        foreach ((array) ($resources['quizzes'] ?? []) as $id => $q) {
            $arr = \is_object($q) ? get_object_vars($q) : (array) $q;
            $t   = (string) ($arr['name'] ?? $arr['title'] ?? '');
            if ($t !== '') { $idx['quizByTitle'][mb_strtolower($t)][] = (int) $id; }
        }
        foreach ((array) ($resources['works'] ?? []) as $id => $w) {
            $arr = \is_object($w) ? get_object_vars($w) : (array) $w;
            $t   = (string) ($arr['name'] ?? $arr['title'] ?? '');
            if ($t !== '') { $idx['workByTitle'][mb_strtolower($t)][] = (int) $id; }
        }
        foreach ((array) ($resources['scorm'] ?? $resources['scorm_documents'] ?? []) as $id => $s) {
            $arr = \is_object($s) ? get_object_vars($s) : (array) $s;
            $t   = (string) ($arr['title'] ?? $arr['name'] ?? '');
            if ($t !== '') { $idx['scormByTitle'][mb_strtolower($t)][] = (int) $id; }
        }

        return $idx;
    }

    /**
     * Resolve LP item "ref" from meta (which refers to the source system) into a local resource id.
     * Strategy:
     *  1) For documents: match by path (strong), or by title (weak).
     *  2) For links: match by url.
     *  3) For forum/quizzes/works/scorm: match by title.
     * If not resolvable, return null and keep original _src in item for later diagnostics.
     */
    private function mapLpItemRef(array $item, array $idx, array $resources): ?int
    {
        $type = (string) ($item['item_type'] ?? '');
        $srcRef = $item['ref'] ?? null;
        $path   = (string) ($item['path'] ?? '');
        $title  = mb_strtolower((string) ($item['title'] ?? ''));

        switch ($type) {
            case 'document':
                if ($path !== '' && isset($idx['documentByPath'][$path])) {
                    return $idx['documentByPath'][$path];
                }
                if ($title !== '' && !empty($idx['documentByTitle'][$title])) {
                    // If multiple, pick the first; could be improved with size/hash if available
                    return $idx['documentByTitle'][$title][0];
                }
                return null;

            case 'link':
                if (isset($idx['linkByUrl'][$srcRef])) {
                    return $idx['linkByUrl'][$srcRef];
                }
                // Sometimes meta keeps URL in "path"
                if ($path !== '' && isset($idx['linkByUrl'][$path])) {
                    return $idx['linkByUrl'][$path];
                }
                return null;

            case 'forum':
                if ($title !== '' && !empty($idx['forumByTitle'][$title])) {
                    return $idx['forumByTitle'][$title][0];
                }
                return null;

            case 'quiz':
            case 'quizzes':
                if ($title !== '' && !empty($idx['quizByTitle'][$title])) {
                    return $idx['quizByTitle'][$title][0];
                }
                return null;

            case 'works':
                if ($title !== '' && !empty($idx['workByTitle'][$title])) {
                    return $idx['workByTitle'][$title][0];
                }
                return null;

            case 'scorm':
                if ($title !== '' && !empty($idx['scormByTitle'][$title])) {
                    return $idx['scormByTitle'][$title][0];
                }
                return null;

            default:
                return null;
        }
    }

    /**
    " Import quizzes from QuizMetaExport sidecars under chamilo/quiz/quiz_.
    * Builds 'quiz' and 'quiz_question' (and their constant-key aliases if defined).
    * Returns true if at least one quiz was imported.
    */
    private function tryImportQuizMeta(string $workDir, array &$resources): bool
    {
        $base = rtrim($workDir, '/').'/chamilo/quiz';
        if (!is_dir($base)) {
            return false;
        }

        // Resolve resource keys (support both constant and string bags)
        $quizKey = \defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz';
        // Chamilo snapshot also uses sometimes 'quizzes' — we fill both for compatibility
        $quizCompatKey = 'quizzes';

        $qqKey = \defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : 'Exercise_Question';
        $qqCompatKey = 'quiz_question';

        $imported = 0;

        // Iterate all quiz_* folders
        $dh = @opendir($base);
        if (!$dh) {
            return false;
        }

        while (false !== ($entry = readdir($dh))) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $base.'/'.$entry;
            if (!is_dir($dir) || strpos($entry, 'quiz_') !== 0) {
                continue;
            }

            $quizJsonFile = $dir.'/quiz.json';
            $questionsFile = $dir.'/questions.json';
            $answersFile   = $dir.'/answers.json'; // optional (flat; we prefer nested)

            $quizWrap = $this->readJsonFile($quizJsonFile);
            $qList    = $this->readJsonFile($questionsFile);
            if (empty($quizWrap) || empty($qList)) {
                // Nothing to import for this folder
                if ($this->debug) {
                    @error_log("MOODLE_IMPORT: Quiz meta missing or incomplete in {$entry}");
                }
                continue;
            }

            $quizArr = (array) ($quizWrap['quiz'] ?? []);
            $questions = (array) ($qList['questions'] ?? []);

            // ---- Resolve or allocate quiz local id
            $title = (string) ($quizArr['title'] ?? $quizArr['name'] ?? 'Quiz');
            $qidLocal = $this->findExistingQuizIdByTitle($resources, $title, [$quizKey, $quizCompatKey]);
            if (!$qidLocal) {
                $qidLocal = $this->nextId($resources[$quizKey] ?? []);
            }

            // Ensure bags exist
            if (!isset($resources[$quizKey]))      { $resources[$quizKey] = []; }
            if (!isset($resources[$quizCompatKey])){ $resources[$quizCompatKey] = []; }
            if (!isset($resources[$qqKey]))        { $resources[$qqKey] = []; }
            if (!isset($resources[$qqCompatKey]))  { $resources[$qqCompatKey] = []; }

            // ---- Build local question id map (src → local)
            $srcToLocalQ = [];
            // If meta provides question_ids, we keep order from 'question_orders' when present.
            $srcIds   = array_map('intval', (array) ($quizArr['question_ids'] ?? []));
            $srcOrder = array_map('intval', (array) ($quizArr['question_orders'] ?? []));

            // First pass: assign local ids to all questions we are about to import
            foreach ($questions as $qArr) {
                // Prefer explicit id added by exporter; otherwise try _links.quiz_id or fallback 0
                $srcQid = (int) ($qArr['id'] ?? 0);
                if ($srcQid <= 0) {
                    // Try to infer from position in the list (not ideal, but keeps import going)
                    $srcQid = $this->nextId($srcToLocalQ); // synthetic progressive id
                }
                $srcToLocalQ[$srcQid] = $this->nextId($resources[$qqKey]);
            }

            // ---- Rebuild quiz payload (builder-compatible)
            $payload = [
                'title'                 => (string) ($quizArr['title'] ?? ''),
                'description'           => (string) ($quizArr['description'] ?? ''),
                'type'                  => (int)    ($quizArr['type'] ?? 0),
                'random'                => (int)    ($quizArr['random'] ?? 0),
                'random_answers'        => (bool)   ($quizArr['random_answers'] ?? false),
                'results_disabled'      => (int)    ($quizArr['results_disabled'] ?? 0),
                'max_attempt'           => (int)    ($quizArr['max_attempt'] ?? 0),
                'feedback_type'         => (int)    ($quizArr['feedback_type'] ?? 0),
                'expired_time'          => (int)    ($quizArr['expired_time'] ?? 0),
                'review_answers'        => (int)    ($quizArr['review_answers'] ?? 0),
                'random_by_category'    => (int)    ($quizArr['random_by_category'] ?? 0),
                'text_when_finished'    => (string) ($quizArr['text_when_finished'] ?? ''),
                'text_when_finished_failure' => (string) ($quizArr['text_when_finished_failure'] ?? ''),
                'display_category_name' => (int)    ($quizArr['display_category_name'] ?? 0),
                'save_correct_answers'  => (int)    ($quizArr['save_correct_answers'] ?? 0),
                'propagate_neg'         => (int)    ($quizArr['propagate_neg'] ?? 0),
                'hide_question_title'   => (bool)   ($quizArr['hide_question_title'] ?? false),
                'hide_question_number'  => (int)    ($quizArr['hide_question_number'] ?? 0),
                'question_selection_type'=> (int)   ($quizArr['question_selection_type'] ?? 0),
                'access_condition'      => (string) ($quizArr['access_condition'] ?? ''),
                'pass_percentage'       => $quizArr['pass_percentage'] ?? null,
                'start_time'            => (string) ($quizArr['start_time'] ?? ''),
                'end_time'              => (string) ($quizArr['end_time'] ?? ''),
                // We will remap IDs to locals below:
                'question_ids'          => [],
                'question_orders'       => [],
            ];

            // Fill question_ids and orders using the local id map
            $localIds   = [];
            $localOrder = [];

            // If we received aligned srcIds/srcOrder, keep that order; otherwise, use question '_order'
            if (!empty($srcIds) && !empty($srcOrder) && \count($srcIds) === \count($srcOrder)) {
                foreach ($srcIds as $i => $srcQid) {
                    if (isset($srcToLocalQ[$srcQid])) {
                        $localIds[]   = $srcToLocalQ[$srcQid];
                        $localOrder[] = (int) $srcOrder[$i];
                    }
                }
            } else {
                // Build order from questions array (_order) if present; otherwise keep list order
                usort($questions, static fn(array $a, array $b) =>
                    (int)($a['_order'] ?? 0) <=> (int)($b['_order'] ?? 0));
                foreach ($questions as $qArr) {
                    $srcQid = (int) ($qArr['id'] ?? 0);
                    if (isset($srcToLocalQ[$srcQid])) {
                        $localIds[]   = $srcToLocalQ[$srcQid];
                        $localOrder[] = (int) ($qArr['_order'] ?? 0);
                    }
                }
            }

            $payload['question_ids']    = $localIds;
            $payload['question_orders'] = $localOrder;

            // Store quiz in both bags (constant/string and compat)
            $resources[$quizKey][$qidLocal] =
                $this->mkLegacyItem($quizKey, $qidLocal, $payload, ['question_ids', 'question_orders']);
            $resources[$quizCompatKey][$qidLocal] = $resources[$quizKey][$qidLocal];

            // ---- Import questions (with nested answers), mapping to local ids
            foreach ($questions as $qArr) {
                $srcQid = (int) ($qArr['id'] ?? 0);
                $qid    = $srcToLocalQ[$srcQid] ?? $this->nextId($resources[$qqKey]);

                $qPayload = [
                    'question'        => (string) ($qArr['question'] ?? ''),
                    'description'     => (string) ($qArr['description'] ?? ''),
                    'ponderation'     => (float)  ($qArr['ponderation'] ?? 0),
                    'position'        => (int)    ($qArr['position'] ?? 0),
                    'type'            => (int)    ($qArr['type'] ?? ($qArr['quiz_type'] ?? 0)),
                    'quiz_type'       => (int)    ($qArr['quiz_type'] ?? ($qArr['type'] ?? 0)),
                    'picture'         => (string) ($qArr['picture'] ?? ''),
                    'level'           => (int)    ($qArr['level'] ?? 0),
                    'extra'           => (string) ($qArr['extra'] ?? ''),
                    'feedback'        => (string) ($qArr['feedback'] ?? ''),
                    'question_code'   => (string) ($qArr['question_code'] ?? ''),
                    'mandatory'       => (int)    ($qArr['mandatory'] ?? 0),
                    'duration'        => $qArr['duration'] ?? null,
                    'parent_media_id' => $qArr['parent_media_id'] ?? null,
                    'answers'         => [],
                ];

                // Answers: prefer nested in questions.json; fallback to answers.json (flat)
                $ansList = [];
                if (isset($qArr['answers']) && \is_array($qArr['answers'])) {
                    $ansList = $qArr['answers'];
                } else {
                    // Try to reconstruct from flat answers.json
                    $ansFlat = $this->readJsonFile($answersFile);
                    foreach ((array) ($ansFlat['answers'] ?? []) as $row) {
                        if ((int) ($row['question_id'] ?? -1) === $srcQid && isset($row['data'])) {
                            $ansList[] = $row['data'];
                        }
                    }
                }

                $pos = 1;
                foreach ($ansList as $a) {
                    $qPayload['answers'][] = [
                        'id'                  => (int)    ($a['id'] ?? $this->nextId($qPayload['answers'])),
                        'answer'              => (string) ($a['answer'] ?? ''),
                        'comment'             => (string) ($a['comment'] ?? ''),
                        'ponderation'         => (float)  ($a['ponderation'] ?? 0),
                        'position'            => (int)    ($a['position'] ?? $pos),
                        'hotspot_coordinates' => $a['hotspot_coordinates'] ?? null,
                        'hotspot_type'        => $a['hotspot_type'] ?? null,
                        'correct'             => $a['correct'] ?? null,
                    ];
                    $pos++;
                }

                // Optional: MATF options (as in builder)
                if (isset($qArr['question_options']) && \is_array($qArr['question_options'])) {
                    $qPayload['question_options'] = array_map(static fn ($o) => [
                        'id'       => (int) ($o['id'] ?? 0),
                        'name'     => (string) ($o['name'] ?? ''),
                        'position' => (int) ($o['position'] ?? 0),
                    ], $qArr['question_options']);
                }

                $resources[$qqKey][$qid] =
                    $this->mkLegacyItem($qqKey, $qid, $qPayload, ['answers', 'question_options']);
                $resources[$qqCompatKey][$qid] = $resources[$qqKey][$qid];
            }

            $imported++;
        }

        closedir($dh);

        if ($this->debug) {
            @error_log("MOODLE_IMPORT: Quizzes from meta imported={$imported}");
        }
        return $imported > 0;
    }

    /** Find an existing quiz by title (case-insensitive) in any of the provided bags. */
    private function findExistingQuizIdByTitle(array $resources, string $title, array $bags): ?int
    {
        $needle = mb_strtolower(trim($title));
        foreach ($bags as $bag) {
            foreach ((array) ($resources[$bag] ?? []) as $id => $q) {
                $arr = \is_object($q) ? get_object_vars($q) : (array) $q;
                $t   = (string) ($arr['title'] ?? $arr['name'] ?? '');
                if ($needle !== '' && mb_strtolower($t) === $needle) {
                    return (int) $id;
                }
            }
        }
        return null;
    }

    /** Quick probe: do we have at least one chamilo/quiz/quiz_quiz.json + questions.json ? */
    private function hasQuizMeta(string $workDir): bool
    {
        $base = rtrim($workDir, '/').'/chamilo/quiz';
        if (!is_dir($base)) return false;
        if (!$dh = @opendir($base)) return false;
        while (false !== ($e = readdir($dh))) {
            if ($e === '.' || $e === '..') continue;
            $dir = $base.'/'.$e;
            if (is_dir($dir) && str_starts_with($e, 'quiz_')
                && is_file($dir.'/quiz.json') && is_file($dir.'/questions.json')) {
                closedir($dh);
                return true;
            }
        }
        closedir($dh);
        return false;
    }

    /** Quick probe: typical LearnpathMetaExport artifacts */
    private function hasLearnpathMeta(string $workDir): bool
    {
        $base = rtrim($workDir, '/').'/chamilo/learnpath';
        return is_dir($base) && (is_file($base.'/index.json') || is_file($base.'/categories.json'));
    }

    /** Cheap reader: obtain <quiz><name> from module xml without building resources. */
    private function peekQuizTitle(string $moduleXml): ?string
    {
        try {
            $doc = $this->loadXml($moduleXml);
            $xp  = new DOMXPath($doc);
            $name = $xp->query('//quiz/name')->item(0)?->nodeValue ?? null;
            return $name ? (string) $name : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * For LP fallback items with missing 'ref', try to resolve by title against resources bags.
     * Matching is case-insensitive and checks both 'title' and 'name' fields in resources.
     */
    private function backfillLpRefsFromResources(array &$lpMap, array $resources, array $bags): void
    {
        // Build lookup: item_type => [lower(title) => id]
        $lookups = [];

        foreach ($bags as $bag) {
            foreach ((array) ($resources[$bag] ?? []) as $id => $wrap) {
                $obj = \is_object($wrap) ? $wrap : (object) $wrap;
                // candidate fields
                $title = '';
                if (isset($obj->title) && \is_string($obj->title)) {
                    $title = $obj->title;
                } elseif (isset($obj->name) && \is_string($obj->name)) {
                    $title = $obj->name;
                } elseif (isset($obj->obj) && \is_object($obj->obj)) {
                    $title = (string) ($obj->obj->title ?? $obj->obj->name ?? '');
                }
                if ($title === '') continue;

                $key = mb_strtolower($title);
                $typeKey = $this->normalizeItemTypeKey($bag); // e.g. 'quiz' for ['quiz','quizzes']
                if (!isset($lookups[$typeKey])) $lookups[$typeKey] = [];
                $lookups[$typeKey][$key] = (int) $id;
            }
        }

        // Walk all LP items and fill 'ref' when empty
        foreach ($lpMap as &$lp) {
            foreach ($lp['items'] as &$it) {
                if (!empty($it['ref'])) continue;
                $type = $this->normalizeItemTypeKey((string) ($it['item_type'] ?? ''));
                $t    = mb_strtolower((string) ($it['title'] ?? ''));
                if ($t !== '' && isset($lookups[$type][$t])) {
                    $it['ref'] = $lookups[$type][$t];
                }
            }
            unset($it);
        }
        unset($lp);
    }

    /** Normalize various bag/item_type labels into a stable key used in lookup. */
    private function normalizeItemTypeKey(string $s): string
    {
        $s = strtolower($s);
        return match ($s) {
            'quizzes', 'quiz', \defined('RESOURCE_QUIZ') ? strtolower((string) RESOURCE_QUIZ) : 'quiz' => 'quiz',
            'document', 'documents' => 'document',
            'forum', 'forums' => 'forum',
            'link', 'links' => 'link',
            'scorm', 'scorm_documents' => 'scorm',
            'coursedescription'   => 'course_description',
            'course_descriptions' => 'course_description',
            default => $s,
        };
    }

    /** Merge bag aliases into canonical keys to avoid duplicate groups. */
    private function canonicalizeResourceBags(array $res): array
    {
        // Canonical keys (fall back to strings if constants not defined)
        $QUIZ_KEY = \defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz';
        $QQ_KEY   = \defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : 'quiz_question';

        // ---- Quizzes ----
        $mergedQuiz = [];
        foreach (['quizzes', 'quiz', 'Exercise', $QUIZ_KEY] as $k) {
            if (!empty($res[$k]) && \is_array($res[$k])) {
                foreach ($res[$k] as $id => $item) {
                    $mergedQuiz[(int)$id] = $item;
                }
            }
            unset($res[$k]);
        }
        $res[$QUIZ_KEY] = $mergedQuiz;

        // ---- Quiz Questions ----
        $mergedQQ = [];
        foreach (['quiz_question', 'Exercise_Question', $QQ_KEY] as $k) {
            if (!empty($res[$k]) && \is_array($res[$k])) {
                foreach ($res[$k] as $id => $item) {
                    $mergedQQ[(int)$id] = $item;
                }
            }
            unset($res[$k]);
        }
        $res[$QQ_KEY] = $mergedQQ;

        $THEM_KEY = \defined('RESOURCE_THEMATIC') ? RESOURCE_THEMATIC : 'thematic';
        $mergedThematic = [];
        foreach (['thematic', $THEM_KEY] as $k) {
            if (!empty($res[$k]) && \is_array($res[$k])) {
                foreach ($res[$k] as $id => $item) {
                    $mergedThematic[(int)$id] = $item;
                }
            }
            unset($res[$k]);
        }
        if (!empty($mergedThematic)) {
            $res[$THEM_KEY] = $mergedThematic;
        }

        $ATT_KEY = \defined('RESOURCE_ATTENDANCE') ? RESOURCE_ATTENDANCE : 'attendance';
        $merged = [];
        foreach (['attendance', $ATT_KEY] as $k) {
            if (!empty($res[$k]) && \is_array($res[$k])) {
                foreach ($res[$k] as $id => $it) { $merged[(int)$id] = $it; }
            }
            unset($res[$k]);
        }
        if ($merged) { $res[$ATT_KEY] = $merged; }

        $GB_KEY = \defined('RESOURCE_GRADEBOOK') ? RESOURCE_GRADEBOOK : 'gradebook';
        $merged = [];
        foreach (['gradebook', 'Gradebook', $GB_KEY] as $k) {
            if (!empty($res[$k]) && \is_array($res[$k])) {
                foreach ($res[$k] as $id => $it) { $merged[(int)$id] = $it; }
            }
            unset($res[$k]);
        }
        if ($merged) { $res[$GB_KEY] = $merged; }

        return $res;
    }

    /**
     * Import Thematic sidecars written by ThematicMetaExport.
     * Authoritative: if present, replaces any pre-filled bag.
     *
     * @return bool true if at least one thematic was imported
     */
    private function tryImportThematicMeta(string $workDir, array &$resources): bool
    {
        $THEM_KEY = \defined('RESOURCE_THEMATIC') ? RESOURCE_THEMATIC : 'thematic';

        $base = rtrim($workDir, '/').'/chamilo/thematic';
        if (!is_dir($base)) {
            return false;
        }

        // 1) Discover files (prefer manifest, fallback to glob)
        $files = [];
        $manifest = @json_decode((string)@file_get_contents(rtrim($workDir, '/').'/chamilo/manifest.json'), true);
        if (\is_array($manifest['items'] ?? null)) {
            foreach ($manifest['items'] as $it) {
                if (($it['kind'] ?? '') === 'thematic' && !empty($it['path'])) {
                    $path = rtrim($workDir, '/').'/'.ltrim((string)$it['path'], '/');
                    if (is_file($path)) {
                        $files[] = $path;
                    }
                }
            }
        }
        if (empty($files)) {
            foreach ((array)@glob($base.'/thematic_*.json') as $f) {
                if (is_file($f)) {
                    $files[] = $f;
                }
            }
        }
        if (empty($files)) {
            return false;
        }

        // Authoritative: reset bag to avoid duplicates
        $resources[$THEM_KEY] = [];

        $imported = 0;
        foreach ($files as $f) {
            $payload = @json_decode((string)@file_get_contents($f), true);
            if (!\is_array($payload)) {
                continue;
            }

            // Exporter shape: { "type":"thematic", ..., "title","content","active","advances":[...], "plans":[...] }
            $title   = (string)($payload['title']   ?? 'Thematic');
            $content = (string)($payload['content'] ?? '');
            $active  = (int)   ($payload['active']  ?? 1);

            // Prefer explicit id inside nested shapes if present
            $iid = (int)($payload['id'] ?? 0);
            if ($iid <= 0) {
                // Derive from filename thematic_{moduleId}.json or moduleid
                $iid = (int)($payload['moduleid'] ?? 0);
                if ($iid <= 0 && preg_match('/thematic_(\d+)\.json$/', (string)$f, $m)) {
                    $iid = (int)$m[1];
                }
                if ($iid <= 0) {
                    $iid = $this->nextId($resources[$THEM_KEY] ?? []);
                }
            }

            // Normalize lists
            $advances = [];
            foreach ((array)($payload['advances'] ?? []) as $a) {
                $a = (array)$a;
                $advances[] = [
                    'id'            => (int)   ($a['id'] ?? ($a['iid'] ?? 0)),
                    'thematic_id'   => (int)   ($a['thematic_id'] ?? $iid),
                    'content'       => (string)($a['content'] ?? ''),
                    'start_date'    => (string)($a['start_date'] ?? ''),
                    'duration'      => (int)   ($a['duration'] ?? 0),
                    'done_advance'  => (bool)  ($a['done_advance'] ?? false),
                    'attendance_id' => (int)   ($a['attendance_id'] ?? 0),
                    'room_id'       => (int)   ($a['room_id'] ?? 0),
                ];
            }

            $plans = [];
            foreach ((array)($payload['plans'] ?? []) as $p) {
                $p = (array)$p;
                $plans[] = [
                    'id'               => (int)   ($p['id'] ?? ($p['iid'] ?? 0)),
                    'thematic_id'      => (int)   ($p['thematic_id'] ?? $iid),
                    'title'            => (string)($p['title'] ?? ''),
                    'description'      => (string)($p['description'] ?? ''),
                    'description_type' => (int)   ($p['description_type'] ?? 0),
                ];
            }

            // mkLegacyItem wrapper with explicit list fields
            $item = $this->mkLegacyItem($THEM_KEY, $iid, [
                'id'      => $iid,
                'title'   => $title,
                'content' => $content,
                'active'  => $active,
            ], ['thematic_advance_list','thematic_plan_list']);

            // Attach lists on the wrapper (builder-friendly)
            $item->thematic_advance_list = $advances;
            $item->thematic_plan_list    = $plans;

            $resources[$THEM_KEY][$iid] = $item;
            $imported++;
        }

        if ($this->debug) {
            @error_log('MOODLE_IMPORT: Thematic meta imported='.$imported);
        }

        return $imported > 0;
    }

    /**
     * Import Attendance sidecars written by AttendanceMetaExport.
     * Authoritative: if present, replaces any pre-filled bag to avoid duplicates.
     *
     * @return bool true if at least one attendance was imported
     */
    private function tryImportAttendanceMeta(string $workDir, array &$resources): bool
    {
        $ATT_KEY = \defined('RESOURCE_ATTENDANCE') ? RESOURCE_ATTENDANCE : 'attendance';
        $base    = rtrim($workDir, '/').'/chamilo/attendance';
        if (!is_dir($base)) {
            return false;
        }

        // 1) Discover files via manifest (preferred), fallback to glob
        $files = [];
        $manifestFile = rtrim($workDir, '/').'/chamilo/manifest.json';
        $manifest = @json_decode((string)@file_get_contents($manifestFile), true);
        if (\is_array($manifest['items'] ?? null)) {
            foreach ($manifest['items'] as $it) {
                if (($it['kind'] ?? '') === 'attendance' && !empty($it['path'])) {
                    $path = rtrim($workDir, '/').'/'.ltrim((string)$it['path'], '/');
                    if (is_file($path)) {
                        $files[] = $path;
                    }
                }
            }
        }
        if (empty($files)) {
            foreach ((array)@glob($base.'/attendance_*.json') as $f) {
                if (is_file($f)) {
                    $files[] = $f;
                }
            }
        }
        if (empty($files)) {
            return false;
        }

        // Authoritative: clear bag to avoid duplicates
        $resources[$ATT_KEY] = [];

        $imported = 0;
        foreach ($files as $f) {
            $payload = @json_decode((string)@file_get_contents($f), true);
            if (!\is_array($payload)) {
                continue;
            }

            // ---- Map top-level fields (robust against naming variants)
            $iid   = (int)($payload['id'] ?? 0);
            if ($iid <= 0) {
                $iid = (int)($payload['moduleid'] ?? 0);
            }
            if ($iid <= 0 && preg_match('/attendance_(\d+)\.json$/', (string)$f, $m)) {
                $iid = (int)$m[1];
            }
            if ($iid <= 0) {
                $iid = $this->nextId($resources[$ATT_KEY] ?? []);
            }

            $title = (string)($payload['title'] ?? $payload['name'] ?? 'Attendance');
            $desc  = (string)($payload['description'] ?? $payload['intro'] ?? '');
            $active= (int)   ($payload['active'] ?? 1);
            $locked= (int)   ($payload['locked'] ?? 0);

            // Qualify block may be nested or flattened
            $qual   = \is_array($payload['qualify'] ?? null) ? $payload['qualify'] : [];
            $qualTitle = (string)($qual['title'] ?? $payload['attendance_qualify_title'] ?? '');
            $qualMax   = (int)   ($qual['max']   ?? $payload['attendance_qualify_max'] ?? 0);
            $weight    = (float) ($qual['weight']?? $payload['attendance_weight'] ?? 0.0);

            // ---- Normalize calendars
            $calIn  = (array)($payload['calendars'] ?? []);
            $cals   = [];
            foreach ($calIn as $c) {
                if (!\is_array($c) && !\is_object($c)) {
                    continue;
                }
                $c = (array)$c;
                $cid   = (int)($c['id'] ?? $c['iid'] ?? 0);
                $aid   = (int)($c['attendance_id'] ?? $iid);
                $dt    = (string)($c['date_time'] ?? $c['datetime'] ?? '');
                $done  = (bool)  ($c['done_attendance'] ?? false);
                $block = (bool)  ($c['blocked'] ?? false);
                $dur   = $c['duration'] ?? null;
                $dur   = (null !== $dur) ? (int)$dur : null;

                $cals[] = [
                    'id'              => $cid > 0 ? $cid : $this->nextId($cals),
                    'attendance_id'   => $aid,
                    'date_time'       => $dt,
                    'done_attendance' => $done,
                    'blocked'         => $block,
                    'duration'        => $dur,
                ];
            }

            // ---- Wrap as legacy item compatible with builder
            $item = $this->mkLegacyItem(
                $ATT_KEY,
                $iid,
                [
                    'id'                        => $iid,
                    'title'                     => $title,
                    'name'                      => $title,   // keep alias
                    'description'               => $desc,
                    'active'                    => $active,
                    'attendance_qualify_title'  => $qualTitle,
                    'attendance_qualify_max'    => $qualMax,
                    'attendance_weight'         => $weight,
                    'locked'                    => $locked,
                ],
                ['attendance_calendar'] // list fields that will be appended below
            );

            // Attach calendars collection on the wrapper
            $item->attendance_calendar = $cals;

            $resources[$ATT_KEY][$iid] = $item;
            $imported++;
        }

        if ($this->debug) {
            @error_log('MOODLE_IMPORT: Attendance meta imported='.$imported);
        }

        return $imported > 0;
    }

    /**
     * Import Gradebook sidecars written by GradebookMetaExport.
     * Authoritative: if present, replaces any pre-filled bag to avoid duplicates.
     *
     * @return bool true if at least one gradebook was imported
     */
    private function tryImportGradebookMeta(string $workDir, array &$resources): bool
    {
        $GB_KEY = \defined('RESOURCE_GRADEBOOK') ? RESOURCE_GRADEBOOK : 'gradebook';
        $base   = rtrim($workDir, '/').'/chamilo/gradebook';
        if (!is_dir($base)) {
            return false;
        }

        // 1) Discover files via manifest (preferred), fallback to glob
        $files = [];
        $manifestFile = rtrim($workDir, '/').'/chamilo/manifest.json';
        $manifest = @json_decode((string)@file_get_contents($manifestFile), true);
        if (\is_array($manifest['items'] ?? null)) {
            foreach ($manifest['items'] as $it) {
                if (($it['kind'] ?? '') === 'gradebook' && !empty($it['path'])) {
                    $path = rtrim($workDir, '/').'/'.ltrim((string)$it['path'], '/');
                    if (is_file($path)) {
                        $files[] = $path;
                    }
                }
            }
        }
        if (empty($files)) {
            foreach ((array)@glob($base.'/gradebook_*.json') as $f) {
                if (is_file($f)) {
                    $files[] = $f;
                }
            }
        }
        if (empty($files)) {
            return false;
        }

        // Authoritative: clear bag to avoid duplicates
        $resources[$GB_KEY] = [];

        $imported = 0;
        foreach ($files as $f) {
            $payload = @json_decode((string)@file_get_contents($f), true);
            if (!\is_array($payload)) {
                continue;
            }

            // Categories are already serialized by the builder; pass them through.
            $categories = \is_array($payload['categories'] ?? null) ? $payload['categories'] : [];

            // Determine a stable id (not really used by Moodle, but kept for parity)
            $iid = (int)($payload['id'] ?? 0);
            if ($iid <= 0) { $iid = (int)($payload['moduleid'] ?? 0); }
            if ($iid <= 0 && preg_match('/gradebook_(\d+)\.json$/', (string)$f, $m)) {
                $iid = (int)$m[1];
            }
            if ($iid <= 0) { $iid = 1; }

            // Build a minimal legacy-like object compatible with GradebookMetaExport::findGradebookBackup()
            $gb = (object)[
                // matches what the exporter looks for
                'categories' => $categories,

                // helpful hints
                'id'        => $iid,
                'source_id' => $iid,
                'title'     => (string)($payload['title'] ?? 'Gradebook'),
            ];

            // Store in canonical bag
            $resources[$GB_KEY][$iid] = $gb;
            $imported++;
        }

        if ($this->debug) {
            @error_log('MOODLE_IMPORT: Gradebook meta imported='.$imported);
        }

        return $imported > 0;
    }

    /**
     * Read activities/wiki_{moduleId}/wiki.xml and return:
     *  - meta: module-level info (name, moduleid, sectionid)
     *  - pages: array of pages with id,title,content,contentformat,version,userid,timecreated,timemodified
     */
    private function readWikiModuleFull(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp  = new DOMXPath($doc);

        // Module meta
        $activity = $xp->query('/activity')->item(0);
        $moduleId = (int) ($activity?->getAttribute('moduleid') ?? 0);

        $nameNode = $xp->query('//wiki/name')->item(0);
        $name = (string) ($nameNode?->nodeValue ?? 'Wiki');

        // Some exports put sectionid on <activity>; default 0
        $sectionId = (int) ($xp->query('/activity')->item(0)?->getAttribute('contextid') ?? 0);

        $pages = [];
        foreach ($xp->query('//wiki/subwikis/subwiki/pages/page') as $node) {
            /** @var DOMElement $node */
            $pid   = (int) ($node->getAttribute('id') ?: 0);
            $title = (string) ($node->getElementsByTagName('title')->item(0)?->nodeValue ?? ('Wiki page '.$pid));
            $uid   = (int)    ($node->getElementsByTagName('userid')->item(0)?->nodeValue ?? 0);

            $timeCreated  = (int) ($node->getElementsByTagName('timecreated')->item(0)?->nodeValue ?? time());
            $timeModified = (int) ($node->getElementsByTagName('timemodified')->item(0)?->nodeValue ?? $timeCreated);

            // Prefer cachedcontent; fallback to the last <versions>/<version>/content
            $cached = $node->getElementsByTagName('cachedcontent')->item(0)?->nodeValue ?? '';
            $content = (string) $cached;
            $version = 1;

            $versionsEl = $node->getElementsByTagName('versions')->item(0);
            if ($versionsEl instanceof DOMElement) {
                $versNodes = $versionsEl->getElementsByTagName('version');
                if ($versNodes->length > 0) {
                    $last = $versNodes->item($versNodes->length - 1);
                    $vHtml = $last?->getElementsByTagName('content')->item(0)?->nodeValue ?? '';
                    $vNum  = (int) ($last?->getElementsByTagName('version')->item(0)?->nodeValue ?? 1);
                    if (trim((string)$vHtml) !== '') {
                        $content = (string) $vHtml;
                    }
                    if ($vNum > 0) {
                        $version = $vNum;
                    }
                }
            }

            $pages[] = [
                'id'            => $pid,
                'title'         => $title,
                'content'       => $content,
                'contentformat' => 'html',
                'version'       => $version,
                'timecreated'   => $timeCreated,
                'timemodified'  => $timeModified,
                'userid'        => $uid,
                'reflink'       => $this->slugify($title),
            ];
        }

        // Stable order
        usort($pages, fn(array $a, array $b) => $a['id'] <=> $b['id']);

        return [
            [
                'moduleid'   => $moduleId,
                'sectionid'  => $sectionId,
                'name'       => $name,
            ],
            $pages,
        ];
    }

    private function rewritePluginfileBasic(string $html, string $context): string
    {
        if ($html === '' || !str_contains($html, '@@PLUGINFILE@@')) {
            return $html;
        }

        // src/href/poster/data
        $html = (string)preg_replace(
            '~\b(src|href|poster|data)\s*=\s*([\'"])@@PLUGINFILE@@/([^\'"]+)\2~i',
            '$1=$2/document/moodle_pages/$3$2',
            $html
        );

        // url(...) in inline styles
        $html = (string)preg_replace(
            '~url\((["\']?)@@PLUGINFILE@@/([^)\'"]+)\1\)~i',
            'url($1/document/moodle_pages/$2$1)',
            $html
        );

        return $html;
    }

    /** Check if chamilo manifest has any 'announcement' kind to avoid duplicates */
    private function hasChamiloAnnouncementMeta(string $exportRoot): bool
    {
        $mf = rtrim($exportRoot, '/').'/chamilo/manifest.json';
        if (!is_file($mf)) { return false; }
        $data = json_decode((string)file_get_contents($mf), true);
        if (!is_array($data) || empty($data['items'])) { return false; }
        foreach ((array)$data['items'] as $it) {
            $k = strtolower((string)($it['kind'] ?? ''));
            if ($k === 'announcement' || $k === 'announcement') {
                return true;
            }
        }
        return false;
    }

    /** Read minimal forum header (type, name) */
    private function readForumHeader(string $moduleXml): array
    {
        $doc = $this->loadXml($moduleXml);
        $xp  = new \DOMXPath($doc);
        $type = (string) ($xp->query('//forum/type')->item(0)?->nodeValue ?? '');
        $name = (string) ($xp->query('//forum/name')->item(0)?->nodeValue ?? '');
        return ['type' => $type, 'name' => $name];
    }

    /**
     * Parse forum.xml (news) → array of announcements:
     * [
     *   [
     *     'title' => string,
     *     'html'  => string,
     *     'date'  => 'Y-m-d H:i:s',
     *     'attachments' => [ {path, filename, size, comment, asset_relpath}... ],
     *     'first_path'  => string,
     *     'first_name'  => string,
     *     'first_size'  => int,
     *   ], ...
     * ]
     */
    private function readAnnouncementsFromForum(string $moduleXml, string $exportRoot): array
    {
        $doc = $this->loadXml($moduleXml);
        $xp  = new \DOMXPath($doc);

        $anns = [];
        // One discussion = one announcement; firstpost = main message
        foreach ($xp->query('//forum/discussions/discussion') as $d) {
            /** @var \DOMElement $d */
            $title = (string) ($d->getElementsByTagName('name')->item(0)?->nodeValue ?? 'Announcement');
            $firstPostId = (int) ($d->getElementsByTagName('firstpost')->item(0)?->nodeValue ?? 0);
            $created = (int) ($d->getElementsByTagName('timemodified')->item(0)?->nodeValue ?? time());

            // find post by id
            $postNode = null;
            foreach ($d->getElementsByTagName('post') as $p) {
                /** @var \DOMElement $p */
                $pid = (int) $p->getAttribute('id');
                if ($pid === $firstPostId || ($firstPostId === 0 && !$postNode)) {
                    $postNode = $p;
                    if ($pid === $firstPostId) { break; }
                }
            }
            if (!$postNode) { continue; }

            $subject = (string) ($postNode->getElementsByTagName('subject')->item(0)?->nodeValue ?? $title);
            $message = (string) ($postNode->getElementsByTagName('message')->item(0)?->nodeValue ?? '');
            $createdPost = (int) ($postNode->getElementsByTagName('created')->item(0)?->nodeValue ?? $created);

            // Normalize HTML and rewrite @@PLUGINFILE@@
            $html = $this->rewritePluginfileForAnnouncements($message, $exportRoot, (int)$postNode->getAttribute('id'));

            // Attachments from files.xml (component=mod_forum, filearea=post, itemid=postId)
            $postId = (int) $postNode->getAttribute('id');
            $attachments = $this->extractForumPostAttachments($exportRoot, $postId);

            // First attachment info (builder-style)
            $first = $attachments[0] ?? null;
            $anns[] = [
                'title'       => $subject !== '' ? $subject : $title,
                'html'        => $html,
                'date'        => date('Y-m-d H:i:s', $createdPost ?: $created),
                'attachments' => $attachments,
                'first_path'  => (string) ($first['path'] ?? ''),
                'first_name'  => (string) ($first['filename'] ?? ''),
                'first_size'  => (int)    ($first['size'] ?? 0),
            ];
        }

        return $anns;
    }

    /**
     * Rewrite @@PLUGINFILE@@ URLs in forum messages to point to /document/announcements/{postId}/<file>.
     * The physical copy is handled by extractForumPostAttachments().
     */
    private function rewritePluginfileForAnnouncements(string $html, string $exportRoot, int $postId): string
    {
        if ($html === '' || !str_contains($html, '@@PLUGINFILE@@')) { return $html; }

        $targetBase = '/document/announcements/'.$postId.'/';

        // src/href/poster/data
        $html = (string)preg_replace(
            '~\b(src|href|poster|data)\s*=\s*([\'"])@@PLUGINFILE@@/([^\'"]+)\2~i',
            '$1=$2'.$targetBase.'$3$2',
            $html
        );

        // url(...) in inline styles
        $html = (string)preg_replace(
            '~url\((["\']?)@@PLUGINFILE@@/([^)\'"]+)\1\)~i',
            'url($1'.$targetBase.'$2$1)',
            $html
        );

        return $html;
    }

    /**
     * Copy attachments for a forum post from files.xml store to /document/announcements/{postId}/
     * and return normalized descriptors for the announcement payload.
     */
    private function extractForumPostAttachments(string $exportRoot, int $postId): array
    {
        $fx = rtrim($exportRoot, '/').'/files.xml';
        if (!is_file($fx)) { return []; }

        $doc = $this->loadXml($fx);
        $xp  = new \DOMXPath($doc);

        // files/file with these conditions
        $q = sprintf("//files/file[component='mod_forum' and filearea='post' and itemid='%d']", $postId);
        $list = $xp->query($q);

        if ($list->length === 0) { return []; }

        $destBase = rtrim($exportRoot, '/').'/document/announcements/'.$postId;
        $this->ensureDir($destBase);

        $out = [];
        foreach ($list as $f) {
            /** @var \DOMElement $f */
            $filename = (string) ($f->getElementsByTagName('filename')->item(0)?->nodeValue ?? '');
            if ($filename === '' || $filename === '.') { continue; } // skip directories

            $contenthash = (string) ($f->getElementsByTagName('contenthash')->item(0)?->nodeValue ?? '');
            $filesize    = (int)    ($f->getElementsByTagName('filesize')->item(0)?->nodeValue ?? 0);

            // Moodle file path inside backup: files/aa/bb/<contenthash>
            $src = rtrim($exportRoot, '/').'/files/'.substr($contenthash, 0, 2).'/'.substr($contenthash, 2, 2).'/'.$contenthash;
            $dst = $destBase.'/'.$filename;

            if (is_file($src)) {
                @copy($src, $dst);
            } else {
                // keep record even if missing; size may still be useful
                if ($this->debug) { error_log("MOODLE_IMPORT: forum post attachment missing file=$src"); }
            }

            $rel = 'document/announcements/'.$postId.'/'.$filename; // relative inside backup root
            $out[] = [
                'path'           => $rel,            // builder sets 'attachment_path' from first item
                'filename'       => $filename,
                'size'           => $filesize,
                'comment'        => '',
                'asset_relpath'  => $rel,            // mirrors builder's asset_relpath semantics
            ];
        }

        return $out;
    }

    private function isNewsForum(array $forumInfo): bool
    {
        $type  = strtolower((string)($forumInfo['type'] ?? ''));
        if ($type === 'news') {
            return true;
        }
        $name  = strtolower((string)($forumInfo['name'] ?? ''));
        $intro = strtolower((string)($forumInfo['description'] ?? $forumInfo['intro'] ?? ''));

        // Common names across locales
        $nameHints = ['announcement'];
        foreach ($nameHints as $h) {
            if ($name !== '' && str_contains($name, $h)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect the special "course homepage" Page exported as activities/page_0.
     * Heuristics:
     *  - directory ends with 'activities/page_0'
     *  - or <activity id="0" moduleid="0" modulename="page">
     *  - or page name equals 'Introduction' (soft signal)
     */
    private function looksLikeCourseHomepage(string $dir, string $moduleXml): bool
    {
        if (preg_match('~/activities/page_0/?$~', $dir)) {
            return true;
        }

        try {
            $doc = $this->loadXml($moduleXml);
            $xp  = new DOMXPath($doc);

            $idAttr       = $xp->query('/activity/@id')->item(0)?->nodeValue ?? null;
            $moduleIdAttr = $xp->query('/activity/@moduleid')->item(0)?->nodeValue ?? null;
            $modNameAttr  = $xp->query('/activity/@modulename')->item(0)?->nodeValue ?? null;
            $nameNode     = $xp->query('//page/name')->item(0)?->nodeValue ?? '';

            $id       = is_numeric($idAttr)       ? (int) $idAttr       : null;
            $moduleId = is_numeric($moduleIdAttr) ? (int) $moduleIdAttr : null;
            $modName  = is_string($modNameAttr)   ? strtolower($modNameAttr) : '';

            if ($id === 0 && $moduleId === 0 && $modName === 'page') {
                return true;
            }
            if (trim($nameNode) === 'Introduction') {
                // Soft hint: do not exclusively rely on this, but keep as fallback
                return true;
            }
        } catch (\Throwable $e) {
            // Be tolerant: if parsing fails, just return false
        }

        return false;
    }

    /**
     * Read <page><content>...</content></page> as decoded HTML.
     */
    private function readPageContent(string $moduleXml): string
    {
        $doc = $this->loadXml($moduleXml);
        $xp  = new DOMXPath($doc);

        $node = $xp->query('//page/content')->item(0);
        if (!$node) {
            return '';
        }

        // PageExport wrote content with htmlspecialchars; decode back to HTML.
        return html_entity_decode($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

}
