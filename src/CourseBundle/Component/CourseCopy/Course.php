<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Resource;
use UnserializeApi;

/**
 * A course-object to use in Export/Import/Backup/Copy.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class Course
{
    public array $resources;
    public string $code;
    public string $path;
    public ?string $destination_path = null;
    public ?string $destination_db = null;
    public string $encoding;
    public string $type;
    public string $backup_path = '';

    /** @var array<string,mixed> Legacy-friendly metadata bag (alias of $meta) */
    public array $info;

    /** @var array<string,mixed> Canonical metadata bag */
    public array $meta;

    /**
     * Create a new Course-object.
     */
    public function __construct()
    {
        $this->resources = [];
        $this->code = '';
        $this->path = '';
        $this->backup_path = '';
        $this->encoding = api_get_system_encoding();
        $this->type = '';

        // Keep $info and $meta in sync (alias)
        $this->info = [];
        $this->meta =& $this->info;
    }

    /**
     * Check if a resource links to the given resource.
     *
     * @param mixed $resource_to_check
     */
    public function is_linked_resource(&$resource_to_check): bool
    {
        foreach ($this->resources as $type => $resources) {
            if (\is_array($resources)) {
                foreach ($resources as $resource) {
                    Resource::setClassType($resource);
                    if ($resource->links_to($resource_to_check)) {
                        return true;
                    }
                    if (RESOURCE_LEARNPATH === $type && 'CourseCopyLearnpath' === $resource::class) {
                        if ($resource->has_item($resource_to_check)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Add a resource from a given type to this course.
     */
    public function add_resource(&$resource): void
    {
        $this->resources[$resource->get_type()][$resource->get_id()] = $resource;
    }

    /**
     * Does this course have resources?
     *
     * @param int|null $type If provided, only check that type.
     */
    public function has_resources($type = null): bool
    {
        if (null !== $type) {
            return isset($this->resources[$type])
                && \is_array($this->resources[$type])
                && \count($this->resources[$type]) > 0;
        }

        return \count($this->resources) > 0;
    }

    public function show(): void
    {
        // no-op
    }

    /**
     * Returns sample text based on the imported course content.
     * This is used for language/encoding detection when metadata is missing.
     */
    public function get_sample_text(): string
    {
        $sample_text = '';

        foreach ($this->resources as $type => &$resources) {
            if (\count($resources) <= 0) {
                continue;
            }

            foreach ($resources as $id => &$resource) {
                $title = '';
                $description = '';

                switch ($type) {
                    case RESOURCE_ANNOUNCEMENT:
                    case RESOURCE_EVENT:
                    case RESOURCE_THEMATIC:
                    case RESOURCE_WIKI:
                        $title = $this->getStrProp($resource, 'title');
                        $description = $this->getStrProp($resource, 'content');
                        break;

                    case RESOURCE_DOCUMENT:
                        // Some old exports may miss "comment" and only carry "description/summary/intro"
                        $title = $this->getStrProp($resource, 'title');
                        $description =
                            $this->getStrProp($resource, 'comment')
                                ?: $this->getStrProp($resource, 'description')
                                ?: $this->getStrProp($resource, 'summary')
                                    ?: $this->getStrProp($resource, 'intro');
                        break;

                    case RESOURCE_FORUM:
                    case RESOURCE_FORUMCATEGORY:
                    case RESOURCE_LINK:
                    case RESOURCE_LINKCATEGORY:
                    case RESOURCE_QUIZ:
                    case RESOURCE_TEST_CATEGORY:
                    case RESOURCE_WORK:
                        $title = $this->getStrProp($resource, 'title');
                        $description = $this->getStrProp($resource, 'description');
                        break;

                    case RESOURCE_FORUMPOST:
                        $title = $this->getStrProp($resource, 'title');
                        $description = $this->getStrProp($resource, 'text');
                        break;

                    case RESOURCE_SCORM:
                    case RESOURCE_FORUMTOPIC:
                        $title = $this->getStrProp($resource, 'title');
                        break;

                    case RESOURCE_GLOSSARY:
                    case RESOURCE_LEARNPATH:
                        $title = $this->getStrProp($resource, 'name');
                        $description = $this->getStrProp($resource, 'description');
                        break;

                    case RESOURCE_LEARNPATH_CATEGORY:
                        $title = $this->getStrProp($resource, 'name');
                        break;

                    case RESOURCE_QUIZQUESTION:
                        $title = $this->getStrProp($resource, 'question');
                        $description = $this->getStrProp($resource, 'description');
                        break;

                    case RESOURCE_SURVEY:
                        $title = $this->getStrProp($resource, 'title');
                        $description = $this->getStrProp($resource, 'subtitle');
                        break;

                    case RESOURCE_SURVEYQUESTION:
                        $title = $this->getStrProp($resource, 'survey_question');
                        $description = $this->getStrProp($resource, 'survey_question_comment');
                        break;

                    case RESOURCE_TOOL_INTRO:
                        $description = $this->getStrProp($resource, 'intro_text');
                        break;

                    case RESOURCE_ATTENDANCE:
                        $title = isset($resource->params['name']) && \is_string($resource->params['name']) ? $resource->params['name'] : '';
                        $description = isset($resource->params['description']) && \is_string($resource->params['description']) ? $resource->params['description'] : '';
                        break;

                    default:
                        break;
                }

                $title = $this->toTextOrEmpty($title);
                $description = $this->toTextOrEmpty($description);

                if ($title !== '') {
                    $sample_text .= $title . "\n";
                }
                if ($description !== '') {
                    $sample_text .= $description . "\n";
                }
                if ($title !== '' || $description !== '') {
                    $sample_text .= "\n";
                }
            }
        }

        return $sample_text;
    }

    /**
     * Converts to the system encoding all the language-sensitive fields in the imported course.
     */
    public function to_system_encoding(): void
    {
        foreach ($this->resources as $type => &$resources) {
            if (\count($resources) <= 0) {
                continue;
            }

            foreach ($resources as &$resource) {
                switch ($type) {
                    case RESOURCE_ANNOUNCEMENT:
                    case RESOURCE_EVENT:
                        // Defensive: only convert if present and string
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'content');
                        break;

                    case RESOURCE_DOCUMENT:
                        // Defensive normalization: backfill "comment" if missing
                        if (!property_exists($resource, 'comment') || !\is_string($resource->comment)) {
                            $fallback =
                                $this->getStrProp($resource, 'description')
                                    ?: $this->getStrProp($resource, 'summary')
                                    ?: $this->getStrProp($resource, 'intro')
                                        ?: '';
                            $resource->comment = $fallback;
                        }
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'comment'); // may be empty (but now always defined)
                        break;

                    case RESOURCE_FORUM:
                    case RESOURCE_QUIZ:
                    case RESOURCE_FORUMCATEGORY:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'description');
                        if (isset($resource->obj) && \is_object($resource->obj)) {
                            // Encode nested forum fields safely
                            foreach (['cat_title', 'cat_comment', 'title', 'description'] as $f) {
                                if (isset($resource->obj->{$f}) && \is_string($resource->obj->{$f})) {
                                    $resource->obj->{$f} = api_to_system_encoding($resource->obj->{$f}, $this->encoding);
                                }
                            }
                        }
                        break;

                    case RESOURCE_LINK:
                    case RESOURCE_LINKCATEGORY:
                    case RESOURCE_TEST_CATEGORY:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'description');
                        break;

                    case RESOURCE_FORUMPOST:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'text');
                        $this->encodeIfSet($resource, 'poster_name');
                        break;

                    case RESOURCE_FORUMTOPIC:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'topic_poster_name');
                        $this->encodeIfSet($resource, 'title_qualify');
                        break;

                    case RESOURCE_GLOSSARY:
                        $this->encodeIfSet($resource, 'name');
                        $this->encodeIfSet($resource, 'description');
                        break;

                    case RESOURCE_LEARNPATH:
                        $this->encodeIfSet($resource, 'name');
                        $this->encodeIfSet($resource, 'description');
                        $this->encodeIfSet($resource, 'content_maker');
                        $this->encodeIfSet($resource, 'content_license');
                        break;

                    case RESOURCE_QUIZQUESTION:
                        $this->encodeIfSet($resource, 'question');
                        $this->encodeIfSet($resource, 'description');
                        if (isset($resource->answers) && \is_array($resource->answers) && \count($resource->answers) > 0) {
                            foreach ($resource->answers as &$answer) {
                                // Answers array may be sparse; be defensive
                                if (isset($answer['answer']) && \is_string($answer['answer'])) {
                                    $answer['answer'] = api_to_system_encoding($answer['answer'], $this->encoding);
                                }
                                if (isset($answer['comment']) && \is_string($answer['comment'])) {
                                    $answer['comment'] = api_to_system_encoding($answer['comment'], $this->encoding);
                                }
                            }
                        }
                        break;

                    case RESOURCE_SCORM:
                        $this->encodeIfSet($resource, 'title');
                        break;

                    case RESOURCE_SURVEY:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'subtitle');
                        $this->encodeIfSet($resource, 'author');
                        $this->encodeIfSet($resource, 'intro');
                        $this->encodeIfSet($resource, 'surveythanks');
                        break;

                    case RESOURCE_SURVEYQUESTION:
                        $this->encodeIfSet($resource, 'survey_question');
                        $this->encodeIfSet($resource, 'survey_question_comment');
                        break;

                    case RESOURCE_TOOL_INTRO:
                        $this->encodeIfSet($resource, 'intro_text');
                        break;

                    case RESOURCE_WIKI:
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'content');
                        $this->encodeIfSet($resource, 'reflink');
                        break;

                    case RESOURCE_WORK:
                        $this->encodeIfSet($resource, 'url');
                        $this->encodeIfSet($resource, 'title');
                        $this->encodeIfSet($resource, 'description');
                        break;

                    default:
                        // No string fields to encode or unsupported resource type
                        break;
                }
            }
        }

        // Update current encoding after conversion
        $this->encoding = api_get_system_encoding();
    }

    /**
     * Serialize the course with the best serializer available (optionally compressed).
     */
    public static function serialize($course): string
    {
        $serialized = \extension_loaded('igbinary')
            ? igbinary_serialize($course)
            : serialize($course);

        // Compress if possible
        if (\function_exists('gzdeflate')) {
            $deflated = gzdeflate($serialized, 9);
            if ($deflated !== false) {
                $serialized = $deflated;
            }
        }

        return $serialized;
    }

    /**
     * Unserialize the course with the best serializer available.
     *
     * @return Course
     */
    public static function unserialize($course): Course
    {
        // Try to uncompress
        if (\function_exists('gzinflate')) {
            $inflated = @gzinflate($course);
            if ($inflated !== false) {
                $course = $inflated;
            }
        }

        $unserialized = \extension_loaded('igbinary')
            ? igbinary_unserialize($course)
            : UnserializeApi::unserialize('course', $course);

        /** @var Course $unserialized */
        return $unserialized;
    }

    /**
     * Safely returns a string property from a dynamic object-like resource.
     * Returns '' if missing or not a string.
     */
    private function getStrProp(object $obj, string $prop): string
    {
        return (property_exists($obj, $prop) && \is_string($obj->$prop)) ? $obj->$prop : '';
    }

    /**
     * Encode obj->$prop in-place if it exists and is a non-empty string.
     * Keeps behavior no-op for absent properties (defensive).
     */
    private function encodeIfSet(object $obj, string $prop): void
    {
        if (property_exists($obj, $prop) && \is_string($obj->$prop) && $obj->$prop !== '') {
            $obj->$prop = api_to_system_encoding($obj->$prop, $this->encoding);
        }
    }

    /**
     * Converts HTML-ish input to plain text if string, else returns ''.
     */
    private function toTextOrEmpty($value): string
    {
        return (\is_string($value) && $value !== '') ? api_html_to_text($value) : '';
    }
}
