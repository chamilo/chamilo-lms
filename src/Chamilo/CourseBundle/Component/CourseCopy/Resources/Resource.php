<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class Resource
 * Representation of a resource in a Chamilo-course.
 * This is a base class of which real resource-classes (for Links,
 * Documents,...) should be derived.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>s
 *
 * @todo Use the globally defined constants voor tools and remove the RESOURCE_*
 * constants
 */
class Resource
{
    /**
     * The id from this resource in the source course.
     */
    public $source_id;

    /**
     * The id from this resource in the destination course.
     */
    public $destination_id;

    /**
     * The type of this resource.
     */
    public $type;

    /**
     * Linked resources.
     */
    public $linked_resources;

    /**
     * The properties of this resource.
     */
    public $item_properties;
    public $obj;
    public $extraFields;

    /**
     * Create a new Resource.
     *
     * @param int $id   the id of this resource in the source course
     * @param int $type the type of this resource
     */
    public function __construct($id, $type)
    {
        $this->source_id = $id;
        $this->type = $type;
        $this->destination_id = -1;
        $this->linked_resources = [];
        $this->item_properties = [];
        $this->extraFields = [];
    }

    /**
     * Add linked resource.
     */
    public function add_linked_resource($type, $id)
    {
        $this->linked_resources[$type][] = $id;
    }

    /**
     * Get linked resources.
     */
    public function get_linked_resources()
    {
        return $this->linked_resources;
    }

    /**
     * Checks if this resource links to a given resource.
     */
    public function links_to(&$resource)
    {
        self::setClassType($resource);
        $type = $resource->get_type();
        if (isset($this->linked_resources[$type]) &&
            is_array($this->linked_resources[$type])
        ) {
            return in_array(
                $resource->get_id(),
                $this->linked_resources[$type]
            );
        }

        return false;
    }

    /**
     * Returns the id of this resource.
     *
     * @return int the id of this resource in the source course
     */
    public function get_id()
    {
        return $this->source_id;
    }

    /**
     * Resturns the type of this resource.
     *
     * @return int the type
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * Get the constant which defines the tool of this resource. This is
     * used in the item_properties table.
     *
     * @param bool $for_item_property_table (optional)    Added by Ivan,
     *                                      29-AUG-2009: A parameter for resolving differencies between defined TOOL_*
     *                                      constants and hardcoded strings that are stored in the database.
     *                                      Example: The constant TOOL_THREAD is defined in the main_api.lib.php
     *                                      with the value 'thread', but the "Forums" tool records in the field 'tool'
     *                                      in the item property table the hardcoded value 'forum_thread'.
     *
     * @todo once the RESOURCE_* constants are replaced by the globally
     * defined TOOL_* constants, this function will be replaced by get_type()
     */
    public function get_tool($for_item_property_table = true)
    {
        switch ($this->get_type()) {
            case RESOURCE_DOCUMENT:
                return TOOL_DOCUMENT;
            case RESOURCE_LINK:
                return TOOL_LINK;
            case RESOURCE_EVENT:
                return TOOL_CALENDAR_EVENT;
            case RESOURCE_COURSEDESCRIPTION:
                return TOOL_COURSE_DESCRIPTION;
            case RESOURCE_LEARNPATH:
                return TOOL_LEARNPATH;
            case RESOURCE_ANNOUNCEMENT:
                return TOOL_ANNOUNCEMENT;
            case RESOURCE_FORUMCATEGORY:
                // Ivan, 29-AUG-2009: A constant like TOOL_FORUM_CATEGORY is missing in main_api.lib.php.
                // Such a constant has been defined in the forum tool for local needs.
                return 'forum_category';
            case RESOURCE_FORUM:
                return TOOL_FORUM;
            case RESOURCE_FORUMTOPIC:
                if ($for_item_property_table) {
                    // Ivan, 29-AUG-2009: A hardcoded value that the "Forums" tool stores in the item property table.
                    return 'forum_thread';
                }

                return TOOL_THREAD;
            case RESOURCE_FORUMPOST:
                return TOOL_POST;
            case RESOURCE_QUIZ:
                return TOOL_QUIZ;
            case RESOURCE_TEST_CATEGORY:
                return TOOL_TEST_CATEGORY;
            //case RESOURCE_QUIZQUESTION: //no corresponding global constant
            //	return TOOL_QUIZ_QUESTION;
            //case RESOURCE_TOOL_INTRO:
            //	return TOOL_INTRO;
            //case RESOURCE_LINKCATEGORY:
            //	return TOOL_LINK_CATEGORY;
            //case RESOURCE_SCORM:
            //	return TOOL_SCORM_DOCUMENT;
            case RESOURCE_SURVEY:
                return TOOL_SURVEY;
            //case RESOURCE_SURVEYQUESTION:
            //	return TOOL_SURVEY_QUESTION;
            //case RESOURCE_SURVEYINVITATION:
            //	return TOOL_SURVEY_INVITATION;
            case RESOURCE_GLOSSARY:
                return TOOL_GLOSSARY;
            case RESOURCE_WIKI:
                return TOOL_WIKI;
            case RESOURCE_THEMATIC:
                return TOOL_COURSE_PROGRESS;
            case RESOURCE_ATTENDANCE:
                return TOOL_ATTENDANCE;
            case RESOURCE_WORK:
                return TOOL_STUDENTPUBLICATION;
            default:
                return null;
        }
    }

    /**
     * Set the destination id.
     *
     * @param int $id the id of this resource in the destination course
     */
    public function set_new_id($id)
    {
        $this->destination_id = $id;
    }

    /**
     * Check if this resource is already restored in the destination course.
     *
     * @return bool true if already restored (i.e. destination_id is set).
     */
    public function is_restored()
    {
        return $this->destination_id > -1;
    }

    /**
     * Show this resource.
     */
    public function show()
    {
        //echo 'RESOURCE: '.$this->get_id().' '.$type[$this->get_type()].' ';
    }

    /**
     * Fix objects coming from 1.9.x to 1.10.x
     * Example class Event to CalendarEvent.
     *
     * @param resource $resource
     */
    public static function setClassType(&$resource)
    {
        $class = get_class($resource);
        switch ($class) {
            case 'Event':
                /** @var $resource CalendarEvent */
                $newResource = new CalendarEvent(
                    $resource->source_id,
                    $resource->title,
                    $resource->content,
                    $resource->start_date,
                    $resource->end_date,
                    $resource->attachment_path,
                    $resource->attachment_filename,
                    $resource->attachment_size,
                    $resource->attachment_comment,
                    $resource->all_day
                );
                $resource = $newResource;
                break;
            case 'CourseDescription':
                if (!method_exists($resource, 'show')) {
                    $resource = (array) $resource;
                    $newResource = new CourseDescription(
                        isset($resource['id']) ? $resource['id'] : '',
                        $resource['title'],
                        $resource['content'],
                        $resource['description_type']
                    );
                    $newResource->source_id = $resource['source_id'];
                    $newResource->destination_id = $resource['source_id'];
                    $newResource->linked_resources = $resource['source_id'];
                    $newResource->item_properties = $resource['source_id'];
                    $newResource->obj = $resource['obj'];
                    $resource = $newResource;
                }

                break;
        }
    }
}
