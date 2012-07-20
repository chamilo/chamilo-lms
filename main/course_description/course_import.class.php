<?php

namespace CourseDescription;

/**
 * Import course descriptions into a course/session.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CourseImport
{

    protected $course = false;
    protected $update_existing_entries = false;
    protected $objects_imported = 0;
    protected $objects_skipped = 0;

    public function __construct($course)
    {
        $this->course = $course;
    }

    public function get_course()
    {
        return $this->course;
    }

    public function get_objects_imported()
    {
        return $this->objects_imported;
    }

    public function get_objects_skipped()
    {
        return $this->objects_skipped;
    }

    /**
     *
     * @param array $descriptions 
     */
    public function add($descriptions)
    {
        $this->objects_imported = 0;
        $this->objects_skipped = 0;

        foreach ($descriptions as $description) {
            $title = $description->title;
            $content = $description->content;
            $type = $description->type;
            if (empty($type)) {
                $type = CourseDescriptionType::repository()->find_one_by_name('general');
                $description->description_type = $type->id;
            }

            if (empty($title) || empty($content)) {
                $this->objects_skipped++;
                continue;
            }

//            $description = $this->find_by_title($title);
//            if ($description && $this->update_existing_entries == false) {
//                $this->objects_skipped++;
//                continue;
//            }
            $description->c_id = $this->course->c_id;
            $description->session_id = $this->course->session_id;
            $repo = CourseDescription::repository();
            $success = $repo->save($description);
            if ($success) {
                $this->objects_imported++;
            } else {
                $this->objects_skipped++;
            }
        }
    }

    function find_by_title($title)
    {
        $c_id = $this->c_id;
        $session_id = $this->session_id;
        $repo = CourseDescriptionRepository::instance();
        $link = $repo->find_one_by_course_and_title($c_id, $session_id, $title);
        return $link;
    }

}