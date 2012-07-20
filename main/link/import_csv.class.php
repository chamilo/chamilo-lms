<?php

namespace Link;

/**
 * Import a csv file into the course/session.
 * 
 * 
 *
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class ImportCsv
{

    protected $c_id;
    protected $session_id;
    protected $path;
    protected $links_imported = 0;
    protected $links_skipped = 0;
    protected $update_existing_entries = false;

    public function __construct($c_id, $session_id, $path, $update_existing_entries = false)
    {
        $this->c_id = $c_id;
        $this->session_id = $session_id;
        $this->path = $path;
        $this->update_existing_entries = $update_existing_entries;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function get_c_id()
    {
        return $this->c_id;
    }

    public function get_session_id()
    {
        return $this->session_id;
    }

    public function get_links_imported()
    {
        return $this->links_imported;
    }

    public function get_links_skipped()
    {
        return $this->links_skipped;
    }

    public function get_update_existing_entries()
    {
        return $this->update_existing_entries;
    }

    /**
     * Read file and returns an array filled up with its' content.
     * 
     * @return array of objects
     */
    public function get_data()
    {
        $result = array();

        $path = $this->path;
        if (!is_readable($path)) {
            return array();
        }

        $items = \Import::csv_reader($path);
        foreach ($items as $item) {
            $item = (object) $item;
            $url = isset($item->url) ? trim($item->url) : '';
            $title = isset($item->title) ? trim($item->title) : '';
            $description = isset($item->description) ? trim($item->description) : '';
            $target = isset($item->target) ? trim($item->target) : '';
            $category_title = isset($item->category_title) ? trim($item->category_title) : '';
            $category_description = isset($item->category_description) ? trim($item->category_description) : '';
            if (empty($url)) {
                continue;
            }
            if ($category_title) {
                $category_title = \Security::remove_XSS($category_title);
                $category_description = \Security::remove_XSS($category_description);
            } else {
                $category_description = '';
            }

            $url = \Security::remove_XSS($url);
            $title = \Security::remove_XSS($title);
            $description = \Security::remove_XSS($description);
            $target = \Security::remove_XSS($target);

            $item->url = $url;
            $item->title = $title;
            $item->description = $description;
            $item->target = $target;
            $item->category_title = $category_title;
            $item->category_description = $category_description;
            $result[] = $item;
        }
        return $result;
    }

    public function run()
    {
        $path = $this->path;
        if (!is_readable($path)) {
            return false;
        }
        $this->links_imported = 0;
        $this->links_skipped = 0;

        $items = $this->get_data();
        foreach ($items as $item) {
            $url = $item->url;
            $title = $item->title;
            $description = $item->description;
            $target = $item->target;
            $category_title = $item->category_title;
            $category_description = $item->category_description;

            if ($category_title) {
                $category = $this->ensure_category($category_title, $category_description);
            }

            $link = $this->find_link_by_url($url);
            if ($link && $this->update_existing_entries == false) {
                $this->links_skipped++;
                continue;
            }
            if (empty($link)) {
                $link = new Link();
                $link->c_id = $this->c_id;
                $link->session_id = $this->session_id;
                $link->url = $url;
            }

            $link->title = $title;
            $link->description = $description;
            $link->target = $target;
            $link->category_id = $category ? $category->id : 0;
            $repo = LinkRepository::instance();
            $repo->save($link);
            $this->links_imported++;
        }
    }

    public function ensure_category($title, $description = '')
    {
        $c_id = $this->c_id;
        $session_id = $this->session_id;
        $repo = LinkCategoryRepository::instance();
        $result = $repo->find_one_by_course_and_name($c_id, $session_id, $title);
        if (empty($result)) {
            $result = new LinkCategory();
            $result->c_id = $c_id;
            $result->category_title = $title;
            $result->description = $description;
            $result->session_id = $session_id;
            $repo->save($result);
        }
        return $result;
    }

    public function find_link_by_url($url)
    {
        $c_id = $this->c_id;
        $session_id = $this->session_id;
        $repo = LinkRepository::instance();
        $link = $repo->find_one_by_course_and_url($c_id, $session_id, $url);
        return $link;
    }

}