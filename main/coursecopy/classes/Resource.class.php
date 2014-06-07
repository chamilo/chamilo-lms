<?php
/* For licensing terms, see /license.txt */

define('RESOURCE_DOCUMENT', 'document');
define('RESOURCE_GLOSSARY', 'glossary');
define('RESOURCE_EVENT', 'calendar_event');
define('RESOURCE_LINK', 'link');
define('RESOURCE_COURSEDESCRIPTION', 'course_description');
define('RESOURCE_LEARNPATH', 'learnpath');
define('RESOURCE_ANNOUNCEMENT', 'announcement');
define('RESOURCE_FORUM', 'forum');
define('RESOURCE_FORUMTOPIC', 'thread');
define('RESOURCE_FORUMPOST', 'post');
define('RESOURCE_QUIZ', 'quiz');
define('RESOURCE_TEST_CATEGORY', 'test_category');
define('RESOURCE_QUIZQUESTION', 'Exercise_Question');
define('RESOURCE_TOOL_INTRO', 'Tool introduction');
define('RESOURCE_LINKCATEGORY', 'Link_Category');
define('RESOURCE_FORUMCATEGORY', 'Forum_Category');
define('RESOURCE_SCORM', 'Scorm');
define('RESOURCE_SURVEY', 'survey');
define('RESOURCE_SURVEYQUESTION', 'survey_question');
define('RESOURCE_SURVEYINVITATION', 'survey_invitation');
define('RESOURCE_WIKI', 'wiki');
define('RESOURCE_THEMATIC', 'thematic');
define('RESOURCE_ATTENDANCE', 'attendance');
define('RESOURCE_WORK', 'work');

/**
 * Class Resource
 * Representation of a resource in a Chamilo-course.
 * This is a base class of which real resource-classes (for Links,
 * Documents,...) should be derived.
 * @author   Bart Mollet <bart.mollet@hogent.be>s
 * @package  chamilo.backup
 * @todo Use the gloabaly defined constants voor tools and remove the RESOURCE_*
 * constants
 */
class Resource
{
    /**
     * The id from this resource in the source course
     */
    public $source_id;

    /**
     * The id from this resource in the destination course
     */
    public $destination_id;

    /**
     * The type of this resource
     */
    public $type;

    /**
     * Linked resources
     */
    public $linked_resources;

    /**
     * The properties of this resource
     */
    public $item_properties;

    public $obj = null;

    /**
     * Create a new Resource
     * @param int $id The id of this resource in the source course.
     * @param constant $type The type of this resource.
     */
    function Resource($id, $type)
    {
        $this->source_id = $id;
        $this->type = $type;
        $this->destination_id = -1;
        $this->linked_resources = array();
        $this->item_properties = array();
    }

    /**
     * Add linked resource
     */
    function add_linked_resource($type, $id) {
        $this->linked_resources[$type][] = $id;
    }

    /**
     * Get linked resources
     */
    function get_linked_resources() {
        return $this->linked_resources;
    }

    /**
     * Checks if this resource links to a given resource
     */
    function links_to(& $resource) {
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
     * @return int The id of this resource in the source course.
     */
    function get_id() {
        return $this->source_id;
    }

    /**
     * Resturns the type of this resource
     * @return constant The type.
     */
    function get_type() {
        return $this->type;
    }

    /**
     * Get the constant which defines the tool of this resource. This is
     * used in the item_properties table.
     * @param bool $for_item_property_table (optional)	Added by Ivan, 29-AUG-2009: A parameter for resolving differencies between defined TOOL_* constants and hardcoded strings that are stored in the database.
     * Example: The constant TOOL_THREAD is defined in the main_api.lib.php with the value 'thread', but the "Forums" tool records in the field 'tool' in the item property table the hardcoded value 'forum_thread'.
     * @todo once the RESOURCE_* constants are replaced by the globally
     * defined TOOL_* constants, this function will be replaced by get_type()
     */
    function get_tool($for_item_property_table = true) {
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
                return 'forum_category'; // Ivan, 29-AUG-2009: A constant like TOOL_FORUM_CATEGORY is missing in main_api.lib.php. Such a constant has been defined in the forum tool for local needs.
            case RESOURCE_FORUM:
                return TOOL_FORUM;
            case RESOURCE_FORUMTOPIC:
                if ($for_item_property_table) {
                    return 'forum_thread'; // Ivan, 29-AUG-2009: A hardcoded value that the "Forums" tool stores in the item property table.
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
     * Set the destination id
     * @param int $id The id of this resource in the destination course.
     */
    function set_new_id($id) {
        $this->destination_id = $id;
    }

    /**
     * Check if this resource is allready restored in the destination course.
     * @return bool true if allready restored (i.e. destination_id is set).
     */
    function is_restored() {
        return $this->destination_id > -1;
    }

    /**
     * Show this resource
     */
    function show() {
        //echo 'RESOURCE: '.$this->get_id().' '.$type[$this->get_type()].' ';
    }
}
