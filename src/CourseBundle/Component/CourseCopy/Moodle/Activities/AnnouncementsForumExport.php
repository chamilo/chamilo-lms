<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const PHP_EOL;

/**
 * Exports Chamilo announcements as a Moodle News forum activity.
 */
class AnnouncementsForumExport extends ActivityExport
{
    public const DEFAULT_MODULE_ID = 48000001;

    private static int $attachmentFileSeq = 0;

    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $moduleId = $moduleId > 0 ? $moduleId : self::DEFAULT_MODULE_ID;
        $forumDir = $this->prepareActivityDirectory($exportDir, 'forum', $moduleId);

        $forumData = $this->getDataFromAnnouncements($moduleId, $sectionId);

        $this->createForumXml($forumData, $forumDir);
        $this->createModuleXml($forumData, $forumDir);
        $this->createInforefXml($forumData, $forumDir);
        $this->createFiltersXml($forumData, $forumDir);
        $this->createGradesXml($forumData, $forumDir);
        $this->createGradeHistoryXml($forumData, $forumDir);
        $this->createCompletionXml($forumData, $forumDir);
        $this->createCommentsXml($forumData, $forumDir);
        $this->createCompetenciesXml($forumData, $forumDir);
        $this->createRolesXml($forumData, $forumDir);
        $this->createCalendarXml($forumData, $forumDir);

        if (!empty($forumData['files']) && \is_array($forumData['files'])) {
            $this->appendFilesToBackup($forumData['files'], $exportDir);
        }
    }

    private function getDataFromAnnouncements(int $moduleId, int $sectionId): array
    {
        $announcements = $this->collectAnnouncements();

        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);
        if ($adminId <= 0) {
            $adminId = 1;
        }

        $threads = [];
        $files = [];
        $users = [$adminId => true];

        $discussionId = 1;
        $postId = 1;
        $timeCreated = 0;
        $timeModified = 0;

        foreach ($announcements as $announcement) {
            $created = (int) ($announcement['created_ts'] ?? time());
            $subject = (string) ($announcement['subject'] ?? 'Announcement');
            $message = (string) ($announcement['message'] ?? '');
            $attachments = (array) ($announcement['attachments'] ?? []);

            $postFiles = $this->buildAnnouncementAttachmentFiles(
                $attachments,
                $moduleId,
                $postId,
                $adminId,
                $created
            );

            $firstAttachmentName = '';
            foreach ($postFiles as $file) {
                $files[] = $file;
                if ('' === $firstAttachmentName && !empty($file['filename'])) {
                    $firstAttachmentName = (string) $file['filename'];
                }
            }

            $threads[] = [
                'id' => $discussionId,
                'title' => $subject,
                'userid' => $adminId,
                'timemodified' => $created,
                'usermodified' => $adminId,
                'firstpost' => $postId,
                'posts' => [[
                    'id' => $postId,
                    'parent' => 0,
                    'userid' => $adminId,
                    'created' => $created,
                    'modified' => $created,
                    'mailed' => 0,
                    'subject' => $subject,
                    'message' => $message,
                    'attachment' => $firstAttachmentName,
                ]],
            ];

            $timeCreated = 0 === $timeCreated ? $created : min($timeCreated, $created);
            $timeModified = max($timeModified, $created);

            $discussionId++;
            $postId++;
        }

        return [
            'id' => $moduleId,
            'moduleid' => $moduleId,
            'modulename' => 'forum',
            'contextid' => $moduleId,
            'sectionid' => $sectionId,
            'sectionnumber' => $sectionId,
            'name' => 'Announcements',
            'description' => '',
            'type' => 'news',
            'forcesubscribe' => 1,
            'timecreated' => $timeCreated > 0 ? $timeCreated : time(),
            'timemodified' => $timeModified > 0 ? $timeModified : time(),
            'threads' => $threads,
            'users' => array_keys($users),
            'files' => $files,
        ];
    }

    private function createForumXml(array $data, string $forumDir): void
    {
        $introCdata = '<![CDATA['.(string) ($data['description'] ?? '').']]>';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$data['id'].'" moduleid="'.$data['moduleid'].'" modulename="forum" contextid="'.$data['contextid'].'">'.PHP_EOL;
        $xml .= '  <forum id="'.$data['id'].'">'.PHP_EOL;
        $xml .= '    <type>'.htmlspecialchars((string) ($data['type'] ?? 'news')).'</type>'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) ($data['name'] ?? 'Announcements')).'</name>'.PHP_EOL;
        $xml .= '    <intro>'.$introCdata.'</intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <duedate>0</duedate>'.PHP_EOL;
        $xml .= '    <cutoffdate>0</cutoffdate>'.PHP_EOL;
        $xml .= '    <assessed>0</assessed>'.PHP_EOL;
        $xml .= '    <assesstimestart>0</assesstimestart>'.PHP_EOL;
        $xml .= '    <assesstimefinish>0</assesstimefinish>'.PHP_EOL;
        $xml .= '    <scale>100</scale>'.PHP_EOL;
        $xml .= '    <maxbytes>512000</maxbytes>'.PHP_EOL;
        $xml .= '    <maxattachments>9</maxattachments>'.PHP_EOL;
        $xml .= '    <forcesubscribe>'.(int) ($data['forcesubscribe'] ?? 1).'</forcesubscribe>'.PHP_EOL;
        $xml .= '    <trackingtype>1</trackingtype>'.PHP_EOL;
        $xml .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xml .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) ($data['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xml .= '    <warnafter>0</warnafter>'.PHP_EOL;
        $xml .= '    <blockafter>0</blockafter>'.PHP_EOL;
        $xml .= '    <blockperiod>0</blockperiod>'.PHP_EOL;
        $xml .= '    <completiondiscussions>0</completiondiscussions>'.PHP_EOL;
        $xml .= '    <completionreplies>0</completionreplies>'.PHP_EOL;
        $xml .= '    <completionposts>0</completionposts>'.PHP_EOL;
        $xml .= '    <displaywordcount>0</displaywordcount>'.PHP_EOL;
        $xml .= '    <lockdiscussionafter>0</lockdiscussionafter>'.PHP_EOL;
        $xml .= '    <grade_forum>0</grade_forum>'.PHP_EOL;
        $xml .= '    <discussions>'.PHP_EOL;

        foreach ((array) ($data['threads'] ?? []) as $thread) {
            $xml .= '      <discussion id="'.(int) ($thread['id'] ?? 0).'">'.PHP_EOL;
            $xml .= '        <name>'.htmlspecialchars((string) ($thread['title'] ?? 'Announcement')).'</name>'.PHP_EOL;
            $xml .= '        <firstpost>'.(int) ($thread['firstpost'] ?? 0).'</firstpost>'.PHP_EOL;
            $xml .= '        <userid>'.(int) ($thread['userid'] ?? 0).'</userid>'.PHP_EOL;
            $xml .= '        <groupid>-1</groupid>'.PHP_EOL;
            $xml .= '        <assessed>0</assessed>'.PHP_EOL;
            $xml .= '        <timemodified>'.(int) ($thread['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
            $xml .= '        <usermodified>'.(int) ($thread['usermodified'] ?? 0).'</usermodified>'.PHP_EOL;
            $xml .= '        <timestart>0</timestart>'.PHP_EOL;
            $xml .= '        <timeend>0</timeend>'.PHP_EOL;
            $xml .= '        <pinned>0</pinned>'.PHP_EOL;
            $xml .= '        <timelocked>0</timelocked>'.PHP_EOL;
            $xml .= '        <posts>'.PHP_EOL;

            foreach ((array) ($thread['posts'] ?? []) as $post) {
                $xml .= '          <post id="'.(int) ($post['id'] ?? 0).'">'.PHP_EOL;
                $xml .= '            <parent>'.(int) ($post['parent'] ?? 0).'</parent>'.PHP_EOL;
                $xml .= '            <userid>'.(int) ($post['userid'] ?? 0).'</userid>'.PHP_EOL;
                $xml .= '            <created>'.(int) ($post['created'] ?? time()).'</created>'.PHP_EOL;
                $xml .= '            <modified>'.(int) ($post['modified'] ?? time()).'</modified>'.PHP_EOL;
                $xml .= '            <mailed>'.(int) ($post['mailed'] ?? 0).'</mailed>'.PHP_EOL;
                $xml .= '            <subject>'.htmlspecialchars((string) ($post['subject'] ?? 'Announcement')).'</subject>'.PHP_EOL;
                $xml .= '            <message><![CDATA['.((string) ($post['message'] ?? '')).']]></message>'.PHP_EOL;
                $xml .= '            <messageformat>1</messageformat>'.PHP_EOL;
                $xml .= '            <messagetrust>0</messagetrust>'.PHP_EOL;
                $xml .= '            <attachment>'.htmlspecialchars((string) ($post['attachment'] ?? '')).'</attachment>'.PHP_EOL;
                $xml .= '            <totalscore>0</totalscore>'.PHP_EOL;
                $xml .= '            <mailnow>0</mailnow>'.PHP_EOL;
                $xml .= '            <privatereplyto>0</privatereplyto>'.PHP_EOL;
                $xml .= '            <wordcount>0</wordcount>'.PHP_EOL;
                $xml .= '            <charcount>0</charcount>'.PHP_EOL;
                $xml .= '            <ratings></ratings>'.PHP_EOL;
                $xml .= '          </post>'.PHP_EOL;
            }

            $xml .= '        </posts>'.PHP_EOL;
            $xml .= '        <discussion_subs>'.PHP_EOL;
            $xml .= '          <discussion_sub id="'.(int) ($thread['id'] ?? 0).'">'.PHP_EOL;
            $xml .= '            <userid>'.(int) ($thread['userid'] ?? 0).'</userid>'.PHP_EOL;
            $xml .= '            <preference>'.(int) ($thread['timemodified'] ?? time()).'</preference>'.PHP_EOL;
            $xml .= '          </discussion_sub>'.PHP_EOL;
            $xml .= '        </discussion_subs>'.PHP_EOL;
            $xml .= '      </discussion>'.PHP_EOL;
        }

        $xml .= '    </discussions>'.PHP_EOL;
        $xml .= '  </forum>'.PHP_EOL;
        $xml .= '</activity>'.PHP_EOL;

        $this->createXmlFile('forum', $xml, $forumDir);
    }

    protected function createInforefXml(array $references, string $directory): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<inforef>'.PHP_EOL;

        if (!empty($references['users']) && \is_array($references['users'])) {
            $xml .= '  <userref>'.PHP_EOL;
            foreach ($references['users'] as $userId) {
                $xml .= '    <user>'.PHP_EOL;
                $xml .= '      <id>'.(int) $userId.'</id>'.PHP_EOL;
                $xml .= '    </user>'.PHP_EOL;
            }
            $xml .= '  </userref>'.PHP_EOL;
        }

        if (!empty($references['files']) && \is_array($references['files'])) {
            $xml .= '  <fileref>'.PHP_EOL;
            foreach ($references['files'] as $file) {
                $fileId = (int) ($file['id'] ?? 0);
                if ($fileId <= 0) {
                    continue;
                }
                $xml .= '    <file>'.PHP_EOL;
                $xml .= '      <id>'.$fileId.'</id>'.PHP_EOL;
                $xml .= '    </file>'.PHP_EOL;
            }
            $xml .= '  </fileref>'.PHP_EOL;
        }

        $xml .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xml, $directory);
    }

    private function collectAnnouncements(): array
    {
        $resources = \is_array($this->course->resources ?? null) ? $this->course->resources : [];
        $assets = \is_array($resources['asset'] ?? null) ? $resources['asset'] : [];

        $bag =
            ($resources[\defined('RESOURCE_ANNOUNCEMENT') ? RESOURCE_ANNOUNCEMENT : 'announcements'] ?? null)
            ?? ($resources['announcements'] ?? null)
            ?? ($resources['announcement'] ?? null)
            ?? [];

        $out = [];
        foreach ((array) $bag as $maybe) {
            $announcement = $this->unwrap($maybe);
            if (!$announcement) {
                continue;
            }

            $title = $this->firstNonEmpty($announcement, ['title', 'name', 'subject'], 'Announcement');
            $html = $this->firstNonEmpty($announcement, ['content', 'message', 'description', 'text', 'body'], '');
            if ('' === $html) {
                continue;
            }

            $out[] = [
                'subject' => $title,
                'message' => $html,
                'created_ts' => $this->firstTimestamp($announcement, ['created', 'ctime', 'date', 'add_date', 'time']),
                'attachments' => $this->collectAnnouncementAttachments($announcement, $assets),
            ];
        }

        return $out;
    }

    private function collectAnnouncementAttachments(object $announcement, array $assets): array
    {
        $attachments = [];
        $rawAttachments = $announcement->attachments ?? [];

        foreach ((array) $rawAttachments as $rawAttachment) {
            $row = \is_object($rawAttachment) ? get_object_vars($rawAttachment) : (array) $rawAttachment;
            $assetRel = trim((string) ($row['asset_relpath'] ?? ''), '/');
            $asset = '' !== $assetRel ? ($assets[$assetRel] ?? null) : null;
            $absPath = \is_array($asset) ? (string) ($asset['abs'] ?? '') : '';

            if ('' === $absPath || !is_file($absPath)) {
                continue;
            }

            $filename = trim((string) ($row['filename'] ?? ''));
            if ('' === $filename) {
                $filename = basename((string) ($row['path'] ?? $assetRel));
            }
            if ('' === $filename) {
                $filename = basename($absPath);
            }

            $size = (int) ($row['size'] ?? 0);
            if ($size <= 0) {
                $size = (int) (\is_array($asset) ? ($asset['size'] ?? 0) : 0);
            }
            if ($size <= 0 && is_file($absPath)) {
                $size = (int) filesize($absPath);
            }

            $attachments[] = [
                'filename' => $filename,
                'comment' => (string) ($row['comment'] ?? ''),
                'size' => $size,
                'abs_path' => $absPath,
            ];
        }

        if (!empty($attachments)) {
            return $attachments;
        }

        $fallbackFilename = trim((string) ($announcement->attachment_filename ?? ''));
        if ('' === $fallbackFilename) {
            return [];
        }

        $fallbackRelPath = ltrim((string) ($announcement->attachment_path ?? $fallbackFilename), '/');
        $fallbackAssetRel = trim('upload/announcements/'.$fallbackRelPath, '/');
        $fallbackAsset = $assets[$fallbackAssetRel] ?? null;
        $fallbackAbsPath = \is_array($fallbackAsset) ? (string) ($fallbackAsset['abs'] ?? '') : '';
        if ('' === $fallbackAbsPath || !is_file($fallbackAbsPath)) {
            return [];
        }

        $fallbackSize = (int) ($announcement->attachment_size ?? 0);
        if ($fallbackSize <= 0) {
            $fallbackSize = (int) (\is_array($fallbackAsset) ? ($fallbackAsset['size'] ?? 0) : 0);
        }
        if ($fallbackSize <= 0 && is_file($fallbackAbsPath)) {
            $fallbackSize = (int) filesize($fallbackAbsPath);
        }

        return [[
            'filename' => $fallbackFilename,
            'comment' => (string) ($announcement->attachment_comment ?? ''),
            'size' => $fallbackSize,
            'abs_path' => $fallbackAbsPath,
        ]];
    }

    private function buildAnnouncementAttachmentFiles(
        array $attachments,
        int $moduleId,
        int $postId,
        int $userId,
        int $timestamp
    ): array {
        $files = [];

        foreach ($attachments as $attachment) {
            $absPath = (string) ($attachment['abs_path'] ?? '');
            if ('' === $absPath || !is_file($absPath)) {
                continue;
            }

            $filename = trim((string) ($attachment['filename'] ?? ''));
            if ('' === $filename) {
                $filename = basename($absPath);
            }
            if ('' === $filename) {
                continue;
            }

            $contentHash = sha1_file($absPath);
            if (false === $contentHash || '' === $contentHash) {
                continue;
            }

            $size = (int) ($attachment['size'] ?? 0);
            if ($size <= 0) {
                $size = (int) filesize($absPath);
            }

            $files[] = [
                'id' => $this->buildAttachmentFileId(),
                'contenthash' => $contentHash,
                'contextid' => $moduleId,
                'component' => 'mod_forum',
                'filearea' => 'attachment',
                'itemid' => $postId,
                'filepath' => '/',
                'filename' => $filename,
                'userid' => $userId,
                'filesize' => $size,
                'mimetype' => $this->detectMimeType($absPath),
                'status' => 0,
                'timecreated' => $timestamp,
                'timemodified' => $timestamp,
                'source' => $filename,
                'author' => 'Unknown',
                'license' => 'allrightsreserved',
                'sortorder' => 0,
                'repositorytype' => '$@NULL@$',
                'repositoryid' => '$@NULL@$',
                'reference' => '$@NULL@$',
                'abs_path' => $absPath,
            ];
        }

        return $files;
    }

    private function appendFilesToBackup(array $files, string $exportDir): void
    {
        if (empty($files)) {
            return;
        }

        $filesXmlPath = rtrim($exportDir, '/').'/files.xml';
        $filesDir = rtrim($exportDir, '/').'/files';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (is_file($filesXmlPath)) {
            $loaded = @$dom->load($filesXmlPath);
            if (!$loaded || !$dom->documentElement) {
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->appendChild($dom->createElement('files'));
            }
        } else {
            $dom->appendChild($dom->createElement('files'));
        }

        $root = $dom->documentElement;
        if (!$root) {
            return;
        }

        if (!is_dir($filesDir)) {
            @mkdir($filesDir, (int) octdec('0775'), true);
        }

        foreach ($files as $file) {
            $fileId = (int) ($file['id'] ?? 0);
            $contentHash = (string) ($file['contenthash'] ?? '');
            $absPath = (string) ($file['abs_path'] ?? '');

            if ($fileId <= 0 || '' === $contentHash || '' === $absPath || !is_file($absPath)) {
                continue;
            }

            $fileNode = $dom->createElement('file');
            $fileNode->setAttribute('id', (string) $fileId);

            $fields = [
                'contenthash',
                'contextid',
                'component',
                'filearea',
                'itemid',
                'filepath',
                'filename',
                'userid',
                'filesize',
                'mimetype',
                'status',
                'timecreated',
                'timemodified',
                'source',
                'author',
                'license',
                'sortorder',
                'repositorytype',
                'repositoryid',
                'reference',
            ];

            foreach ($fields as $field) {
                $value = (string) ($file[$field] ?? '');
                $fileNode->appendChild($dom->createElement($field, $value));
            }

            $root->appendChild($fileNode);

            $prefixDir = $filesDir.'/'.substr($contentHash, 0, 2);
            if (!is_dir($prefixDir)) {
                @mkdir($prefixDir, (int) octdec('0775'), true);
            }

            $destination = $prefixDir.'/'.$contentHash;
            if (!is_file($destination)) {
                @copy($absPath, $destination);
            }
        }

        $dom->save($filesXmlPath);
    }

    private function buildAttachmentFileId(): int
    {
        self::$attachmentFileSeq++;

        return 1950000000 + self::$attachmentFileSeq;
    }

    private function detectMimeType(string $absPath): string
    {
        try {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($absPath);
            if ('' !== $mime) {
                return $mime;
            }
        } catch (\Throwable) {
        }

        return 'application/octet-stream';
    }

    private function unwrap(mixed $maybe): ?object
    {
        if (\is_object($maybe)) {
            return (isset($maybe->obj) && \is_object($maybe->obj)) ? $maybe->obj : $maybe;
        }
        if (\is_array($maybe)) {
            return (object) $maybe;
        }

        return null;
    }

    private function firstNonEmpty(object $object, array $keys, string $fallback = ''): string
    {
        foreach ($keys as $key) {
            if (!empty($object->{$key}) && \is_string($object->{$key})) {
                $value = trim((string) $object->{$key});
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    private function firstTimestamp(object $object, array $keys): int
    {
        foreach ($keys as $key) {
            if (!isset($object->{$key})) {
                continue;
            }

            $value = $object->{$key};
            if (\is_numeric($value)) {
                return (int) $value;
            }
            if (\is_string($value)) {
                $timestamp = strtotime($value);
                if (false !== $timestamp) {
                    return (int) $timestamp;
                }
            }
        }

        return time();
    }
}
