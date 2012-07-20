<?php

namespace Link;

use Database;

/**
 * Database interface for link_category
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class LinkCategoryRepository
{

    /**
     * 
     * @return \Link\LinkCategoryRepository
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    public function save($category)
    {
        $id = $category->id;
        if (empty($id)) {
            return $this->insert($category);
        } else {
            return $this->update($category);
        }
    }

    public function insert($category)
    {
        $c_id = (int) $category->c_id;

        $session_id = (int) $category->session_id;
        $session_id = $session_id ? $session_id : '0';

        $category_title = trim($category->category_title);
        $category_title = Database::escape_string($category_title);

        $description = trim($category->description);
        $description = Database::escape_string($description);

        $display_order = $this->next_display_order($c_id);

        $table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $sql = "INSERT INTO $table 
                    (c_id, category_title, description, display_order, session_id)
			    VALUES 
                    ($c_id , '$category_title', '$description', $display_order, $session_id)";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $id = Database::insert_id();
            $category->id = $id;
            $category->display_order = $display_order;
        }
        return $result;
    }

    function update($category)
    {
        $c_id = (int) $category->c_id;
        $id = (int) $category->id;

        $session_id = (int) $category->session_id;
        $session_id = $session_id ? $session_id : '0';

        $category_title = trim($category->category_title);
        $category_title = Database::escape_string($category_title);

        $description = trim($category->description);
        $description = Database::escape_string($description);

        $display_order = (int) $category->display_order;

        $table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $sql = "UPDATE $table SET                    
                    category_title = '$category_title',
                    description = '$description',
                    display_order = $display_order,
                    session_id = $session_id
			    WHERE
                    c_id = $c_id AND
                    id = $id";
        $result = (bool) Database :: query($sql);
        return $result;
    }

    function remove($category)
    {
        $table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $c_id = (int) $category->c_id;
        $id = (int) $category->id;

        $sql = "DELETE FROM $table WHERE c_id=$c_id AND id=$id";
        $success = (bool) Database :: query($sql);

        if ($success) {
            LinkRepository::instance()->remove_by_category($category);
        }
        return $success;
    }
    
    function remove_by_course($c_id, $session_id = 0)
    {
        $result = true;
        $categories = $this->find_by_course($c_id, $session_id);
        foreach($categories as $category){
            $success = $this->remove($category);
            if(!$success){
                $result = false;
            }
        }
        return $result;
    }

    function next_display_order($c_id)
    {
        $table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $sql = "SELECT MAX(display_order) FROM  $table WHERE c_id = $c_id ";
        $rs = Database :: query($sql);
        list ($result) = Database :: fetch_row($rs);
        $result = $result + 1;
        $result = intval($result);
        return $result;
    }

    /**
     *
     * @param string $where
     * @return array
     */
    public function find($where)
    {
        $result = array();
        $table = Database :: get_course_table(TABLE_LINK_CATEGORY);
        $where = $where ? "WHERE $where" : '';
        $sql = "SELECT * FROM $table $where ORDER BY display_order DESC";
        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            $result[] = LinkCategory::create($data);
        }
        return $result;
    }

    /**
     *
     * @param string $where
     * @return \Link\LinkCategory 
     */
    public function find_one($where)
    {
        $items = $this->find($where);
        foreach ($items as $item) {
            return $item;
        }
        return null;
    }

    /**
     *
     * @param int $c_id
     * @param int  $id
     * @return \Link\LinkCategory 
     */
    public function find_one_by_id($c_id, $id)
    {
        $where = "c_id = $c_id AND id = $id";
        return $this->find_one($where);
    }


    /**
     *
     * @param int $c_id
     * @param int $session_id
     * @param string $title
     * @return \Link\LinkCategory 
     */
    public function find_one_by_course_and_name($c_id, $session_id = 0, $title)
    {
        $c_id = (int) $c_id;
        $session_id = (int) $session_id;
        $title = Database::escape_string($title);
        
        $condition_session = api_get_session_condition($session_id, true, true);
        $where = "c_id = $c_id $condition_session AND category_title = '$title'";
        return $this->find_one($where);
    }

    function find_by_course($c_id, $session_id = 0)
    {
        $c_id = (int) $c_id;
        $session_id = (int) $session_id;
        $condition_session = api_get_session_condition($session_id, true, true);
        $where = "c_id = $c_id $condition_session";
        return $this->find($where);
    }

    /**
     * Ensure $ids are sorted in the order given from greater to lower.
     * 
     * Simple algorithm, works only if all ids are given at the same time.
     * This would not work if pagination were used and only a subset were sorted.
     * On the other hand the algorithm is sturdy, it will work even if current 
     * weights/display orders are not correctly set up in the db.
     * 
     * @param int $c_id
     * @param array $ids 
     */
    public function order($c_id, $ids)
    {
        $result = true;
        $ids = array_map('intval', $ids);
        $table = Database::get_course_table(TABLE_LINK_CATEGORY);

        $counter = 0;
        $weight = count($ids) + 1;
        foreach ($ids as $id) {
            $sql = "UPDATE $table SET display_order = $weight WHERE c_id = $c_id AND id = $id";
            $success = Database::query($sql);
            if (!$success) {
                $result = false;
            }
            --$weight;
        }
        return $result;
    }

}