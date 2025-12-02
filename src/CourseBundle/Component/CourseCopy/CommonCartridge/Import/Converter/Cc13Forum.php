<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Cc1p3Convert;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Database;

use const DIRECTORY_SEPARATOR;

/**
 * CC 1.3 -> Forum importer for Chamilo 2 (entity-based, no legacy helpers).
 */
class Cc13Forum extends Cc13Entities
{
    /**
     * Utility: call the first available setter among a list of candidates.
     * This helps across branches that renamed setters (e.g., setDescription vs setComment).
     */
    private function setIfCallable(object $obj, array $methodCandidates, mixed $value): bool
    {
        foreach ($methodCandidates as $m) {
            if (\is_callable([$obj, $m])) {
                $obj->{$m}($value);

                return true;
            }
        }

        return false;
    }

    public function fullPath($path, $dir_sep = DIRECTORY_SEPARATOR)
    {
        // Delegate to the hardened implementation in the base class.
        return parent::fullPath($path, $dir_sep);
    }

    public function generateData()
    {
        $data = [];
        if (!empty(Cc1p3Convert::$instances['instances']['forum'])) {
            foreach (Cc1p3Convert::$instances['instances']['forum'] as $instance) {
                $data[] = $this->getForumData($instance);
            }
        }

        return $data;
    }

    public function getForumData($instance)
    {
        $topic_data = $this->getTopicData($instance);

        $values = [];
        if (!empty($topic_data)) {
            $values = [
                'instance' => $instance['instance'],
                'title' => self::safexml($topic_data['title']),
                'description' => self::safexml($topic_data['description']),
            ];
        }

        return $values;
    }

    /**
     * Store forums using Chamilo 2 entities (no legacy array mapping).
     *
     * @param mixed $forums
     */
    public function storeForums($forums)
    {
        if (empty($forums)) {
            return true;
        }

        $em = Database::getManager();
        $course = api_get_course_entity(api_get_course_int_id());
        $session = api_get_session_entity(api_get_session_id());
        $catRepo = Container::getForumCategoryRepository();
        $forumRepo = Container::getForumRepository();

        // --- Find or create the "CC1p3" forum category under this course ---
        $existing = $this->findCategoryUnderCourse($catRepo, 'CC1p3', $course, $session);

        if (!$existing instanceof CForumCategory) {
            // Create category like courserestore does
            $existing = (new CForumCategory())
                ->setTitle('CC1p3')          // title
                ->setCatComment('')          // comment/description
                ->setParent($course)         // attach to course resource tree
                ->addCourseLink($course, $session)
            ;

            // Use repository create() to stay consistent with Chamilo 2 style
            $catRepo->create($existing);
            $em->flush();
        }

        // --- Create forums in that category ---
        foreach ($forums as $forum) {
            $title = trim((string) ($forum['title'] ?? 'Untitled Topic'));
            $desc = (string) ($forum['description'] ?? '');

            $entity = (new CForum())
                ->setTitle($title)
                // In some branches the setter is setComment(), in others setForumComment().
                // If your branch uses setComment(), change the line below accordingly.
                ->setForumComment($desc)
                ->setForumCategory($existing)
                ->setAllowAttachments(1)
                ->setAllowNewThreads(1)
                ->setDefaultView('flat')
                ->setModerated(false)
                // Parent in the resource tree: the category (like courserestore does)
                ->setParent($existing)
                ->addCourseLink($course, $session)
            ;

            $forumRepo->create($entity);
            $em->flush();
        }

        return true;
    }

    /**
     * Helper: find a forum category by title under a course's resource tree.
     * Tries repo helper (if available) and falls back to a QueryBuilder join.
     *
     * @param mixed $catRepo
     * @param mixed $courseEntity
     * @param mixed $sessionEntity
     */
    private function findCategoryUnderCourse($catRepo, string $title, $courseEntity, $sessionEntity): ?CForumCategory
    {
        // 1) Preferred: repository helper present in Chamilo 2 stacks
        if ($catRepo && \is_callable([$catRepo, 'findCourseResourceByTitle'])) {
            // signature: (title, parentNode, courseEntity, sessionEntity, groupEntity)
            return $catRepo->findCourseResourceByTitle(
                $title,
                $courseEntity->getResourceNode(),
                $courseEntity,
                $sessionEntity,
                api_get_group_entity(0)
            );
        }

        // 2) Fallback: manual DQL with join to resourceNode and its parent
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('c')
            ->from(CForumCategory::class, 'c')
            ->join('c.resourceNode', 'rn')
            // 'rn.parent = :parentNode' is valid in DQL; do NOT use "resourceNode.parent" in findOneBy
            ->where('c.title = :title')
            ->andWhere('rn.parent = :parentNode')
            ->setParameter('title', $title)
            ->setParameter('parentNode', $courseEntity->getResourceNode())
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getTopicData($instance)
    {
        $topic_data = [];
        $topic_text = '';

        $topic_file = $this->getExternalXml($instance['resource_identifier']);

        if (!empty($topic_file)) {
            $topic_file_path = Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$topic_file;
            $topic = $this->loadXmlResource($topic_file_path);

            if (!empty($topic)) {
                $xpath = Cc1p3Convert::newxPath($topic, Cc1p3Convert::$forumns);

                $topic_title = $xpath->query('/dt:topic/dt:title');
                $topic_title = ($topic_title->length > 0 && !empty($topic_title->item(0)->nodeValue))
                    ? $topic_title->item(0)->nodeValue
                    : 'Untitled Topic';

                $topic_text_node = $xpath->query('/dt:topic/dt:text');
                $topic_text = !empty($topic_text_node->item(0)->nodeValue)
                    ? $this->updateSources($topic_text_node->item(0)->nodeValue, \dirname($topic_file))
                    : '';
                $topic_text = '' !== $topic_text ? str_replace('%24', '$', $this->includeTitles($topic_text)) : '';

                $topic_data['title'] = $topic_title;
                $topic_data['description'] = $topic_text;

                // Attachments section only if $xpath was defined.
                $topic_attachments = $xpath->query('/dt:topic/dt:attachments/dt:attachment/@href');
                if ($topic_attachments && $topic_attachments->length > 0) {
                    $attachment_html = '';
                    foreach ($topic_attachments as $file) {
                        $attachment_html .= $this->generateAttachmentHtml($this->fullPath($file->nodeValue, '/'));
                    }
                    $topic_data['description'] = $topic_text.
                        ('' !== $attachment_html ? '<p>Attachments:</p>'.$attachment_html : '');
                }
            }
        }

        return $topic_data;
    }

    private function generateAttachmentHtml(string $filename, ?string $rootPath = null)
    {
        $images_extensions = ['gif', 'jpeg', 'jpg', 'jif', 'jfif', 'png', 'bmp', 'webp'];
        $fileinfo = pathinfo($filename);
        $rootPath = $rootPath ?? '';

        $basename = $fileinfo['basename'] ?? basename((string) $filename);
        $ext = strtolower($fileinfo['extension'] ?? '');

        if (\in_array($ext, $images_extensions, true)) {
            return '<img src="'.$rootPath.$filename.'" title="'.$basename.'" alt="'.$basename.'" /><br />';
        }

        return '<a href="'.$rootPath.$filename.'" title="'.$basename.'" alt="'.$basename.'">'.$basename.'</a><br />';
    }
}
