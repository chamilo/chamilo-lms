<?php

namespace Notebook;

/**
 * Import notebook entries into a course/session.
 * 
 * Usage
 *      
 *      //init
 *      $course = (object)array();
 *      $course->c_id = xxx;
 *      $course->session_id = xxx;
 *      $import = new CourseImport($course);
 * 
 *      //create notebook entry
 *      $item = (object)array();
 *      $item->title = 'xxx';
 *      $item->description = 'xxx';
 * 
 *      //import notebook entry
 *      $import->add($item);
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
     * @param array $items 
     */
    public function add($items)
    {
        $this->objects_imported = 0;
        $this->objects_skipped = 0;

        foreach ($items as $item) {
            $title = $item->title;
            $description = $item->description;

            if (empty($title) || empty($description)) {
                $this->objects_skipped++;
                continue;
            }
            
            $item->c_id = $this->course->c_id;
            $item->session_id = $this->course->session_id;
            $repo = Notebook::repository();
            $success = $repo->save($item);
            if ($success) {
                $this->objects_imported++;
            } else {
                $this->objects_skipped++;
            }
        }
    }

}