<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LearnPathCategory;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Edge\Undirected;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Set\Vertices;
use Fhaculty\Graph\Set\VerticesMap;
use Fhaculty\Graph\Vertex;

class UnserializeApi
{
    /**
     * @param string $type
     * @param string $serialized
     *
     * @return mixed
     */
    public static function unserialize($type, $serialized, $ignoreErrors = false)
    {
        $allowedClasses = [];

        switch ($type) {
            case 'career':
            case 'sequence_graph':
                $allowedClasses = [
                    Graph::class,
                    VerticesMap::class,
                    Vertices::class,
                    Edges::class,
                    Vertex::class,
                    \Fhaculty\Graph\Edge\Base::class,
                    Directed::class,
                    Undirected::class,
                ];
                break;

            case 'course':
                $allowedClasses = [
                    \Chamilo\CourseBundle\Component\CourseCopy\Course::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement::class,
                    Asset::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyLearnpath::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyTestCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseSession::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Document::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Forum::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumPost::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumTopic::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup::class,
                    LearnPathCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Link::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyInvitation::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Work::class,
                    CLpCategory::class,
                    PersistentCollection::class,
                    ArrayCollection::class,
                    stdClass::class,
                ];
            // no break

            case 'lp':
                $allowedClasses = array_merge(
                    $allowedClasses,
                    [
                        learnpath::class,
                        learnpathItem::class,
                        scorm::class,
                        scormItem::class,
                        scormMetadata::class,
                        scormOrganization::class,
                        scormResource::class,
                        Link::class,
                    ]
                );
                break;

            case 'not_allowed_classes':
            default:
                $allowedClasses = false;
        }

        try {
            $result = @unserialize(
                $serialized,
                ['allowed_classes' => $allowedClasses]
            );
        } catch (Throwable $e) {
            if ($ignoreErrors) {
                return false;
            }

            throw $e;
        }

        if ('course' === $type) {
            $visited = [];
            $result = self::normalizeCourseBackup($result, $visited);
        }

        return $result;
    }

    /**
     * Normalize legacy course backup objects after unserialize.
     * This avoids keeping Doctrine runtime collections from old backups.
     */
    private static function normalizeCourseBackup(mixed $node, array &$visited = []): mixed
    {
        if (is_array($node)) {
            foreach ($node as $key => $value) {
                $node[$key] = self::normalizeCourseBackup($value, $visited);
            }

            return $node;
        }

        if (!is_object($node)) {
            return $node;
        }

        $objectId = spl_object_id($node);
        if (isset($visited[$objectId])) {
            return $visited[$objectId];
        }

        if ($node instanceof \__PHP_Incomplete_Class) {
            $normalized = new stdClass();
            $visited[$objectId] = $normalized;

            foreach (get_object_vars($node) as $property => $value) {
                if ('__PHP_Incomplete_Class_Name' === $property) {
                    continue;
                }

                $normalizedProperty = self::normalizeSerializedPropertyName((string) $property);
                $normalized->{$normalizedProperty} = self::normalizeCourseBackup($value, $visited);
            }

            return $normalized;
        }

        $visited[$objectId] = $node;

        if ($node instanceof CLpCategory) {
            self::resetClpCategoryUsers($node);
        }

        foreach (get_object_vars($node) as $property => $value) {
            $node->{$property} = self::normalizeCourseBackup($value, $visited);
        }

        return $node;
    }

    /**
     * Legacy backups may contain a Doctrine PersistentCollection in CLpCategory::$users.
     * The relation is not needed for restore, so replace it with an empty collection.
     */
    private static function resetClpCategoryUsers(CLpCategory $category): void
    {
        $reflection = new ReflectionClass($category);

        while ($reflection) {
            if ($reflection->hasProperty('users')) {
                $property = $reflection->getProperty('users');
                $property->setValue($category, new ArrayCollection());

                return;
            }

            $reflection = $reflection->getParentClass();
        }
    }

    private static function normalizeSerializedPropertyName(string $property): string
    {
        if ('' !== $property && "\0" === $property[0]) {
            $parts = explode("\0", $property);

            return (string) end($parts);
        }

        return $property;
    }
}
