<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Resource;

/**
 * A course-object to use in Export/Import/Backup/Copy.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class Course
{
    public $resources;
    public $code;
    public $path;
    public $destination_path;
    public $destination_db;
    public $encoding;

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
    }

    /**
     * Check if a resource links to the given resource.
     */
    public function is_linked_resource(&$resource_to_check)
    {
        foreach ($this->resources as $type => $resources) {
            if (is_array($resources)) {
                foreach ($resources as $resource) {
                    Resource::setClassType($resource);
                    if ($resource->links_to($resource_to_check)) {
                        return true;
                    }
                    if ($type == RESOURCE_LEARNPATH && get_class($resource) == 'CourseCopyLearnpath') {
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
     *
     * @param $resource
     */
    public function add_resource(&$resource)
    {
        $this->resources[$resource->get_type()][$resource->get_id()] = $resource;
    }

    /**
     * Does this course has resources?
     *
     * @param int $type Check if this course has resources of the
     *                  given type. If no type is given, check if course has resources of any
     *                  type.
     *
     * @return bool
     */
    public function has_resources($type = null)
    {
        if ($type != null) {
            return
                isset($this->resources[$type]) && is_array($this->resources[$type]) && (
                    count($this->resources[$type]) > 0
                );
        }

        return count($this->resources) > 0;
    }

    public function show()
    {
    }

    /**
     * Returns sample text based on the imported course content.
     * This sample text is to be used for course language or encoding
     * detection if there is missing (meta)data in the archive.
     *
     * @return string the resulting sample text extracted from some common resources' data fields
     */
    public function get_sample_text()
    {
        $sample_text = '';
        foreach ($this->resources as $type => &$resources) {
            if (count($resources) > 0) {
                foreach ($resources as $id => &$resource) {
                    $title = '';
                    $description = '';
                    switch ($type) {
                        case RESOURCE_ANNOUNCEMENT:
                        case RESOURCE_EVENT:
                        case RESOURCE_THEMATIC:
                        case RESOURCE_WIKI:
                            $title = $resource->title;
                            $description = $resource->content;
                            break;
                        case RESOURCE_DOCUMENT:
                            $title = $resource->title;
                            $description = $resource->comment;
                            break;
                        case RESOURCE_FORUM:
                        case RESOURCE_FORUMCATEGORY:
                        case RESOURCE_LINK:
                        case RESOURCE_LINKCATEGORY:
                        case RESOURCE_QUIZ:
                        case RESOURCE_TEST_CATEGORY:
                        case RESOURCE_WORK:
                            $title = $resource->title;
                            $description = $resource->description;
                            break;
                        case RESOURCE_FORUMPOST:
                            $title = $resource->title;
                            $description = $resource->text;
                            break;
                        case RESOURCE_SCORM:
                        case RESOURCE_FORUMTOPIC:
                            $title = $resource->title;
                            break;
                        case RESOURCE_GLOSSARY:
                        case RESOURCE_LEARNPATH:
                            $title = $resource->name;
                            $description = $resource->description;
                            break;
                        case RESOURCE_LEARNPATH_CATEGORY:
                            $title = $resource->name;
                            break;
                        case RESOURCE_QUIZQUESTION:
                            $title = $resource->question;
                            $description = $resource->description;
                            break;
                        case RESOURCE_SURVEY:
                            $title = $resource->title;
                            $description = $resource->subtitle;
                            break;
                        case RESOURCE_SURVEYQUESTION:
                            $title = $resource->survey_question;
                            $description = $resource->survey_question_comment;
                            break;
                        case RESOURCE_TOOL_INTRO:
                            $description = $resource->intro_text;
                            break;
                        case RESOURCE_ATTENDANCE:
                            $title = $resource->params['name'];
                            $description = $resource->params['description'];
                            break;
                        default:
                            break;
                    }

                    $title = api_html_to_text($title);
                    $description = api_html_to_text($description);

                    if (!empty($title)) {
                        $sample_text .= $title."\n";
                    }
                    if (!empty($description)) {
                        $sample_text .= $description."\n";
                    }
                    if (!empty($title) || !empty($description)) {
                        $sample_text .= "\n";
                    }
                }
            }
        }

        return $sample_text;
    }

    /**
     * Converts to the system encoding all the language-sensitive fields in the imported course.
     */
    public function to_system_encoding()
    {
        if (api_equal_encodings($this->encoding, api_get_system_encoding())) {
            return;
        }

        foreach ($this->resources as $type => &$resources) {
            if (count($resources) > 0) {
                foreach ($resources as &$resource) {
                    switch ($type) {
                        case RESOURCE_ANNOUNCEMENT:
                        case RESOURCE_EVENT:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->content = api_to_system_encoding($resource->content, $this->encoding);
                            break;
                        case RESOURCE_DOCUMENT:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->comment = api_to_system_encoding($resource->comment, $this->encoding);
                            break;
                        case RESOURCE_FORUM:
                        case RESOURCE_QUIZ:
                        case RESOURCE_FORUMCATEGORY:
                        case RESOURCE_LINK:
                        case RESOURCE_LINKCATEGORY:
                        case RESOURCE_TEST_CATEGORY:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->description = api_to_system_encoding($resource->description, $this->encoding);
                            break;
                        case RESOURCE_FORUMPOST:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->text = api_to_system_encoding($resource->text, $this->encoding);
                            $resource->poster_name = api_to_system_encoding($resource->poster_name, $this->encoding);
                            break;
                        case RESOURCE_FORUMTOPIC:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->topic_poster_name = api_to_system_encoding($resource->topic_poster_name, $this->encoding);
                            $resource->title_qualify = api_to_system_encoding($resource->title_qualify, $this->encoding);
                            break;
                        case RESOURCE_GLOSSARY:
                            $resource->name = api_to_system_encoding($resource->name, $this->encoding);
                            $resource->description = api_to_system_encoding($resource->description, $this->encoding);
                            break;
                        case RESOURCE_LEARNPATH:
                            $resource->name = api_to_system_encoding($resource->name, $this->encoding);
                            $resource->description = api_to_system_encoding($resource->description, $this->encoding);
                            $resource->content_maker = api_to_system_encoding($resource->content_maker, $this->encoding);
                            $resource->content_license = api_to_system_encoding($resource->content_license, $this->encoding);
                            break;
                        case RESOURCE_QUIZQUESTION:
                            $resource->question = api_to_system_encoding($resource->question, $this->encoding);
                            $resource->description = api_to_system_encoding($resource->description, $this->encoding);
                            if (is_array($resource->answers) && count($resource->answers) > 0) {
                                foreach ($resource->answers as &$answer) {
                                    $answer['answer'] = api_to_system_encoding($answer['answer'], $this->encoding);
                                    $answer['comment'] = api_to_system_encoding($answer['comment'], $this->encoding);
                                }
                            }
                            break;
                        case RESOURCE_SCORM:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            break;
                        case RESOURCE_SURVEY:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->subtitle = api_to_system_encoding($resource->subtitle, $this->encoding);
                            $resource->author = api_to_system_encoding($resource->author, $this->encoding);
                            $resource->intro = api_to_system_encoding($resource->intro, $this->encoding);
                            $resource->surveythanks = api_to_system_encoding($resource->surveythanks, $this->encoding);
                            break;
                        case RESOURCE_SURVEYQUESTION:
                            $resource->survey_question = api_to_system_encoding($resource->survey_question, $this->encoding);
                            $resource->survey_question_comment = api_to_system_encoding($resource->survey_question_comment, $this->encoding);
                            break;
                        case RESOURCE_TOOL_INTRO:
                            $resource->intro_text = api_to_system_encoding($resource->intro_text, $this->encoding);
                            break;
                        case RESOURCE_WIKI:
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->content = api_to_system_encoding($resource->content, $this->encoding);
                            $resource->reflink = api_to_system_encoding($resource->reflink, $this->encoding);
                            break;
                        case RESOURCE_WORK:
                            $resource->url = api_to_system_encoding($resource->url, $this->encoding);
                            $resource->title = api_to_system_encoding($resource->title, $this->encoding);
                            $resource->description = api_to_system_encoding($resource->description, $this->encoding);
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        $this->encoding = api_get_system_encoding();
    }

    /**
     * Serialize the course with the best serializer available.
     *
     * @return string
     */
    public static function serialize($course)
    {
        if (extension_loaded('igbinary')) {
            $serialized = igbinary_serialize($course);
        } else {
            $serialized = serialize($course);
        }

        // Compress
        if (function_exists('gzdeflate')) {
            $deflated = gzdeflate($serialized, 9);
            if ($deflated !== false) {
                $deflated = $serialized;
            }
        }

        return $serialized;
    }

    /**
     * Unserialize the course with the best serializer available.
     *
     * @param string $course
     *
     * @return Course
     */
    public static function unserialize($course)
    {
        // Uncompress
        if (function_exists('gzdeflate')) {
            $inflated = @gzinflate($course);
            if ($inflated !== false) {
                $course = $inflated;
            }
        }

        if (extension_loaded('igbinary')) {
            $unserialized = igbinary_unserialize($course);
        } else {
            $unserialized = \UnserializeApi::unserialize(
                'course',
                $course
            );
        }

        return $unserialized;
    }
}
